<?php

namespace App\Mcp\Handler;

use App\Mcp\McpHandler;
use App\Mcp\McpError;
use App\Mcp\McpException;
use App\Model\Catalog;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * MCP 目录操作 Handler
 */
class CatalogHandler extends McpHandler
{
  /**
   * 获取支持的操列列表
   *
   * @return array
   */
  public function getSupportedOperations(): array
  {
    return ['list_catalogs', 'get_catalog', 'create_catalog', 'update_catalog', 'delete_catalog'];
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
      case 'list_catalogs':
        return $this->listCatalogs($params);

      case 'get_catalog':
        return $this->getCatalog($params);

      case 'create_catalog':
        return $this->createCatalog($params);

      case 'update_catalog':
        return $this->updateCatalog($params);

      case 'delete_catalog':
        return $this->deleteCatalog($params);

      default:
        McpError::throw(McpError::METHOD_NOT_FOUND, "操作不存在: {$operation}");
    }
  }

  /**
   * 获取项目的目录树
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function listCatalogs(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }

    // 检查读取权限
    $this->requireReadPermission($itemId);

    // 获取目录列表
    $catalogs = DB::table('catalog')
      ->where('item_id', $itemId)
      ->orderBy('s_number', 'asc')
      ->orderBy('cat_id', 'asc')
      ->get()
      ->all();

    // 构建树形结构
    $catalogTree = $this->buildCatalogTree($catalogs, 0);

    return [
      'item_id' => $itemId,
      'catalogs' => $catalogTree,
    ];
  }

  /**
   * 构建目录树
   *
   * @param array $catalogs 目录列表
   * @param int $parentId 父目录ID
   * @return array
   */
  private function buildCatalogTree(array $catalogs, int $parentId): array
  {
    $tree = [];
    foreach ($catalogs as $catalog) {
      $catalog = (array) $catalog;
      if ((int) $catalog['parent_cat_id'] === $parentId) {
        $children = $this->buildCatalogTree($catalogs, (int) $catalog['cat_id']);
        $node = [
          'cat_id' => (int) $catalog['cat_id'],
          'cat_name' => $catalog['cat_name'],
          's_number' => (int) ($catalog['s_number'] ?? 0),
          'addtime' => $catalog['addtime'] ?? '',
        ];
        if (!empty($children)) {
          $node['children'] = $children;
        }
        $tree[] = $node;
      }
    }
    return $tree;
  }

  /**
   * 获取目录详情
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function getCatalog(array $params): array
  {
    $catId = (int) ($params['cat_id'] ?? 0);
    if ($catId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '目录ID不能为空');
    }

    // 获取目录信息
    $catalog = DB::table('catalog')
      ->where('cat_id', $catId)
      ->first();

    if (!$catalog) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '目录不存在');
    }

    $catalog = (array) $catalog;
    $itemId = (int) $catalog['item_id'];

    // 检查读取权限
    $this->requireReadPermission($itemId);

    // 获取分表名称
    $tableName = \App\Model\Page::tableForItem($itemId);

    // 获取目录下的页面数量
    $pageCount = DB::table($tableName)
      ->where('item_id', $itemId)
      ->where('cat_id', $catId)
      ->where('is_del', 0)
      ->count();

    return [
      'cat_id' => (int) $catalog['cat_id'],
      'cat_name' => $catalog['cat_name'],
      'item_id' => $itemId,
      'parent_cat_id' => (int) ($catalog['parent_cat_id'] ?? 0),
      's_number' => (int) ($catalog['s_number'] ?? 0),
      'addtime' => $catalog['addtime'] ?? '',
      'page_count' => $pageCount,
    ];
  }

  /**
   * 创建目录
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function createCatalog(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }

    $catName = trim($params['cat_name'] ?? '');
    if ($catName === '') {
      McpError::throw(McpError::INVALID_PARAMS, '目录名称不能为空');
    }

    // 检查写入权限
    $this->requireWritePermission($itemId);

    // 检查单项目目录数量上限（与 CatalogController::save 一致，防止死循环导致数据库写满）
    $maxCatalogsPerItem = 7500; // 单项目最多 7500 个目录
    $currentCatalogCount = Catalog::getCount($itemId);
    if ($currentCatalogCount >= $maxCatalogsPerItem) {
      McpError::throw(
        McpError::OPERATION_FAILED,
        "该项目已达到目录数量上限（{$maxCatalogsPerItem}个），无法继续创建。如有特殊需求，请联系网站管理员"
      );
    }

    $parentCatId = (int) ($params['parent_cat_id'] ?? 0);
    $sNumber = (int) ($params['s_number'] ?? 99);

    // 检查父目录是否存在
    if ($parentCatId > 0) {
      $parentCatalog = Catalog::findById($parentCatId);
      if (!$parentCatalog || (int) $parentCatalog->item_id !== $itemId) {
        McpError::throw(McpError::RESOURCE_NOT_FOUND, '父目录不存在');
      }
    }

    // 复用 Catalog::save 方法，确保与原后端逻辑一致（包括层级计算等）
    $result = Catalog::save(0, $itemId, $catName, $parentCatId, $sNumber);
    if (!$result) {
      McpError::throw(McpError::OPERATION_FAILED, '目录创建失败');
    }

    // 更新项目最后更新时间
    DB::table('item')
      ->where('item_id', $itemId)
      ->update(['last_update_time' => time()]);

    return [
      'cat_id' => (int) $result['cat_id'],
      'cat_name' => $result['cat_name'],
      'parent_cat_id' => (int) ($result['parent_cat_id'] ?? 0),
      'message' => '目录创建成功',
    ];
  }

  /**
   * 更新目录
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function updateCatalog(array $params): array
  {
    $catId = (int) ($params['cat_id'] ?? 0);
    if ($catId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '目录ID不能为空');
    }

    // 获取目录信息
    $catalog = Catalog::findById($catId);
    if (!$catalog) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '目录不存在');
    }

    $itemId = (int) $catalog->item_id;

    // 检查写入权限
    $this->requireWritePermission($itemId);

    // 准备更新参数
    $catName = isset($params['cat_name']) ? trim($params['cat_name']) : $catalog->cat_name;
    if ($catName === '') {
      McpError::throw(McpError::INVALID_PARAMS, '目录名称不能为空');
    }

    $parentCatId = isset($params['parent_cat_id']) ? (int) $params['parent_cat_id'] : (int) $catalog->parent_cat_id;
    $sNumber = isset($params['s_number']) ? (int) $params['s_number'] : (int) $catalog->s_number;

    // 复用 Catalog::save 方法，确保与原后端逻辑一致（包括层级计算等）
    $result = Catalog::save($catId, $itemId, $catName, $parentCatId, $sNumber);
    if (!$result) {
      McpError::throw(McpError::OPERATION_FAILED, '目录更新失败');
    }

    // 更新项目最后更新时间
    DB::table('item')
      ->where('item_id', $itemId)
      ->update(['last_update_time' => time()]);

    return [
      'cat_id' => $catId,
      'message' => '目录更新成功',
    ];
  }

  /**
   * 删除目录（含子目录和页面）
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function deleteCatalog(array $params): array
  {
    $catId = (int) ($params['cat_id'] ?? 0);
    if ($catId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '目录ID不能为空');
    }

    // 获取目录信息
    $catalog = Catalog::findById($catId);
    if (!$catalog) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '目录不存在');
    }

    $itemId = (int) $catalog->item_id;

    // 检查写入权限
    $this->requireWritePermission($itemId);

    // 统计要删除的目录数量（包含子目录）
    $catIds = $this->getAllChildCatalogIds($itemId, $catId);
    $catIds[] = $catId;
    $deletedCount = count($catIds);

    // 复用 Catalog::deleteCat 方法，确保与原后端逻辑一致
    // 包括：递归删除子目录、软删除页面、清理缓存等
    $success = Catalog::deleteCat($catId);
    if (!$success) {
      McpError::throw(McpError::OPERATION_FAILED, '目录删除失败');
    }

    // 更新项目最后更新时间
    DB::table('item')
      ->where('item_id', $itemId)
      ->update(['last_update_time' => time()]);

    return [
      'cat_id' => $catId,
      'deleted_catalogs' => $deletedCount,
      'message' => '目录已删除',
    ];
  }

  /**
   * 获取所有子目录ID
   *
   * @param int $itemId 项目ID
   * @param int $parentCatId 父目录ID
   * @return array
   */
  private function getAllChildCatalogIds(int $itemId, int $parentCatId): array
  {
    $catIds = [];
    $children = DB::table('catalog')
      ->where('item_id', $itemId)
      ->where('parent_cat_id', $parentCatId)
      ->get()
      ->all();

    foreach ($children as $child) {
      $childCatId = (int) $child->cat_id;
      $catIds[] = $childCatId;
      // 递归获取子目录
      $catIds = array_merge($catIds, $this->getAllChildCatalogIds($itemId, $childCatId));
    }

    return $catIds;
  }
}
