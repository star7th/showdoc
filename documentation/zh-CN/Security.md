# 安全加固与部署指南

本指南覆盖 ShowDoc 入口文件的安全修复说明，以及生产环境部署时的安全加固建议。
对应的英文版本见 [Security.md](https://github.com/star7th/showdoc/blob/master/documentation/en/Security.md)。

## 本次安全修复说明

### 1. MCP 入口路由劫持（mcp.php）

**问题**：`mcp.php` 是 MCP 的固定入口，此前客户端可在 URL 中带 `?s=Api/xxx/xxx`。
`server/index.php` 检测到 `$_GET['s']` 时会覆盖 `REQUEST_URI`，把请求分发到任意
控制器/方法，完全绕过 MCP 的 Token 鉴权。

**修复**：`mcp.php` 现在固定忽略 query string（清空 `QUERY_STRING` 并移除 `$_GET['s']`），
所有请求只会被路由到 `Api/Mcp/index`。

**验证**：`POST /mcp.php?s=Api/Item/delete&item_id=1` 这类请求不会再命中通用路由。

### 2. 生产环境错误信息泄露（index.php / server/index.php）

**问题**：入口文件一律开启 Slim 错误中间件的 `displayErrorDetails`，异常时会向客户端
返回堆栈、绝对路径、数据库配置等敏感信息。

**修复**：错误详情默认关闭，仅在开发环境通过环境变量开启：

```bash
# .env 或服务器环境变量
APP_DEBUG=false   # 生产环境（默认）
APP_DEBUG=true    # 开发环境，开启详细错误信息
```

### 3. 安装状态检测逻辑（index.php / server/app/Home/Controller/IndexController.php）

**问题**：旧逻辑要求 `install` 目录**可写**且存在 `install.lock` 才视为已安装。
部署后把 `install` 目录设为只读加固（常见做法）会导致系统一直跳转到安装页，
安装页在错误条件下可被重新触发。

**修复**：只要存在 `install/install.lock` 即视为已安装，不再依赖目录可写性。

### 4. MCP 请求体大小限制（server/app/Api/Controller/McpController.php）

**问题**：MCP 入口对请求体大小没有限制，超大 POST 请求会占用大量内存。

**修复**：单次请求体上限 1MB，超出时返回 `INVALID_REQUEST`（-32600）错误。

### 5. MCP 批量请求频率限制（server/app/Api/Controller/McpController.php）

**问题**：批量 JSON-RPC 请求只校验 Token，不执行频率限制，可被用于高频打接口。

**修复**：批量请求与单请求一致，对 `ai_token` 执行频率限制检查；单次批量最多处理
10 个请求（`MAX_BATCH_REQUESTS`）。

### 6. SQLite 库文件远程代码执行（Sqlite 目录访问拦截 + 输入卫生）

**问题**：SQLite 数据库文件默认位于网站目录 `Sqlite/showdoc.db.php`，Web 服务器会按
PHP 解析它。登录用户可通过可写字段（如个人资料 name）把 `<?php ... __halt_compiler();`
写入数据库，随后直接访问 `/Sqlite/showdoc.db.php` 即可执行任意 PHP 代码（RCE）。

**修复**（双重拦截 + 输入卫生）：

1. **Nginx**：容器镜像构建时（entrypoint.sh）在
   `/opt/docker/etc/nginx/vhost.common.d/20-deny-serverbin.conf` 写入
   `location ^~ /Sqlite/ { deny all; }`，从访问层焊死。
2. **Apache**：仓库根 `.htaccess` 新增 `RewriteRule ^Sqlite/ - [F,L]`，
   确保已存在的 db.php 也被拦截。
3. **输入卫生**：SSO（SecretKey/OAuth2/CAS）自动注册的用户名统一应用与
   `registerByVerify` 相同的白名单
   `/^[a-zA-Z0-9_\-\x{4e00}-\x{9fa5}]{2,30}$/u`，不合法则拒绝注册；
   `Api/PageFeedback` 的 `client_id` 限制为十六进制/连字符格式
   `/^[a-f0-9\-]{8,64}$/i`，不合法置空。

**验证**：`curl -I http://your-host/Sqlite/showdoc.db.php` 应返回 403（Nginx）
或 403/404（Apache），而不是 200 或数据库内容。

> ⚠️ 注意：白名单收紧后，如果已有 SSO 用户的用户名含白名单外字符（如邮箱、空格），
> 该用户在**首次自动注册**时会被拒绝；已注册用户不受影响（登录查找在白名单校验之前）。

## 生产环境部署建议

### 拒绝访问 Sqlite 目录（自建 Nginx/Apache 必读）

官方 Docker 镜像（webdevops/php-nginx 基础镜像）已自动包含该拦截规则。
**自行搭建 Nginx/Apache 部署 ShowDoc 的用户必须手动添加 Sqlite 目录拒绝访问规则**，
否则 SQLite 库文件（`Sqlite/showdoc.db.php`）可被直接下载，且在被写入恶意 PHP 代码后
可被 Web 服务器解析执行，导致远程代码执行（RCE）：

Nginx 示例：

```nginx
location ^~ /Sqlite/ {
    deny all;
}
```

Apache 示例（httpd.conf 或 VirtualHost 内，mod_rewrite）：

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteRule ^Sqlite/ - [F,L]
</IfModule>
```

（仓库根目录的 `.htaccess` 已包含上述 Apache 规则；若 httpd 配置中 `AllowOverride None`
则 .htaccess 不生效，必须把规则写进主配置。）

修改后验证：`curl -I http://your-host/Sqlite/showdoc.db.php` 应返回 403，
而不是 200 或数据库内容。

### 使用 HTTPS

MCP 使用 Bearer Token 认证，请在 Nginx / Apache 层强制 HTTPS，避免 Token 明文传输。
同时在反代配置中设置 `Access-Control-Allow-Origin` 白名单。

### 安装目录处理

安装完成后建议：

```bash
# 保留锁文件，防止重新触发安装
touch install/install.lock
# 移除 install 目录写权限（可选，只读更安全）
chmod -R a-w install
# 或直接删除 install 目录
rm -rf install
```

### 关闭调试信息

确保环境变量 `APP_DEBUG` 为 `false`（默认值），并检查 PHP 配置：

```ini
display_errors = Off
```

### 限制 MCP 入口访问

如仅面向内网或特定 AI 编辑器使用，可在 Nginx 层对 `mcp.php` 做 IP / 网络访问控制：

```nginx
location = /mcp.php {
    allow 127.0.0.1;
    # 按需添加其它允许来源
    deny all;
}
```

### 请求体大小限制（前端服务器）

建议在 Nginx 层再次限制请求体大小，与 MCP 入口的 1MB 上限保持一致：

```nginx
client_max_body_size 1m;
```

### 其他建议

- 使用强密码，并在首次登录后修改管理员 `showdoc` 默认密码（`123456`）。
- 定期备份数据库与上传文件。
- 关注官方更新，及时升级修复安全漏洞。

## 相关文件

| 文件 | 说明 |
| --- | --- |
| `index.php` | 站点入口：安装检测、重定向、错误处理 |
| `server/index.php` | API 入口：路由分发、错误处理 |
| `mcp.php` | MCP 独立入口（JSON-RPC over HTTP） |
| `server/app/Api/Controller/McpController.php` | MCP 控制器：Token 鉴权、频率限制 |
