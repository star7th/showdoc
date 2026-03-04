# 接口 JSON 分享功能

## 功能概述

在单页项目的分享弹窗中，当勾选"创建单页"选项后，会显示一个 **JSON API 链接**，该链接返回纯 JSON 格式的页面内容，方便提供给 AI 或其他自动化工具使用。

## 使用流程

1. 打开单页项目的页面（如 `/#/13/242`）
2. 点击右上角的"分享"按钮
3. 勾选"创建单页"选项
4. 在弹窗中会显示三个链接：
   - **页面/项目地址**：用于浏览器访问的链接
   - **单页链接**：独立的单页访问链接
   - **JSON API 链接**：返回 JSON 格式数据的接口（新增）

## JSON API 接口

### 端点
```
GET /server/index.php?s=/api/page/jsonByKey&unique_key={unique_key}
```

### 参数
- `unique_key` (string, 必需)：单页的唯一标识符

### 响应示例
```json
{
  "page_id": 242,
  "page_title": "API 文档",
  "page_content": "# API 文档\n\n## 接口列表\n...",
  "page_comments": 0,
  "addtime": "2026-03-02 12:00:00",
  "unique_key": "abc123def456...",
  "expire_time": 0
}
```

### 响应字段说明
| 字段 | 类型 | 说明 |
|------|------|------|
| page_id | integer | 页面 ID |
| page_title | string | 页面标题 |
| page_content | string | 页面内容（Markdown 格式） |
| page_comments | integer | 页面评论数 |
| addtime | string | 创建时间（格式：YYYY-MM-DD HH:mm:ss） |
| unique_key | string | 单页唯一标识 |
| expire_time | integer | 过期时间戳（0 表示永久有效） |

### 错误响应
```json
{
  "error_code": 10101,
  "error_message": "该分享链接已过期或不存在"
}
```

## 技术实现

### 后端
- **文件**：`server/app/Api/Controller/PageController.php`
- **方法**：`jsonByKey()`
- **功能**：
  - 验证 unique_key 的有效性
  - 检查链接是否过期
  - 返回纯 JSON 格式的页面数据
  - 支持 CORS 跨域请求

### 前端
- **文件**：`web_src/src/views/modals/item/ShareModal/index.vue`
- **新增**：
  - `shareJsonLink` 计算属性：生成 JSON API 链接
  - `handleCopyJson()` 方法：复制 JSON 链接到剪贴板
  - 模板中的 JSON 链接显示区域

### 国际化
- **中文**：`web_src/src/i18n/zh-CN/item.ts` - `api_json_link: 'JSON API 链接'`
- **英文**：`web_src/src/i18n/en-US/item.ts` - `api_json_link: 'JSON API Link'`

## 使用场景

1. **AI 集成**：将页面内容提供给 AI 模型进行分析或生成
2. **自动化工具**：通过 API 自动获取文档内容进行处理
3. **第三方应用**：集成 ShowDoc 文档到其他系统
4. **数据导出**：以 JSON 格式导出页面数据

## 示例代码

### JavaScript/Node.js
```javascript
const uniqueKey = 'abc123def456...';
const response = await fetch(
  `https://example.com/server/index.php?s=/api/page/jsonByKey&unique_key=${uniqueKey}`
);
const data = await response.json();
console.log(data.page_title);
console.log(data.page_content);
```

### Python
```python
import requests
import json

unique_key = 'abc123def456...'
url = f'https://example.com/server/index.php?s=/api/page/jsonByKey&unique_key={unique_key}'
response = requests.get(url)
data = response.json()
print(data['page_title'])
print(data['page_content'])
```

### cURL
```bash
curl "https://example.com/server/index.php?s=/api/page/jsonByKey&unique_key=abc123def456..."
```

## 安全性说明

- 只有创建了单页链接的页面才能通过 JSON API 访问
- 支持设置链接过期时间，过期后自动失效
- 返回的数据不包含敏感的项目信息（如 item_id、cat_id）
- 支持 CORS 跨域请求，方便前端集成

## 注意事项

1. JSON API 链接仅在勾选"创建单页"后才会显示
2. 链接的有效期与单页链接保持一致
3. 页面内容为 Markdown 格式，需要在客户端进行渲染
4. 建议在生产环境中对 API 调用进行速率限制
