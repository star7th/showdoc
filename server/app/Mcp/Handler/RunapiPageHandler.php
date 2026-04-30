<?php

namespace App\Mcp\Handler;

use App\Mcp\McpHandler;
use App\Mcp\McpError;
use App\Mcp\McpException;
use App\Model\Page;
use App\Model\PageHistory;
use App\Model\Catalog;
use App\Model\Item;
use App\Common\Helper\ContentCodec;
use Illuminate\Database\Capsule\Manager as DB;

class RunapiPageHandler extends McpHandler
{
  public function getSupportedOperations(): array
  {
    return [
      'get_runapi_page',
      'create_runapi_page',
      'update_runapi_page',
      'upsert_runapi_page',
    ];
  }

  public function execute(string $operation, array $params = [])
  {
    switch ($operation) {
      case 'get_runapi_page':
        return $this->getRunapiPage($params);

      case 'create_runapi_page':
        return $this->createRunapiPage($params);

      case 'update_runapi_page':
        return $this->updateRunapiPage($params);

      case 'upsert_runapi_page':
        return $this->upsertRunapiPage($params);

      default:
        McpError::throw(McpError::METHOD_NOT_FOUND, "操作不存在: {$operation}");
    }
  }

  private function getRunapiPage(array $params): array
  {
    $pageId = (int) ($params['page_id'] ?? 0);
    $itemId = (int) ($params['item_id'] ?? 0);
    $pageTitle = trim($params['page_title'] ?? '');

    if ($pageId <= 0 && ($itemId <= 0 || $pageTitle === '')) {
      McpError::throw(McpError::INVALID_PARAMS, '请提供 page_id 或 item_id + page_title');
    }

    if ($pageId <= 0 && $itemId > 0 && $pageTitle !== '') {
      $tableName = Page::tableForItem($itemId);
      $pageRow = DB::table($tableName)
        ->where('item_id', $itemId)
        ->where('page_title', $pageTitle)
        ->where('is_del', 0)
        ->first();
      if (!$pageRow) {
        McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
      }
      $pageId = (int) $pageRow->page_id;
    }

    $shard = $this->findPageInShard($pageId);
    $pageObj = $shard['page'];
    $itemId = $shard['itemId'];

    $item = Item::findById($itemId);
    if (!$item || (int) $item->item_type !== 3) {
      McpError::throw(McpError::OPERATION_FAILED, '该页面不属于RunApi项目（item_type不为3）');
    }

    $this->requireReadPermission($itemId);

    $page = (array) $pageObj;
    if ((int) ($page['is_del'] ?? 0) === 1) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
    }

    if (($page['is_draft'] ?? 0) == 1) {
      $authorUid = (int) ($page['author_uid'] ?? 0);
      $currentUid = (int) ($this->user['uid'] ?? 0);
      if ($currentUid <= 0 || $currentUid !== $authorUid) {
        McpError::throw(McpError::RESOURCE_NOT_FOUND, '该页面是草稿状态，暂不可访问');
      }
    }

    $content = $page['page_content'] ?? '';
    $pageContent = json_decode($content, true);
    if ($pageContent === null && $content !== '') {
      $pageContent = ['raw' => $content];
    }

    $contentHash = substr(md5($page['page_content'] ?? ''), 0, 12);

    $catId = (int) ($page['cat_id'] ?? 0);
    $catName = '';
    if ($catId > 0) {
      $catalog = DB::table('catalog')
        ->where('cat_id', $catId)
        ->first();
      if ($catalog) {
        $catName = $catalog->cat_name ?? '';
      }
    }

    $addtime = date('Y-m-d H:i:s', (int) ($page['addtime'] ?? time()));

