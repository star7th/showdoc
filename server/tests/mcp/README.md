# ShowDoc MCP 测试（开源版）

本目录包含 ShowDoc MCP (Model Context Protocol) 功能的自动化测试脚本。

## 安全说明

⚠️ **这些测试脚本只能通过命令行执行，禁止 web 访问。**

脚本会在运行时检查执行环境，如果通过 web 访问会返回 403 Forbidden。

## 文件说明

| 文件                    | 说明                                                 |
| ----------------------- | ---------------------------------------------------- |
| `mcp_test.php`          | 主测试脚本，运行所有测试用例                         |
| `bootstrap.php`         | 引导文件，包含公共函数、数据库初始化、McpTester 类   |
| `openapi3.json`         | OpenAPI 3.0 格式的测试文件，用于 import_openapi 测试 |

## 快速开始

```bash
# 在项目根目录运行
php server/tests/mcp/mcp_test.php
```

## 配置

测试脚本会自动从数据库中获取测试用户（优先使用管理员用户，其次普通用户），无需手动配置 UID。

如需修改 MCP 端点地址，在 `mcp_test.php` 中修改：

```php
define('MCP_URL', 'http://127.0.0.1/showdoc/mcp.php');
```

## 测试内容

### 1. 初始化测试 (2)

- MCP initialize
- 无 Token 访问被拒绝

### 2. Token 准备

- 自动获取或创建读写 Token（包含 `can_create_item` 和 `can_delete_item` 权限）
- 自动获取或创建只读 Token
- 自动创建指定项目范围 Token（用于权限测试）

### 3. 基础功能测试 (7)

- tools/list
- list_items
- get_item
- list_catalogs
- list_pages
- search_pages
- get_page_template

### 4. 项目操作测试 (2)

- create_item
- update_item

### 5. 目录操作测试 (3)

- create_catalog
- update_catalog
- get_catalog

### 6. 页面操作测试 (9)

- create_page
- get_page
- update_page
- upsert_page (create)
- upsert_page (update)
- batch_get_pages
- batch_upsert_pages
- create_page_by_comment

### 7. 历史版本测试 (4)

- get_page_history
- get_page_version
- diff_page_versions
- restore_page_version

### 8. 附件管理测试 (3)

- upload_attachment
- list_attachments
- delete_attachment

### 9. OpenAPI 导入测试 (1)

- import_openapi（支持 OpenAPI 3.0 格式）

### 10. 权限控制测试 (2)

- 只读 Token 写操作被拒绝
- 指定项目范围 Token 访问其他项目被拒绝

### 11. 边界条件测试 (8)

- get_item 空参数被拒绝
- get_page 空参数被拒绝
- create_page 缺少必要参数被拒绝
- get_item 无效ID类型被拒绝
- get_item 负数ID被拒绝
- create_item 超长名称被拒绝或截断
- search_pages 特殊字符处理
- create_page 空标题被拒绝

### 12. 错误场景测试 (8)

- get_item 不存在的项目
- get_page 不存在的页面
- get_catalog 不存在的目录
- 无效 Token 格式被拒绝
- 空 Token 被拒绝
- 不存在的方法被拒绝
- 无效 JSON-RPC 请求处理
- 更新无权限项目被拒绝

### 13. 业务逻辑测试 (3+)

- **get_page 内容解转义**：验证 MCP 返回的内容已正确解转义
- **create_page 存储时 HTML 转义**：验证存储到数据库时内容已转义
- **配额信息显示**：显示当前用户的空间使用情况

### 14. 清理测试数据

- 删除创建的页面、目录、项目
- 清理测试创建的 Token

## 开源版与主版差异

本测试脚本针对开源版进行了适配：

1. **无 VIP 功能**：移除了 VIP 相关测试，配额使用固定大值（100000 项目、1TB 空间）
2. **无敏感词检测**：移除了敏感词检测相关的测试分支
3. **无分表**：数据库查询使用单一 `page` 表，而非 `Page::tableForItem()`
4. **MCP 地址**：默认使用 `http://127.0.0.1/showdoc/mcp.php`

## 测试输出示例

```
=====================================
| ShowDoc MCP 功能自动化测试（开源版） |
=====================================

测试用户 UID: 1
用户类型: 管理员
已绑定邮箱: 是
MCP 地址: http://127.0.0.1/showdoc/mcp.php

=======================
| 1. 初始化测试 |
=======================

  ✓ MCP initialize - 2024-11-05
  ✓ 无 Token 访问被拒绝 - 正确返回错误: Token 无效或已过期

========================
| 2. 准备测试 Token |
========================

  ℹ 读写 Token: ai_b3f925f...ceca4b
  ℹ   - 权限: write
  ℹ   - 范围: all
  ℹ   - 可创建项目: 是
  ℹ   - 可删除项目: 是
  ℹ 只读 Token: ai_12da512...3e36d7

...

=======================
| 13. 业务逻辑测试 |
=======================

  ✓ get_page 内容解转义 - 内容正确解转义
  ✓ create_page 存储时 HTML 转义 - 存储时正确转义
  ℹ 当前用户项目数: 5, 开源版配额上限: 100000
  ✓ create_item 配额限制 - 项目数 5 未达上限 100000，跳过配额测试
  ℹ 已用空间: 1.23 MB / 1024.00 GB

...

================
| 测试总结 |
================

  通过: 55
  失败: 0
  总计: 55
  成功率: 100%

测试完成！
```

## 调试模式

在 `mcp_test.php` 中设置 `$debugMode = true` 可以显示详细的 API 响应：

```php
$debugMode = true;
```

## 注意事项

1. **自动用户选择**：测试脚本会自动从数据库中选择用户（优先管理员 groupid=1，其次普通用户 groupid=2）
2. **数据库连接**：测试脚本会自动加载项目根目录的 `.env` 文件获取数据库配置
3. **测试数据清理**：测试完成后会自动删除创建的项目、目录、页面和 Token
4. **权限测试**：需要至少 2 个项目才能完整测试指定项目范围的权限控制
5. **附件测试**：使用 Base64 编码的测试图片，无需真实文件
6. **Token 权限**：读写 Token 会自动设置 `can_create_item=1` 和 `can_delete_item=1`
7. **安全设计**：访问不存在的项目时，系统返回"无权限"而非"不存在"，避免暴露项目是否存在
8. **业务逻辑测试**：包含 HTML 转义/解转义测试

## 测试覆盖

| 类别         | 测试数量 |
| ------------ | -------- |
| 初始化       | 2        |
| 基础功能     | 7        |
| 项目操作     | 2        |
| 目录操作     | 3        |
| 页面操作     | 9        |
| 历史版本     | 4        |
| 附件管理     | 3        |
| OpenAPI 导入 | 1        |
| 权限控制     | 2        |
| 边界条件     | 8        |
| 错误场景     | 8        |
| 业务逻辑     | 3+       |
| 清理         | 动态     |
| **总计**     | **52+**  |
