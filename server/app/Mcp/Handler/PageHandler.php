<?php

namespace App\Mcp\Handler;

use App\Mcp\McpHandler;
use App\Mcp\McpError;
use App\Mcp\McpException;
use App\Model\Page;
use App\Model\PageHistory;
use App\Model\Catalog;
use App\Model\Item;
use App\Common\Helper\Convert;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * MCP 页面操作 Handler（开源版）
 *
 * 注意：开源版使用单表 page，不支持分表。
 * 开源版不包含 BadKeywords、Vip、ItemWhitelist 等商业功能。
 */
class PageHandler extends McpHandler
{
  /**
   * 获取支持的操列列表
   *
   * @return array
   */
  public function getSupportedOperations(): array
  {
    return [
      'list_pages',
      'get_page',
      'batch_get_pages',
      'search_pages',
      'get_page_template',
      'create_page',
      'create_page_by_comment',
      'update_page',
      'upsert_page',
      'batch_upsert_pages',
      'delete_page',
      // 页面历史相关
      'get_page_history',
      'get_page_version',
      'diff_page_versions',
      'restore_page_version',
    ];
  }

  /**
   * 执行操作
   *
   * @param string $operation 操作名称
   * @param array $params 参数
   * @return mixed
   * @throws McpException
   */
  public function execute(string $operation, array $params = [])
  {
    switch ($operation) {
      case 'list_pages':
        return $this->listPages($params);

      case 'get_page':
        return $this->getPage($params);

      case 'batch_get_pages':
        return $this->batchGetPages($params);

      case 'search_pages':
        return $this->searchPages($params);

      case 'get_page_template':
        return $this->getPageTemplate($params);

      case 'create_page':
        return $this->createPage($params);

      case 'create_page_by_comment':
        return $this->createPageByComment($params);

      case 'update_page':
        return $this->updatePage($params);

      case 'upsert_page':
        return $this->upsertPage($params);

      case 'batch_upsert_pages':
        return $this->batchUpsertPages($params);

      case 'delete_page':
        return $this->deletePage($params);

      case 'get_page_history':
        return $this->getPageHistory($params);

      case 'get_page_version':
        return $this->getPageVersion($params);

      case 'diff_page_versions':
        return $this->diffPageVersions($params);

      case 'restore_page_version':
        return $this->restorePageVersion($params);

      default:
        McpError::throw(McpError::METHOD_NOT_FOUND, "操作不存在: {$operation}");
    }
  }

  /**
   * 获取项目/目录下的页面列表（分页，不含内容）
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function listPages(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }

    // 检查读取权限
    $this->requireReadPermission($itemId);

    $catId = (int) ($params['cat_id'] ?? 0);
    $page = max(1, (int) ($params['page'] ?? 1));
    $pageSize = min(100, max(1, (int) ($params['page_size'] ?? 50)));

    // 开源版：使用单表 page，不使用分表
    $query = DB::table('page')
      ->where('item_id', $itemId)
      ->where('is_del', 0);

    if ($catId > 0) {
      $query->where('cat_id', $catId);
    }

    // 获取总数
    $total = $query->count();

    // 分页获取
    $pages = $query->orderBy('s_number', 'asc')
      ->orderBy('page_id', 'asc')
      ->offset(($page - 1) * $pageSize)
      ->limit($pageSize)
      ->get(['page_id', 'page_title', 'item_id', 'cat_id', 's_number', 'addtime', 'author_uid', 'author_username'])
      ->all();

    $result = [];
    foreach ($pages as $p) {
      $result[] = [
        'page_id' => (int) $p->page_id,
        'page_title' => $p->page_title,
        'item_id' => (int) $p->item_id,
        'cat_id' => (int) ($p->cat_id ?? 0),
        's_number' => (int) ($p->s_number ?? 0),
        'addtime' => $p->addtime ?? '',
        'author_uid' => (int) ($p->author_uid ?? 0),
        'author_username' => $p->author_username ?? '',
      ];
    }

    return [
      'item_id' => $itemId,
      'cat_id' => $catId,
      'pages' => $result,
      'total' => $total,
      'page' => $page,
      'page_size' => $pageSize,
    ];
  }

  /**
   * 获取页面详情
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function getPage(array $params): array
  {
    $pageId = (int) ($params['page_id'] ?? 0);
    $itemId = (int) ($params['item_id'] ?? 0);
    $pageTitle = trim($params['page_title'] ?? '');

    // 支持通过 page_id 或 item_id + page_title 获取
    if ($pageId <= 0 && ($itemId <= 0 || $pageTitle === '')) {
      McpError::throw(McpError::INVALID_PARAMS, '请提供 page_id 或 item_id + page_title');
    }

    // 如果是通过标题查找（开源版：使用单表 page）
    if ($pageId <= 0 && $itemId > 0 && $pageTitle !== '') {
      $pageRow = DB::table('page')
        ->where('item_id', $itemId)
        ->where('page_title', $pageTitle)
        ->where('is_del', 0)
        ->first();
      if (!$pageRow) {
        McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
      }
      $pageId = (int) $pageRow->page_id;
    }

    // 先从 page 表获取 item_id（与原后端 PageController::info 一致）
    $pageRow = DB::table('page')
      ->where('page_id', $pageId)
      ->first();
    if (!$pageRow) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
    }
    $itemId = (int) $pageRow->item_id;

    // 使用 Page::findPageByCache 获取页面（带缓存，自动处理分表和解压）
    $page = Page::findPageByCache($pageId, $itemId);
    if (!$page || (int) ($page['is_del'] ?? 0) === 1) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
    }

    // 检查草稿状态（与原后端 PageController::info 一致）
    if (($page['is_draft'] ?? 0) == 1) {
      $authorUid = (int) ($page['author_uid'] ?? 0);
      $currentUid = (int) ($this->user['uid'] ?? 0);
      if ($currentUid <= 0 || $currentUid !== $authorUid) {
        McpError::throw(McpError::RESOURCE_NOT_FOUND, '该页面是草稿状态，暂不可访问');
      }
    }

    // 检查读取权限
    $this->requireReadPermission($itemId);

    // 获取项目类型
    $item = DB::table('item')
      ->where('item_id', $itemId)
      ->first();
    $itemType = (int) ($item->item_type ?? 1);

    // 处理内容（Page::findPageByCache 已自动解压）
    $content = $page['page_content'] ?? '';
    $pageType = 'markdown';

    // 如果是 RunApi 项目，转换为 Markdown
    if ($itemType === 3) {
      $pageType = 'runapi';
      $convert = new Convert();
      $content = $convert->runapiToMd($content);
    }

    // 计算内容哈希（用于乐观锁）
    $contentHash = substr(md5($content), 0, 12);

    // 对内容进行 HTML 解转义（MCP 场景下 AI 需要原始内容）
    $content = htmlspecialchars_decode($content, ENT_QUOTES);

    // 格式化 addtime（与原后端 PageController::info 一致）
    $addtime = date('Y-m-d H:i:s', (int) ($page['addtime'] ?? time()));

    // 附件数量（与原后端 PageController::info 一致）
    $attachmentCount = \App\Model\FilePage::getAttachmentCount($pageId);

    return [
      'page_id' => (int) $page['page_id'],
      'page_title' => $page['page_title'],
      'item_id' => $itemId,
      'cat_id' => (int) ($page['cat_id'] ?? 0),
      'type' => $pageType,
      'content' => $content,
      'content_hash' => $contentHash,
      's_number' => (int) ($page['s_number'] ?? 0),
      'addtime' => $addtime,
      'author_uid' => (int) ($page['author_uid'] ?? 0),
      'author_username' => $page['author_username'] ?? '',
      'attachment_count' => $attachmentCount,
    ];
  }

  /**
   * 批量获取页面详情（最多10个）
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function batchGetPages(array $params): array
  {
    $pageIds = $params['page_ids'] ?? [];
    if (!is_array($pageIds) || empty($pageIds)) {
      McpError::throw(McpError::INVALID_PARAMS, 'page_ids 必须是非空数组');
    }

    // 限制最多10个
    $pageIds = array_slice(array_map('intval', $pageIds), 0, 10);

    $result = [];
    foreach ($pageIds as $pageId) {
      try {
        $pageData = $this->getPage(['page_id' => $pageId]);
        $result[] = [
          'status' => 'success',
          'page_id' => $pageId,
          'data' => $pageData,
        ];
      } catch (McpException $e) {
        $result[] = [
          'status' => 'failed',
          'page_id' => $pageId,
          'error' => $e->getMessage(),
        ];
      }
    }

    return [
      'pages' => $result,
      'total' => count($result),
    ];
  }

  /**
   * 搜索页面
   *
   * @param array $params 参数
   *   - query: 搜索关键字（必填）
   *   - item_id: 项目ID（可选，不传则搜索所有有权限的项目）
   *   - search_mode: 搜索模式（可选，默认 title）
   *     - title: 只搜索标题（数据库 LIKE，速度快）
   *     - content: 只搜索内容（需解压后 PHP 层面搜索，速度慢）
   *     - all: 搜索标题和内容（需解压后 PHP 层面搜索，速度慢）
   * @return array
   * @throws McpException
   */
  private function searchPages(array $params): array
  {
    $query = trim($params['query'] ?? '');
    if ($query === '') {
      McpError::throw(McpError::INVALID_PARAMS, '搜索关键字不能为空');
    }

    $itemId = (int) ($params['item_id'] ?? 0);
    // 搜索模式：title（默认，只搜索标题）、content（只搜索内容）、all（搜索标题和内容）
    $searchMode = $params['search_mode'] ?? 'title';
    if (!in_array($searchMode, ['title', 'content', 'all'], true)) {
      $searchMode = 'title';
    }

    $result = [];
    $maxResults = 50;
    $queryLower = strtolower($query);

    // 如果指定了项目ID，只搜索该项目的分表
    if ($itemId > 0) {
      // 检查读取权限
      $this->requireReadPermission($itemId);

      $result = $this->searchInItem($itemId, $query, $queryLower, $searchMode, $maxResults);
    } else {
      // 没有指定项目ID，遍历用户有权限的所有项目
      $uid = $this->getUid();
      $itemIds = $this->getUserItemIds($uid);

      foreach ($itemIds as $itemId) {
        if (count($result) >= $maxResults) {
          break;
        }

        $remaining = $maxResults - count($result);
        $found = $this->searchInItem($itemId, $query, $queryLower, $searchMode, $remaining);
        $result = array_merge($result, $found);
      }
    }

    return [
      'query' => $query,
      'item_id' => $itemId,
      'search_mode' => $searchMode,
      'pages' => $result,
      'total' => count($result),
    ];
  }