    return [
      'page_id' => (int) $page['page_id'],
      'page_title' => $page['page_title'],
      'item_id' => $itemId,
      'cat_id' => $catId,
      'cat_name' => $catName,
      'type' => 'runapi',
      'page_content' => $pageContent,
      'content_hash' => $contentHash,
      's_number' => (int) ($page['s_number'] ?? 0),
      'addtime' => $addtime,
      'author_uid' => (int) ($page['author_uid'] ?? 0),
      'author_username' => $page['author_username'] ?? '',
    ];
  }

  private function createRunapiPage(array $params, bool $skipPermissionCheck = false): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }

    $pageTitle = trim($params['page_title'] ?? '');
    if ($pageTitle === '') {
      McpError::throw(McpError::INVALID_PARAMS, '接口名称不能为空');
    }

    $pageContent = $params['page_content'] ?? null;
    if ($pageContent === null) {
      McpError::throw(McpError::INVALID_PARAMS, 'page_content不能为空');
    }

    $this->validateRunapiContent($pageContent);

    $catName = trim($params['cat_name'] ?? '');
    $sNumber = (int) ($params['s_number'] ?? 99);

    if (!$skipPermissionCheck) {
      $this->requireWritePermission($itemId);
    }

    $item = Item::findById($itemId);
    if (!$item) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '项目不存在');
    }
    if ((int) $item->item_type !== 3) {
      McpError::throw(McpError::OPERATION_FAILED, '该操作仅适用于RunApi项目（item_type必须为3）');
    }

    $contentString = json_encode($pageContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (empty($contentString)) {
      McpError::throw(McpError::INVALID_PARAMS, '不允许保存空内容，请随便写点什么');
    }

    $maxContentSize = 10 * 1024 * 1024;
    if (strlen($contentString) > $maxContentSize) {
      $maxMB = round($maxContentSize / 1024 / 1024, 1);
      McpError::throw(McpError::OPERATION_FAILED, "页面内容大小超出限制（{$maxMB}MB），请精简内容或拆分为多个页面");
    }

    $tableName = Page::tableForItem($itemId);

    $maxPagesPerItem = 10000;
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

    $catId = 0;
    if ($catName !== '') {
      $catId = $this->getOrCreateCatalog($itemId, $catName);
    }

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
        'page_content' => $contentString,
        'item_id' => $itemId,
        'cat_id' => $catId,
        's_number' => $sNumber,
        'addtime' => $now,
        'author_uid' => $this->getUid(),
        'author_username' => $this->getUsername(),
      ];

      $pageId = Page::addPage($itemId, $data);

      if ($pageId <= 0) {
        McpError::throw(McpError::OPERATION_FAILED, '创建页面失败');
      }

      DB::table('item')
        ->where('item_id', $itemId)
        ->update(['last_update_time' => $now]);

      Item::deleteCache($itemId);

      usleep(800000);

      return [
        'page_id' => $pageId,
        'page_title' => $pageTitle,
        'item_id' => $itemId,
        'cat_id' => $catId,
        'message' => 'RunApi页面创建成功',
      ];
    } catch (McpException $e) {
      throw $e;
    } catch (\Throwable $e) {
      McpError::throw(McpError::OPERATION_FAILED, '页面创建失败: ' . $e->getMessage());
    }
  }

  private function updateRunapiPage(array $params, bool $skipPermissionCheck = false): array
  {
    $pageId = (int) ($params['page_id'] ?? 0);
    if ($pageId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '页面ID不能为空');
    }

    $shard = $this->findPageInShard($pageId);
    $page = $shard['page'];
    $itemId = $shard['itemId'];

    $item = Item::findById($itemId);
    if (!$item || (int) $item->item_type !== 3) {
      McpError::throw(McpError::OPERATION_FAILED, '该页面不属于RunApi项目（item_type不为3）');
    }

    if (!$skipPermissionCheck) {
      $this->requireWritePermission($itemId);
    }

    $updateData = [];
    $pageTitle = trim($params['page_title'] ?? '');
    $pageContent = $params['page_content'] ?? null;

    if ($pageContent !== null) {
      $this->validateRunapiContent($pageContent);

      $contentString = json_encode($pageContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

      if (empty($contentString)) {
        McpError::throw(McpError::INVALID_PARAMS, '不允许保存空内容，请随便写点什么');
      }

      $maxContentSize = 10 * 1024 * 1024;
      if (strlen($contentString) > $maxContentSize) {
        $maxMB = round($maxContentSize / 1024 / 1024, 1);
        McpError::throw(McpError::OPERATION_FAILED, "页面内容大小超出限制（{$maxMB}MB），请精简内容或拆分为多个页面");
      }

      $updateData['page_content'] = $contentString;
    }

    if ($pageTitle !== '') {
      $tableName = $shard['tableName'];
      $existingPage = DB::table($tableName)
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

    if (empty($updateData) && ($params['expected_hash'] ?? null) === null) {
      McpError::throw(McpError::INVALID_PARAMS, '没有需要更新的内容');
    }

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

      $updateData['addtime'] = $now;
      $updateData['author_uid'] = $this->getUid();
      $updateData['author_username'] = $this->getUsername();

      $ret = Page::savePage($pageId, $itemId, $updateData);
      if (!$ret) {
        McpError::throw(McpError::OPERATION_FAILED, '保存失败');
      }

      DB::table('item')
        ->where('item_id', $itemId)
        ->update(['last_update_time' => $now]);

      $keepCount = 20;
      $historyCount = PageHistory::getCount($pageId);
      if ($historyCount > $keepCount) {
        PageHistory::deleteOldVersions($pageId, $keepCount);
      }

      Page::deleteCache($pageId);
      Item::deleteCache($itemId);

      $newContent = $updateData['page_content'] ?? $page->page_content;
      $newHash = substr(md5($newContent), 0, 12);

      return [
        'page_id' => $pageId,
        'content_hash' => $newHash,
        'message' => 'RunApi页面更新成功',
      ];
    } catch (McpException $e) {
      throw $e;
    } catch (\Throwable $e) {
      McpError::throw(McpError::OPERATION_FAILED, '页面更新失败: ' . $e->getMessage());
    }
  }

  private function upsertRunapiPage(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }

    $pageTitle = trim($params['page_title'] ?? '');
    if ($pageTitle === '') {
      McpError::throw(McpError::INVALID_PARAMS, '接口名称不能为空');
    }

    $pageContent = $params['page_content'] ?? null;
    if ($pageContent === null) {
      McpError::throw(McpError::INVALID_PARAMS, 'page_content不能为空');
    }

    $this->validateRunapiContent($pageContent);

    $this->requireWritePermission($itemId);

    $item = Item::findById($itemId);
    if (!$item) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '项目不存在');
    }
    if ((int) $item->item_type !== 3) {
      McpError::throw(McpError::OPERATION_FAILED, '该操作仅适用于RunApi项目（item_type必须为3）');
    }

    $tableName = Page::tableForItem($itemId);

    $catName = trim($params['cat_name'] ?? '');
    $catId = 0;
    if ($catName !== '') {
      $catId = $this->getOrCreateCatalog($itemId, $catName);
    }

    $existingPage = DB::table($tableName)
      ->where('item_id', $itemId)
      ->where('cat_id', $catId)
      ->where('page_title', $pageTitle)
      ->where('is_del', 0)
      ->first();

    if ($existingPage) {
      return $this->updateRunapiPage([
        'page_id' => $existingPage->page_id,
        'page_content' => $pageContent,
      ], true);
    }

    return $this->createRunapiPage($params, true);
  }

  private function validateRunapiContent($content): void
  {
    if (!is_array($content)) {
      McpError::throw(McpError::INVALID_PARAMS, 'page_content必须是JSON对象');
    }

    $depth = $this->getJsonDepth($content);
    if ($depth > 10) {
      McpError::throw(McpError::INVALID_PARAMS, 'page_content JSON层级不能超过10层');
    }

    if (empty($content['info']['url'] ?? '')) {
      McpError::throw(McpError::INVALID_PARAMS, 'page_content缺少必填字段 info.url');
    }

    if (empty($content['info']['method'] ?? '')) {
      McpError::throw(McpError::INVALID_PARAMS, 'page_content缺少必填字段 info.method');
    }

    $allowedMethods = ['get', 'post', 'put', 'delete', 'patch', 'options', 'head'];
    $method = strtolower($content['info']['method'] ?? '');
    if (!in_array($method, $allowedMethods, true)) {
      McpError::throw(McpError::INVALID_PARAMS, 'info.method 无效，允许值: ' . implode(', ', $allowedMethods));
    }
  }

  private function getJsonDepth($data): int
  {
    if (!is_array($data)) {
      return 0;
    }
    $maxDepth = 1;
    foreach ($data as $value) {
      if (is_array($value)) {
        $depth = 1 + $this->getJsonDepth($value);
        if ($depth > $maxDepth) {
          $maxDepth = $depth;
        }
      }
    }
    return $maxDepth;
  }

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

      $catalog = DB::table('catalog')
        ->where('item_id', $itemId)
        ->where('cat_name', $name)
        ->where('parent_cat_id', $parentCatId)
        ->where('level', $level)
        ->first();

      if ($catalog) {
        $catId = (int) $catalog->cat_id;
      } else {
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

  private function getUsername(): string
  {
    $uid = $this->getUid();
    if ($uid <= 0) {
      return '';
    }

    $user = \App\Model\User::findById($uid);
    return $user ? ($user->username ?? '') : '';
  }

  private function findPageInShard(int $pageId): array
  {
    if ($pageId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '页面ID不能为空');
    }

    $pageRow = DB::table('page')
      ->where('page_id', $pageId)
      ->where('is_del', 0)
      ->first();

    if (!$pageRow) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
    }

    $itemId = (int) $pageRow->item_id;
    $tableName = Page::tableForItem($itemId);

    $page = DB::table($tableName)
      ->where('page_id', $pageId)
      ->where('is_del', 0)
      ->first();

    if (!$page) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
    }

    $pageArr = (array) $page;
    if (!empty($pageArr['page_content'])) {
      $decoded = ContentCodec::decompress($pageArr['page_content']);
      if ($decoded !== '' && $decoded !== $pageArr['page_content']) {
        $pageArr['page_content'] = $decoded;
        $page = (object) $pageArr;
      }
    }

    return [
      'page' => $page,
      'itemId' => $itemId,
      'tableName' => $tableName,
    ];
  }
}