  /**
   * 在单个项目中搜索页面
   *
   * @param int $itemId 项目ID
   * @param string $query 原始搜索关键字
   * @param string $queryLower 小写搜索关键字
   * @param string $searchMode 搜索模式
   * @param int $limit 最大返回数量
   * @return array
   */
  private function searchInItem(int $itemId, string $query, string $queryLower, string $searchMode, int $limit): array
  {
    $result = [];
    $tableName = Page::tableForItem($itemId);

    // 标题搜索模式：直接数据库 LIKE 搜索（速度快）
    if ($searchMode === 'title') {
      $pages = DB::table($tableName)
        ->where('item_id', $itemId)
        ->where('is_del', 0)
        ->where('page_title', 'like', "%{$query}%")
        ->limit($limit)
        ->get(['page_id', 'page_title', 'item_id', 'cat_id', 'addtime'])
        ->all();

      foreach ($pages as $p) {
        $result[] = [
          'page_id' => (int) $p->page_id,
          'page_title' => $p->page_title,
          'item_id' => (int) $p->item_id,
          'cat_id' => (int) ($p->cat_id ?? 0),
          'addtime' => $p->addtime ?? '',
        ];
      }

      // 释放内存
      unset($pages);
      return $result;
    }

    // 内容搜索模式：需要加载到 PHP 内存解压后搜索（速度慢）
    // 限制最多加载 500 条记录，避免大项目下加载全部页面导致内存/CPU 爆炸
    $pages = DB::table($tableName)
      ->where('item_id', $itemId)
      ->where('is_del', 0)
      ->orderBy('s_number')
      ->orderBy('page_id')
      ->limit(500)
      ->get(['page_id', 'page_title', 'page_content', 'item_id', 'cat_id', 'addtime'])
      ->all();

    foreach ($pages as $p) {
      if (count($result) >= $limit) {
        break;
      }

      $pageTitle = strtolower((string) ($p->page_title ?? ''));
      $pageContent = (string) ($p->page_content ?? '');

      // 解压内容
      $decoded = \App\Common\Helper\ContentCodec::decompress($pageContent);
      if ($decoded !== '') {
        $pageContent = $decoded;
      }
      $pageContentLower = strtolower($pageContent);

      // 根据搜索模式匹配
      $matched = false;
      if ($searchMode === 'content') {
        // 只搜索内容
        $matched = strpos($pageContentLower, $queryLower) !== false;
      } elseif ($searchMode === 'all') {
        // 搜索标题和内容
        $matched = strpos($pageTitle, $queryLower) !== false || strpos($pageContentLower, $queryLower) !== false;
      }

      if ($matched) {
        $result[] = [
          'page_id' => (int) $p->page_id,
          'page_title' => $p->page_title,
          'item_id' => (int) $p->item_id,
          'cat_id' => (int) ($p->cat_id ?? 0),
          'addtime' => $p->addtime ?? '',
        ];
      }

      // 释放当前页面内容的内存（特别是解压后的大内容）
      unset($pageContent, $pageContentLower, $decoded);
    }

    // 释放内存
    unset($pages);

    return $result;
  }

  /**
   * 获取用户有权限的项目ID列表
   *
   * @param int $uid 用户ID
   * @return array
   */
  private function getUserItemIds(int $uid): array
  {
    $itemIds = [];

    // 用户创建的项目
    $createdItems = DB::table('item')
      ->where('uid', $uid)
      ->where('is_del', 0)
      ->pluck('item_id')
      ->all();
    $itemIds = array_merge($itemIds, $createdItems);

    // 用户作为成员的项目
    $memberItems = DB::table('item_member')
      ->where('uid', $uid)
      ->pluck('item_id')
      ->all();
    $itemIds = array_merge($itemIds, $memberItems);

    // 用户所在团队的项目
    $teamItems = DB::table('team_item_member')
      ->where('member_uid', $uid)
      ->pluck('item_id')
      ->all();
    $itemIds = array_merge($itemIds, $teamItems);

    // 根据 Token scope 过滤
    $scope = $this->tokenInfo['scope'] ?? 'all';
    if ($scope === 'selected') {
      $allowedItems = json_decode($this->tokenInfo['allowed_items'] ?? '[]', true) ?: [];
      $itemIds = array_intersect($itemIds, $allowedItems);
    }

    return array_unique(array_map('intval', $itemIds));
  }

  /**
   * 获取文档模板
   *
   * @param array $params 参数
   * @return array
   */
  private function getPageTemplate(array $params): array
  {
    $type = $params['type'] ?? 'api';

    $templates = [
      'api' => $this->getApiTemplate(),
      'runapi_comment' => $this->getRunapiCommentTemplate(),
      'database' => $this->getDatabaseTemplate(),
      'general' => $this->getGeneralTemplate(),
    ];

    $template = $templates[$type] ?? $templates['api'];

    return [
      'type' => $type,
      'template' => $template,
    ];
  }

  /**
   * API 文档模板
   *
   * @return string
   */
  private function getApiTemplate(): string
  {
    return <<<'MARKDOWN'
# 接口名称

**简要描述**：接口功能的简要说明

#### 基本信息

- **请求方式**：POST
- **请求路径**：/api/xxx
- **接口描述**：详细的接口功能描述

#### 请求头

| 参数名 | 必填 | 类型 | 说明 |
| ------ | ---- | ---- | ---- |
| Authorization | 是 | string | 认证令牌，格式：Bearer xxx |
| Content-Type | 是 | string | application/json |

#### 请求参数

##### Query 参数（URL 参数）

| 参数名 | 必填 | 类型 | 说明 |
| ------ | ---- | ---- | ---- |
| page | 否 | int | 页码，默认 1 |
| pageSize | 否 | int | 每页数量，默认 20 |

##### Body 参数（请求体）

| 参数名 | 必填 | 类型 | 说明 |
| ------ | ---- | ---- | ---- |
| field1 | 是 | string | 字段1说明 |
| field2 | 否 | int | 字段2说明 |
| field3 | 否 | array | 字段3说明，数组类型 |

#### 返回参数

| 参数名 | 类型 | 说明 |
| ------ | ---- | ---- |
| code | int | 状态码，0 表示成功，非 0 表示失败 |
| msg | string | 提示信息 |
| data | object | 返回数据 |
| data.list | array | 数据列表 |
| data.total | int | 总数量 |
| data.page | int | 当前页码 |

#### 返回示例

##### 成功响应

```json
{
  "code": 0,
  "msg": "success",
  "data": {
    "list": [],
    "total": 100,
    "page": 1
  }
}
```

##### 失败响应

```json
{
  "code": 10001,
  "msg": "参数错误",
  "data": null
}
```

#### 错误码说明

| 错误码 | 说明 |
| ------ | ---- |
| 0 | 成功 |
| 10001 | 参数错误 |
| 10002 | 未授权 |
| 10003 | 资源不存在 |

#### 备注

- 其他需要说明的内容
- 特殊情况处理说明
MARKDOWN;
  }

  /**
   * RunApi 注释模板
   *
   * @return string
   */
  private function getRunapiCommentTemplate(): string
  {
    return <<<'COMMENT'
/**
 * showdoc
 * @title 接口名称
 * @description 接口的详细功能描述，说明这个接口做什么
 * @method POST
 * @url /api/path/to/endpoint
 * @catalog 目录名称/子目录名称
 *
 * @param page int 否选 页码，默认1
 * @param page_size int 否选 每页数量，默认20，最大100
 * @param keyword string 否选 搜索关键词
 * @param id int 是选 数据ID
 *
 * @header Authorization string 是选 认证令牌，格式：Bearer xxx
 * @header Content-Type string 是选 固定值：application/json
 * @header X-Request-Id string 否选 请求追踪ID
 *
 * @json_param name string 是选 用户名
 * @json_param email string 是选 邮箱地址
 * @json_param age int 否选 用户年龄
 * @json_param tags array 否选 标签列表
 *
 * @return {"code":0,"msg":"success","data":{"id":1,"name":"示例","created_at":"2024-01-01 00:00:00"}}
 * @return_param code int 状态码，0表示成功，非0表示失败
 * @return_param msg string 提示信息
 * @return_param data object 返回数据对象
 * @return_param data.id int 数据ID
 * @return_param data.name string 数据名称
 * @return_param data.created_at string 创建时间
 *
 * @remark 这是一个POST请求示例，使用JSON格式传参。
 * @remark 必选字段不能为空，否则返回参数错误。
 * @remark 认证令牌需要先调用登录接口获取。
 * @number 1
 */
COMMENT;
  }

  /**
   * 数据字典模板
   *
   * @return string
   */
  private function getDatabaseTemplate(): string
  {
    return <<<'MARKDOWN'
# 表名：user

**表说明**：用户信息表，存储用户基本信息

#### 字段列表

| 字段名 | 类型 | 长度 | 默认值 | 是否为空 | 说明 |
| ------ | ---- | ---- | ------ | -------- | ---- |
| id | int | 11 | - | 否 | 主键ID，自增 |
| username | varchar | 64 | - | 否 | 用户名，唯一 |
| email | varchar | 128 | NULL | 是 | 邮箱地址 |
| password | varchar | 255 | - | 否 | 密码（加密存储） |
| phone | varchar | 20 | NULL | 是 | 手机号码 |
| avatar | varchar | 255 | NULL | 是 | 头像URL |
| status | tinyint | 1 | 1 | 否 | 状态：0=禁用，1=启用 |
| vip_type | tinyint | 1 | 0 | 否 | VIP类型：0=免费，1=基础，2=高级，3=企业 |
| last_login_time | datetime | - | NULL | 是 | 最后登录时间 |
| created_at | datetime | - | CURRENT_TIMESTAMP | 否 | 创建时间 |
| updated_at | datetime | - | CURRENT_TIMESTAMP | 否 | 更新时间 |
| deleted_at | datetime | - | NULL | 是 | 软删除时间 |

#### 索引

| 索引名 | 字段 | 类型 | 说明 |
| ------ | ---- | ---- | ---- |
| PRIMARY | id | 主键 | 主键索引 |
| uk_username | username | 唯一索引 | 用户名唯一 |
| idx_email | email | 普通索引 | 邮箱查询优化 |
| idx_status | status | 普通索引 | 状态筛选优化 |
| idx_created_at | created_at | 普通索引 | 创建时间查询优化 |

#### 关联关系

| 关联表 | 关联字段 | 关系类型 | 说明 |
| ------ | -------- | -------- | ---- |
| order | user_id | 一对多 | 一个用户可以有多个订单 |
| item | uid | 一对多 | 一个用户可以创建多个项目 |
| team_member | uid | 多对多 | 用户可以属于多个团队 |

#### 建表语句

```sql
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `username` varchar(64) NOT NULL COMMENT '用户名',
  `email` varchar(128) DEFAULT NULL COMMENT '邮箱地址',
  `password` varchar(255) NOT NULL COMMENT '密码',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号码',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像URL',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `vip_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'VIP类型',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '软删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户信息表';
```

#### 备注

- 密码使用 bcrypt 算法加密存储
- 软删除字段 deleted_at 非空时表示已删除
- VIP类型影响用户配额和功能权限
MARKDOWN;
  }

  /**
   * 通用文档模板
   *
   * @return string
   */
  private function getGeneralTemplate(): string
  {
    return <<<'MARKDOWN'
# 文档标题

**简要描述**：一句话概括文档的主要内容

**更新日期**：2024-01-01

---

#### 一、概述

在这里写文档的背景介绍、目标读者、适用范围等内容。

##### 1.1 目标读者

- 开发人员：了解 xxx 的实现细节
- 运维人员：掌握 xxx 的部署配置
- 产品经理：理解 xxx 的功能边界

##### 1.2 前置条件

- 已安装 xxx 环境
- 具有 xxx 权限
- 熟悉 xxx 基础知识

---

#### 二、核心概念

##### 2.1 概念一

概念一的详细说明...

##### 2.2 概念二

概念二的详细说明...

| 术语 | 说明 |
| ---- | ---- |
| 术语1 | 术语1的解释 |
| 术语2 | 术语2的解释 |

---

#### 三、使用指南

##### 3.1 快速开始

```bash
# 示例命令
command --option value
```

##### 3.2 配置说明

| 配置项 | 类型 | 默认值 | 说明 |
| ------ | ---- | ------ | ---- |
| option1 | string | "" | 配置项1说明 |
| option2 | int | 0 | 配置项2说明 |
| enabled | bool | false | 是否启用 |

##### 3.3 代码示例

```javascript
// JavaScript 示例
const config = {
  option1: 'value',
  option2: 100,
  enabled: true
};
```

```php
// PHP 示例
$config = [
  'option1' => 'value',
  'option2' => 100,
  'enabled' => true,
];
```

---

#### 四、常见问题 FAQ

##### Q1: 问题一？

**A:** 答案一的详细说明...

##### Q2: 问题二？

**A:** 答案二的详细说明...

---

#### 五、注意事项

- ⚠️ 注意事项1：详细说明
- ⚠️ 注意事项2：详细说明
- 💡 提示：有用的提示信息

---

#### 六、相关链接

- [相关文档1](链接地址)
- [相关文档2](链接地址)
- [外部资源](链接地址)

---

#### 更新记录

| 日期 | 版本 | 更新内容 | 作者 |
| ---- | ---- | -------- | ---- |
| 2024-01-01 | v1.0 | 初始版本 | xxx |
MARKDOWN;
  }

  /**
   * 创建页面
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function createPage(array $params, bool $skipPermissionCheck = false): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }

    $pageTitle = trim($params['page_title'] ?? '');
    if ($pageTitle === '') {
      McpError::throw(McpError::INVALID_PARAMS, '页面标题不能为空');
    }

    $pageContent = $params['page_content'] ?? '';
    $catName = trim($params['cat_name'] ?? '');
    $sNumber = (int) ($params['s_number'] ?? 99);

    // 检查写入权限（batch_upsert_pages 已在外层检查过时跳过）
    if (!$skipPermissionCheck) {
      $this->requireWritePermission($itemId);
    }

    // 验证内容不能为空（与 PageController::save 一致）
    if (empty($pageContent)) {
      McpError::throw(McpError::INVALID_PARAMS, '不允许保存空内容，请随便写点什么');
    }

    // 检查页面内容大小限制（防止超大内容导致数据库问题）
    $maxContentSize = 50 * 1024 * 1024; // 50MB
    if (strlen($pageContent) > $maxContentSize) {
      $maxMB = round($maxContentSize / 1024 / 1024, 1);
      McpError::throw(McpError::OPERATION_FAILED, "页面内容大小超出限制（{$maxMB}MB），请精简内容或拆分为多个页面");
    }

    // HTML 转义（与 PageController::save 一致）
    $pageContent = htmlspecialchars($pageContent, ENT_QUOTES, 'UTF-8');

    // 获取项目信息
    $item = Item::findById($itemId);
    if (!$item) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '项目不存在');
    }

    $uid = $this->getUid();

    // 获取分表名称
    $tableName = Page::tableForItem($itemId);

    // 检查单项目页面数量上限（防止死循环导致数据库写满）
    $maxPagesPerItem = 50000; // 单项目最多 50000 个页面
    $currentPageCount = DB::table($tableName)
      ->where('item_id', $itemId)
      ->where('is_del', 0)
      ->count();
    if ($currentPageCount >= $maxPagesPerItem) {
      McpError::throw(
        McpError::OPERATION_FAILED,
        "该项目已达到页面数量上限（{$maxPagesPerItem}个），无法继续创建。如有特殊需求，请联系网站管理员"
      );
    }

    // 处理目录（提前计算 cat_id，用于后续唯一性检查）
    $catId = 0;
    if ($catName !== '') {
      $catId = $this->getOrCreateCatalog($itemId, $catName);
    }

    // 检查页面是否已存在（按 item_id + cat_id + page_title 判重，允许不同目录下存在同名页面）
    $existingPage = DB::table($tableName)
      ->where('item_id', $itemId)
      ->where('cat_id', $catId)
      ->where('page_title', $pageTitle)
      ->where('is_del', 0)
      ->first();
    if ($existingPage) {
      McpError::throw(McpError::OPERATION_FAILED, "页面标题已存在: {$pageTitle}");
    }

    try {
      $now = time();
      $data = [
        'page_title' => $pageTitle,
        'page_content' => $pageContent,
        'item_id' => $itemId,
        'cat_id' => $catId,
        's_number' => $sNumber,
        'addtime' => $now,
        'author_uid' => $this->getUid(),
        'author_username' => $this->getUsername(),
      ];

      // 复用 Page::addPage 方法，确保与原后端逻辑一致
      // 包括：在 page 主表插入记录、内容压缩、分表插入等
      $pageId = Page::addPage($itemId, $data);

      if ($pageId <= 0) {
        McpError::throw(McpError::OPERATION_FAILED, '创建页面失败');
      }

      // 更新项目最后更新时间
      DB::table('item')
        ->where('item_id', $itemId)
        ->update(['last_update_time' => $now]);

      // 删除菜单缓存（与 PageController::save 一致）
      Item::deleteCache($itemId);

      // 暂停 800 毫秒，以便应对主从数据库同步延迟（与 PageController::save 一致）
      usleep(800000);

      return [
        'page_id' => $pageId,
        'page_title' => $pageTitle,
        'item_id' => $itemId,
        'cat_id' => $catId,
        'message' => '页面创建成功',
      ];
    } catch (\Throwable $e) {
      McpError::throw(McpError::OPERATION_FAILED, '页面创建失败: ' . $e->getMessage());
    }
  }

  /**
   * 通过代码注释创建 RunApi 格式页面
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function createPageByComment(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }

    $commentContent = trim($params['comment_content'] ?? '');
    if ($commentContent === '') {
      McpError::throw(McpError::INVALID_PARAMS, '注释内容不能为空');
    }

    // 检查写入权限
    $this->requireWritePermission($itemId);

    // 解析注释内容
    $parsedData = $this->parseShowdocComment($commentContent);
    if (empty($parsedData['title'])) {
      McpError::throw(McpError::INVALID_PARAMS, '注释格式错误：缺少 @title 标签');
    }

    $pageTitle = $parsedData['title'];
    $catName = $parsedData['catalog'] ?? '';
    $sNumber = (int) ($parsedData['number'] ?? 99);

    // 处理目录
    $catId = 0;
    if ($catName !== '') {
      $catId = $this->getOrCreateCatalog($itemId, $catName);
    }

    // 获取项目类型
    $item = DB::table('item')
      ->where('item_id', $itemId)
      ->first();
    $itemType = (int) ($item->item_type ?? 1);

    // 构建 RunApi JSON 格式
    $runapiContent = $this->buildRunapiContent($parsedData);

    // 如果是普通文档项目，转换为 Markdown
    if ($itemType !== 3) {
      $convert = new Convert();
      $pageContent = $convert->runapiToMd($runapiContent);
    } else {
      $pageContent = $runapiContent;
    }

    // 获取分表名称
    $tableName = Page::tableForItem($itemId);

    // 检查页面是否已存在（按 item_id + cat_id + page_title 判重，允许不同目录下存在同名页面）
    $existingPage = DB::table($tableName)
      ->where('item_id', $itemId)
      ->where('cat_id', $catId)
      ->where('page_title', $pageTitle)
      ->where('is_del', 0)
      ->first();

    try {
      $now = time();
      $data = [
        'page_title' => $pageTitle,
        'page_content' => $pageContent,
        'item_id' => $itemId,
        'cat_id' => $catId,
        's_number' => $sNumber,
        'addtime' => $now,
        'author_uid' => $this->getUid(),
        'author_username' => $this->getUsername(),
      ];

      if ($existingPage) {
        // 更新已存在的页面，复用 Page::savePage 确保内容压缩
        $pageId = (int) $existingPage->page_id;
        $ret = Page::savePage($pageId, $itemId, $data);
        if (!$ret) {
          McpError::throw(McpError::OPERATION_FAILED, '页面更新失败');
        }
        $message = '页面更新成功';
      } else {
        // 创建新页面，复用 Page::addPage 确保在 page 主表插入记录和内容压缩
        $pageId = Page::addPage($itemId, $data);
        if ($pageId <= 0) {
          McpError::throw(McpError::OPERATION_FAILED, '页面创建失败');
        }
        $message = '页面创建成功';
      }

      // 更新项目最后更新时间
      DB::table('item')
        ->where('item_id', $itemId)
        ->update(['last_update_time' => $now]);

      // 删除菜单缓存
      Item::deleteCache($itemId);

      return [
        'page_id' => $pageId,
        'page_title' => $pageTitle,
        'item_id' => $itemId,
        'cat_id' => $catId,
        'message' => $message,
      ];
    } catch (\Throwable $e) {
      McpError::throw(McpError::OPERATION_FAILED, '页面创建失败: ' . $e->getMessage());
    }
  }

  /**
   * 解析 showdoc 注释
   *
   * @param string $content 注释内容
   * @return array
   */
  private function parseShowdocComment(string $content): array
  {
    $data = [];

    // 检查是否包含 showdoc 标记
    if (strpos($content, 'showdoc') === false) {
      return $data;
    }

    // 解析各个标签
    $patterns = [
      'title' => '/@title\s+(.+)/i',
      'description' => '/@description\s+(.+)/i',
      'method' => '/@method\s+(.+)/i',
      'url' => '/@url\s+(.+)/i',
      'catalog' => '/@catalog\s+(.+)/i',
      'remark' => '/@remark\s+(.+)/i',
      'number' => '/@number\s+(.+)/i',
    ];

    foreach ($patterns as $key => $pattern) {
      if (preg_match($pattern, $content, $matches)) {
        $data[$key] = trim($matches[1]);
      }
    }

    // 解析 @param 标签
    $data['params'] = [];
    if (preg_match_all('/@param\s+(\S+)\s+(\S+)\s+(\S+)\s+(.+)/i', $content, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $data['params'][] = [
          'name' => $match[1],
          'type' => $match[2],
          'required' => $match[3],
          'description' => $match[4],
        ];
      }
    }

    // 解析 @header 标签
    $data['headers'] = [];
    if (preg_match_all('/@header\s+(\S+)\s+(\S+)\s+(\S+)\s+(.+)/i', $content, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $data['headers'][] = [
          'name' => $match[1],
          'type' => $match[2],
          'required' => $match[3],
          'description' => $match[4],
        ];
      }
    }

    // 解析 @return 标签（返回示例）
    if (preg_match('/@return\s+(.+)/i', $content, $matches)) {
      $data['return_example'] = $matches[1];
    }

    // 解析 @return_param 标签
    $data['return_params'] = [];
    if (preg_match_all('/@return_param\s+(\S+)\s+(\S+)\s+(.+)/i', $content, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $data['return_params'][] = [
          'name' => $match[1],
          'type' => $match[2],
          'description' => $match[3],
        ];
      }
    }

    return $data;
  }

  /**
   * 构建 RunApi JSON 内容
   *
   * @param array $parsedData 解析后的数据
   * @return string
   */
  private function buildRunapiContent(array $parsedData): string
  {
    $runapiData = [
      'info' => [
        'url' => $parsedData['url'] ?? '',
        'method' => strtoupper($parsedData['method'] ?? 'GET'),
        'name' => $parsedData['title'] ?? '',
        'description' => $parsedData['description'] ?? '',
        'remark' => $parsedData['remark'] ?? '',
      ],
      'request' => [
        'params' => [],
        'headers' => [],
        'body' => [],
      ],
      'response' => [
        'example' => $parsedData['return_example'] ?? '',
        'params' => [],
      ],
    ];

    // 请求参数
    foreach ($parsedData['params'] ?? [] as $param) {
      $runapiData['request']['params'][] = [
        'name' => $param['name'],
        'type' => $param['type'],
        'required' => $param['required'] === '必选' ? 1 : 0,
        'description' => $param['description'],
      ];
    }

    // 请求头
    foreach ($parsedData['headers'] ?? [] as $header) {
      $runapiData['request']['headers'][] = [
        'name' => $header['name'],
        'type' => $header['type'],
        'required' => $header['required'] === '必选' ? 1 : 0,
        'description' => $header['description'],
      ];
    }

    // 返回参数
    foreach ($parsedData['return_params'] ?? [] as $param) {
      $runapiData['response']['params'][] = [
        'name' => $param['name'],
        'type' => $param['type'],
        'description' => $param['description'],
      ];
    }

    return json_encode($runapiData, JSON_UNESCAPED_UNICODE);
  }

  /**
   * 更新页面
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function updatePage(array $params, bool $skipPermissionCheck = false): array
  {
    $pageId = (int) ($params['page_id'] ?? 0);
    if ($pageId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '页面ID不能为空');
    }

    // 开源版：使用单一 page 表
    $page = DB::table('page')
      ->where('page_id', $pageId)
      ->where('is_del', 0)
      ->first();

    if (!$page) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
    }

    $itemId = (int) $page->item_id;

    // 检查写入权限（batch_upsert_pages 已在外层检查过时跳过）
    if (!$skipPermissionCheck) {
      $this->requireWritePermission($itemId);
    }

    // 准备更新数据
    $updateData = [];
    $pageTitle = trim($params['page_title'] ?? '');
    $pageContent = $params['page_content'] ?? null;

    if ($pageContent !== null) {
      // 验证内容不能为空（与 PageController::save 一致）
      if (empty($pageContent)) {
        McpError::throw(McpError::INVALID_PARAMS, '不允许保存空内容，请随便写点什么');
      }

      // HTML 转义（与 PageController::save 一致）
      $pageContent = htmlspecialchars($pageContent, ENT_QUOTES, 'UTF-8');
      $updateData['page_content'] = $pageContent;
    }

    if ($pageTitle !== '') {
      // 检查标题是否与其他页面重复（按 item_id + cat_id + page_title 判重，允许不同目录下存在同名页面）
      $existingPage = DB::table('page')
        ->where('item_id', $itemId)
        ->where('cat_id', $page->cat_id)
        ->where('page_title', $pageTitle)
        ->where('page_id', '<>', $pageId)
        ->where('is_del', 0)
        ->first();
      if ($existingPage) {
        McpError::throw(McpError::OPERATION_FAILED, "页面标题已存在: {$pageTitle}");
      }
      $updateData['page_title'] = $pageTitle;
    }

    if (empty($updateData)) {
      McpError::throw(McpError::INVALID_PARAMS, '没有需要更新的内容');
    }

    // 获取项目信息
    $item = Item::findById($itemId);
    if (!$item) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '项目不存在');
    }

    $uid = $this->getUid();

    // 乐观锁检查
    $expectedHash = $params['expected_hash'] ?? null;
    if ($expectedHash !== null) {
      $currentHash = substr(md5($page->page_content ?? ''), 0, 12);
      if ($expectedHash !== $currentHash) {
        McpError::throw(
          McpError::VERSION_CONFLICT,
          '版本冲突：文档已被其他人修改',
          [
            'error_type' => 'version_conflict',
            'your_hash' => $expectedHash,
            'current_hash' => $currentHash,
            'suggestion' => '请重新获取最新内容，合并您的修改后重新提交',
          ]
        );
      }
    }

    try {
      $now = time();
      $updateData['addtime'] = $now;
      $updateData['author_uid'] = $this->getUid();
      $updateData['author_username'] = $this->getUsername();

      // 保存历史版本（在更新之前保存当前页面内容）
      $historyData = [
        'page_id'         => $pageId,
        'item_id'         => $itemId,
        'cat_id'          => $page->cat_id ?? 0,
        'page_title'      => $page->page_title ?? '',
        'page_comments'   => $page->page_comments ?? '',
        'page_content'    => $page->page_content ?? '',
        's_number'        => $page->s_number ?? 0,
        'addtime'         => $page->addtime ?? $now,
        'author_uid'      => $page->author_uid ?? 0,
        'author_username' => $page->author_username ?? '',
        'ext_info'        => $page->ext_info ?? '',
      ];
      PageHistory::add($pageId, $historyData);

      // 复用 Page::savePage 方法，确保内容压缩等逻辑与原后端一致
      $ret = Page::savePage($pageId, $itemId, $updateData);
      if (!$ret) {
        McpError::throw(McpError::OPERATION_FAILED, '保存失败');
      }

      // 更新项目最后更新时间
      DB::table('item')
        ->where('item_id', $itemId)
        ->update(['last_update_time' => $now]);

      // 清理旧的历史版本（保留最近 20 个版本）
      $keepCount = 20;
      $historyCount = PageHistory::getCount($pageId);
      if ($historyCount > $keepCount) {
        PageHistory::deleteOldVersions($pageId, $keepCount);
      }

      // 删除缓存（与 PageController::save 一致）
      Page::deleteCache($pageId);
      Item::deleteCache($itemId);

      // 计算新的内容哈希
      $newContent = $updateData['page_content'] ?? $page->page_content;
      $newHash = substr(md5($newContent), 0, 12);

      return [
        'page_id' => $pageId,
        'content_hash' => $newHash,
        'message' => '页面更新成功',
      ];
    } catch (\Throwable $e) {
      McpError::throw(McpError::OPERATION_FAILED, '页面更新失败: ' . $e->getMessage());
    }
  }

  /**
   * 按标题智能匹配：存在则更新，不存在则创建
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function upsertPage(array $params, bool $skipPermissionCheck = false): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }

    $pageTitle = trim($params['page_title'] ?? '');
    if ($pageTitle === '') {
      McpError::throw(McpError::INVALID_PARAMS, '页面标题不能为空');
    }

    $pageContent = $params['page_content'] ?? '';
    $catName = trim($params['cat_name'] ?? '');
    $sNumber = (int) ($params['s_number'] ?? 99);

    // 检查写入权限（batch_upsert_pages 已在外层检查过时跳过）
    if (!$skipPermissionCheck) {
      $this->requireWritePermission($itemId);
    }

    // 获取分表名称
    $tableName = Page::tableForItem($itemId);

    // 处理目录（提前计算 cat_id，用于后续唯一性检查）
    $catId = 0;
    if ($catName !== '') {
      $catId = $this->getOrCreateCatalog($itemId, $catName);
    }

    // 检查页面是否已存在（按 item_id + cat_id + page_title 判重，允许不同目录下存在同名页面）
    $existingPage = DB::table($tableName)
      ->where('item_id', $itemId)
      ->where('cat_id', $catId)
      ->where('page_title', $pageTitle)
      ->where('is_del', 0)
      ->first();

    if ($existingPage) {
      // 更新已存在的页面（page_id 由内部通过 item_id+title 查得，不存在越权风险）
      return $this->updatePage([
        'page_id' => $existingPage->page_id,
        'page_content' => $pageContent,
      ], true);
    }

    // 创建新页面（item_id 来自外层已验证的参数，不存在越权风险）
    return $this->createPage($params, true);
  }

  /**
   * 批量创建/更新页面（最多50个）
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function batchUpsertPages(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }

    $pages = $params['pages'] ?? [];
    if (!is_array($pages) || empty($pages)) {
      McpError::throw(McpError::INVALID_PARAMS, 'pages 必须是非空数组');
    }

    // 限制最多50个
    $pages = array_slice($pages, 0, 50);

    $result = [
      'success_count' => 0,
      'failed_count' => 0,
      'results' => [],
    ];

    foreach ($pages as $pageData) {
      try {
        $pageResult = $this->upsertPage([
          'item_id' => $itemId,
          'page_title' => $pageData['page_title'] ?? '',
          'page_content' => $pageData['page_content'] ?? '',
          'cat_name' => $pageData['cat_name'] ?? '',
          's_number' => $pageData['s_number'] ?? 99,
        ]);

        $result['success_count']++;
        $result['results'][] = [
          'page_title' => $pageData['page_title'] ?? '',
          'status' => 'success',
          'page_id' => $pageResult['page_id'],
        ];
      } catch (McpException $e) {
        $result['failed_count']++;
        $result['results'][] = [
          'page_title' => $pageData['page_title'] ?? '',
          'status' => 'failed',
          'error' => $e->getMessage(),
        ];
      }
    }

    return $result;
  }

  /**
   * 删除页面（软删除）
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function deletePage(array $params): array
  {
    $pageId = (int) ($params['page_id'] ?? 0);
    if ($pageId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '页面ID不能为空');
    }

    // 开源版：使用单一 page 表，不需要分表查找
    $page = DB::table('page')
      ->where('page_id', $pageId)
      ->where('is_del', 0)
      ->first();

    if (!$page) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
    }

    $itemId = (int) $page->item_id;

    // 检查写入权限
    $this->requireWritePermission($itemId);

    try {
      // 软删除页面
      DB::table('page')
        ->where('page_id', $pageId)
        ->update(['is_del' => 1]);

      // 更新项目最后更新时间
      DB::table('item')
        ->where('item_id', $itemId)
        ->update(['last_update_time' => time()]);

      // 删除缓存
      Page::deleteCache($pageId);
      Item::deleteCache($itemId);

      return [
        'page_id' => $pageId,
        'message' => '页面已删除',
      ];
    } catch (\Throwable $e) {
      McpError::throw(McpError::OPERATION_FAILED, '页面删除失败: ' . $e->getMessage());
    }
  }

  /**
   * 获取或创建目录
   *
   * @param int $itemId 项目ID
   * @param string $catName 目录名称（支持多级，用/分隔）
   * @return int 目录ID
   */
  private function getOrCreateCatalog(int $itemId, string $catName): int
  {
    $catNames = array_map('trim', explode('/', $catName));
    $parentCatId = 0;
    $catId = 0;

    for ($i = 0; $i < count($catNames); $i++) {
      $name = $catNames[$i];
      if ($name === '') {
        continue;
      }

      $level = $i + 2;

      // 查找目录
      $catalog = DB::table('catalog')
        ->where('item_id', $itemId)
        ->where('cat_name', $name)
        ->where('parent_cat_id', $parentCatId)
        ->where('level', $level)
        ->first();

      if ($catalog) {
        $catId = (int) $catalog->cat_id;
      } else {
        // 创建目录
        $catId = DB::table('catalog')->insertGetId([
          'item_id' => $itemId,
          'cat_name' => $name,
          'parent_cat_id' => $parentCatId,
          's_number' => 99,
          'addtime' => time(),
          'level' => $level,
        ]);
      }

      $parentCatId = $catId;
    }

    return $catId;
  }

  /**
   * 获取当前用户名
   *
   * @return string
   */
  private function getUsername(): string
  {
    $uid = $this->getUid();
    if ($uid <= 0) {
      return '';
    }

    $user = \App\Model\User::findById($uid);
    return $user ? ($user->username ?? '') : '';
  }

  /**
   * 获取页面修改历史列表
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function getPageHistory(array $params): array
  {
    $pageId = (int) ($params['page_id'] ?? 0);
    if ($pageId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '页面ID不能为空');
    }

    // 开源版：使用单一 page 表
    $page = DB::table('page')
      ->where('page_id', $pageId)
      ->where('is_del', 0)
      ->first();

    if (!$page) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
    }

    $itemId = (int) $page->item_id;

    // 检查读取权限
    $this->requireReadPermission($itemId);

    // 获取历史版本列表
    $limit = min(500, max(1, (int) ($params['limit'] ?? 20)));
    $historyList = PageHistory::getList($pageId, $limit);

    $history = [];
    foreach ($historyList as $row) {
      $history[] = [
        'version_id' => (int) $row['page_history_id'],
        'author' => $row['author_username'] ?? '',
        'author_uid' => (int) ($row['author_uid'] ?? 0),
        'updated_at' => $row['addtime'] ?? '',
        'change_summary' => $row['page_comments'] ?? '',
      ];
    }

    return [
      'page_id' => $pageId,
      'history' => $history,
      'total' => count($history),
    ];
  }

  /**
   * 获取指定版本的页面内容
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function getPageVersion(array $params): array
  {
    $pageId = (int) ($params['page_id'] ?? 0);
    $versionId = (int) ($params['version_id'] ?? 0);

    if ($pageId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '页面ID不能为空');
    }
    if ($versionId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '版本ID不能为空');
    }

    // 开源版：使用单一 page 表
    $page = DB::table('page')
      ->where('page_id', $pageId)
      ->where('is_del', 0)
      ->first();

    if (!$page) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
    }

    $itemId = (int) $page->item_id;

    // 检查读取权限
    $this->requireReadPermission($itemId);

    // 获取历史版本
    $historyPage = PageHistory::findById($pageId, $versionId);
    if (!$historyPage) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '历史版本不存在');
    }

    // 获取项目类型
    $item = DB::table('item')
      ->where('item_id', $itemId)
      ->first();
    $itemType = (int) ($item->item_type ?? 1);

    // 处理内容
    $content = $historyPage['page_content'] ?? '';
    $pageType = 'markdown';

    // 如果是 RunApi 项目，转换为 Markdown
    if ($itemType === 3) {
      $pageType = 'runapi';
      $convert = new Convert();
      $content = $convert->runapiToMd($content);
    }

    // 对内容进行 HTML 解转义（MCP 场景下 AI 需要原始内容）
    $content = htmlspecialchars_decode($content, ENT_QUOTES);

    return [
      'page_id' => $pageId,
      'version_id' => $versionId,
      'page_title' => $historyPage['page_title'] ?? '',
      'item_id' => $itemId,
      'type' => $pageType,
      'content' => $content,
      'author' => $historyPage['author_username'] ?? '',
      'author_uid' => (int) ($historyPage['author_uid'] ?? 0),
      'updated_at' => $historyPage['addtime'] ?? '',
      'change_summary' => $historyPage['page_comments'] ?? '',
    ];
  }

  /**
   * 对比两个版本的差异
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function diffPageVersions(array $params): array
  {
    $pageId = (int) ($params['page_id'] ?? 0);
    $versionId1 = (int) ($params['version_id_1'] ?? 0);
    $versionId2 = (int) ($params['version_id_2'] ?? 0);

    if ($pageId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '页面ID不能为空');
    }
    if ($versionId1 <= 0 || $versionId2 <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '版本ID不能为空');
    }

    // 开源版：使用单一 page 表
    $page = DB::table('page')
      ->where('page_id', $pageId)
      ->where('is_del', 0)
      ->first();

    if (!$page) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
    }

    $itemId = (int) $page->item_id;

    // 检查读取权限
    $this->requireReadPermission($itemId);

    // 获取两个历史版本
    $version1 = PageHistory::findById($pageId, $versionId1);
    $version2 = PageHistory::findById($pageId, $versionId2);

    if (!$version1) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '版本1不存在');
    }
    if (!$version2) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '版本2不存在');
    }

    // 计算差异
    $diff = $this->calculateDiff(
      $version1['page_content'] ?? '',
      $version2['page_content'] ?? ''
    );

    return [
      'page_id' => $pageId,
      'version_1' => [
        'version_id' => $versionId1,
        'author' => $version1['author_username'] ?? '',
        'updated_at' => $version1['addtime'] ?? '',
      ],
      'version_2' => [
        'version_id' => $versionId2,
        'author' => $version2['author_username'] ?? '',
        'updated_at' => $version2['addtime'] ?? '',
      ],
      'diff' => $diff,
    ];
  }

  /**
   * 计算两个文本的差异
   *
   * @param string $old 旧文本
   * @param string $new 新文本
   * @return array 差异结果
   */
  private function calculateDiff(string $old, string $new): array
  {
    // 简单的行级差异计算
    $oldLines = explode("\n", $old);
    $newLines = explode("\n", $new);

    $diff = [];
    $maxLines = max(count($oldLines), count($newLines));

    for ($i = 0; $i < $maxLines; $i++) {
      $oldLine = $oldLines[$i] ?? '';
      $newLine = $newLines[$i] ?? '';

      if ($oldLine === $newLine) {
        // 相同行
        $diff[] = [
          'type' => 'unchanged',
          'line' => $i + 1,
          'content' => $oldLine,
        ];
      } else {
        // 不同行
        if ($oldLine !== '' && !isset($oldLines[$i])) {
          // 新增行
          $diff[] = [
            'type' => 'added',
            'line' => $i + 1,
            'content' => $newLine,
          ];
        } elseif ($newLine !== '' && !isset($newLines[$i])) {
          // 删除行
          $diff[] = [
            'type' => 'removed',
            'line' => $i + 1,
            'content' => $oldLine,
          ];
        } else {
          // 修改行
          if ($oldLine !== '') {
            $diff[] = [
              'type' => 'removed',
              'line' => $i + 1,
              'content' => $oldLine,
            ];
          }
          if ($newLine !== '') {
            $diff[] = [
              'type' => 'added',
              'line' => $i + 1,
              'content' => $newLine,
            ];
          }
        }
      }
    }

    // 只返回有变化的行
    $changes = array_filter($diff, function ($item) {
      return $item['type'] !== 'unchanged';
    });

    return [
      'changes' => array_values($changes),
      'summary' => [
        'added' => count(array_filter($changes, fn($item) => $item['type'] === 'added')),
        'removed' => count(array_filter($changes, fn($item) => $item['type'] === 'removed')),
      ],
    ];
  }

  /**
   * 恢复页面到指定历史版本
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function restorePageVersion(array $params): array
  {
    $pageId = (int) ($params['page_id'] ?? 0);
    $versionId = (int) ($params['version_id'] ?? 0);

    if ($pageId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '页面ID不能为空');
    }
    if ($versionId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '版本ID不能为空');
    }

    // 开源版：使用单一 page 表
    $page = DB::table('page')
      ->where('page_id', $pageId)
      ->where('is_del', 0)
      ->first();

    if (!$page) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
    }

    $itemId = (int) $page->item_id;

    // 检查写入权限
    $this->requireWritePermission($itemId);

    // 获取历史版本
    $historyPage = PageHistory::findById($pageId, $versionId);
    if (!$historyPage) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '历史版本不存在');
    }

    try {
      $now = time();
      $uid = $this->getUid();
      $username = $this->getUsername();

      // 先保存当前版本到历史记录
      $currentData = [
        'page_id' => $pageId,
        'item_id' => $itemId,
        'cat_id' => (int) ($page->cat_id ?? 0),
        'page_title' => $page->page_title,
        'page_content' => $page->page_content,
        's_number' => (int) ($page->s_number ?? 0),
        'addtime' => $now,
        'author_uid' => (int) ($page->author_uid ?? 0),
        'author_username' => $page->author_username ?? '',
        'page_comments' => '恢复前自动备份',
      ];
      PageHistory::add($pageId, $currentData);

      // 更新页面内容为历史版本
      $updateData = [
        'page_content' => $historyPage['page_content'] ?? '',
        'page_title' => $historyPage['page_title'] ?? $page->page_title,
        'addtime' => $now,
        'author_uid' => $uid,
        'author_username' => $username,
      ];

      // 复用 Page::savePage 方法，确保内容压缩等逻辑与原后端一致
      $ret = Page::savePage($pageId, $itemId, $updateData);
      if (!$ret) {
        McpError::throw(McpError::OPERATION_FAILED, '恢复版本失败');
      }

      // 更新项目最后更新时间
      DB::table('item')
        ->where('item_id', $itemId)
        ->update(['last_update_time' => $now]);

      // 删除缓存
      Page::deleteCache($pageId);
      Item::deleteCache($itemId);

      return [
        'page_id' => $pageId,
        'version_id' => $versionId,
        'item_id' => $itemId,
        'page_title' => $updateData['page_title'],
        'restored_at' => date('Y-m-d H:i:s', $now),
        'message' => '页面已恢复到指定版本，当前版本已自动备份',
      ];
    } catch (\Throwable $e) {
      McpError::throw(McpError::OPERATION_FAILED, '恢复版本失败: ' . $e->getMessage());
    }
  }
}
