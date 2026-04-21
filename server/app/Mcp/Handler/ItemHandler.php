<?php

namespace App\Mcp\Handler;

use App\Mcp\McpHandler;
use App\Mcp\McpError;
use App\Mcp\McpException;
use App\Model\Item;
use App\Model\ItemMember;
use App\Model\UserAiToken;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * MCP 项目操作 Handler
 */
class ItemHandler extends McpHandler
{
  /**
   * 获取支持的操列列表
   *
   * @return array
   */
  public function getSupportedOperations(): array
  {
    return ['list_items', 'get_item', 'create_item', 'update_item', 'delete_item'];
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
      case 'list_items':
        return $this->listItems($params);

      case 'get_item':
        return $this->getItem($params);

      case 'create_item':
        return $this->createItem($params);

      case 'update_item':
        return $this->updateItem($params);

      case 'delete_item':
        return $this->deleteItem($params);

      default:
        McpError::throw(McpError::METHOD_NOT_FOUND, "操作不存在: {$operation}");
    }
  }

  /**
   * 列出用户可访问的所有项目
   *
   * @param array $params 参数
   * @return array
   */
  private function listItems(array $params): array
  {
    $uid = $this->getUid();
    if ($uid <= 0) {
      McpError::throw(McpError::TOKEN_INVALID, '用户未登录');
    }

    // 获取用户创建的项目
    $createdItems = DB::table('item')
      ->where('uid', $uid)
      ->where('is_del', 0)
      ->get()
      ->all();

    // 获取用户作为成员的项目
    $memberItems = DB::table('item_member')
      ->join('item', 'item_member.item_id', '=', 'item.item_id')
      ->where('item_member.uid', $uid)
      ->where('item.is_del', 0)
      ->select('item.*', 'item_member.member_group_id')
      ->get()
      ->all();

    // 获取用户所在团队的项目
    $teamItems = DB::table('team_item_member')
      ->join('item', 'team_item_member.item_id', '=', 'item.item_id')
      ->where('team_item_member.member_uid', $uid)
      ->where('item.is_del', 0)
      ->select('item.*', 'team_item_member.member_group_id')
      ->get()
      ->all();

    // 合并并去重
    $itemsMap = [];
    foreach ($createdItems as $item) {
      $item = (array) $item;
      $item['role'] = 'owner';
      $itemsMap[$item['item_id']] = $item;
    }

    foreach ($memberItems as $item) {
      $item = (array) $item;
      if (!isset($itemsMap[$item['item_id']])) {
        $groupId = (int) $item['member_group_id'];
        $item['role'] = $groupId === 2 ? 'admin' : ($groupId === 1 ? 'editor' : 'readonly');
        $itemsMap[$item['item_id']] = $item;
      }
    }

    foreach ($teamItems as $item) {
      $item = (array) $item;
      if (!isset($itemsMap[$item['item_id']])) {
        $groupId = (int) $item['member_group_id'];
        $item['role'] = $groupId === 2 ? 'admin' : ($groupId === 1 ? 'editor' : 'readonly');
        $itemsMap[$item['item_id']] = $item;
      }
    }

    // 根据 Token scope 过滤
    $scope = $this->tokenInfo['scope'] ?? 'all';
    if ($scope === 'selected') {
      $allowedItems = json_decode($this->tokenInfo['allowed_items'] ?? '[]', true) ?: [];
      $itemsMap = array_filter($itemsMap, function ($item) use ($allowedItems) {
        return in_array($item['item_id'], $allowedItems);
      });
    }

    // 格式化输出
    $items = [];
    foreach ($itemsMap as $item) {
      $items[] = [
        'item_id' => (int) $item['item_id'],
        'item_name' => $item['item_name'],
        'item_type' => (int) ($item['item_type'] ?? 1),
        'item_description' => $item['item_description'] ?? '',
        'role' => $item['role'],
        'create_time' => $item['addtime'] ?? '',
        'last_update_time' => $item['last_update_time'] ?? '',
      ];
    }

    return [
      'items' => $items,
      'total' => count($items),
    ];
  }

  /**
   * 获取项目详情
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function getItem(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }

    // 检查权限
    $this->requireReadPermission($itemId);

    // 获取项目信息
    $item = Item::findById($itemId);
    if (!$item) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '项目不存在');
    }

    // 获取用户角色
    $role = $this->getItemRole($itemId);

    // 获取分表名称
    $tableName = \App\Model\Page::tableForItem($itemId);

    return [
      'item_id' => (int) $item->item_id,
      'item_name' => $item->item_name,
      'item_type' => (int) ($item->item_type ?? 1),
      'item_description' => $item->item_description ?? '',
      'role' => $role,
      'create_time' => $item->addtime ?? '',
      'last_update_time' => $item->last_update_time ?? '',
      'page_count' => DB::table($tableName)
        ->where('item_id', $itemId)
        ->where('is_del', 0)
        ->count(),
    ];
  }

  /**
   * 创建新项目
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function createItem(array $params): array
  {
    $uid = $this->getUid();
    if ($uid <= 0) {
      McpError::throw(McpError::TOKEN_INVALID, '用户未登录');
    }

    // 检查 Token 是否允许创建项目
    if (!$this->canCreateItem()) {
      McpError::throw(McpError::TOKEN_OPERATION_DENIED, 'Token 不允许创建项目');
    }

    $itemName = trim($params['item_name'] ?? '');
    if ($itemName === '') {
      McpError::throw(McpError::INVALID_PARAMS, '项目名称不能为空');
    }

    $itemType = (int) ($params['item_type'] ?? 1);
    $itemDescription = trim($params['item_description'] ?? '');
    $password = trim($params['password'] ?? '');

    // 检查用户项目数量限制（复用现有逻辑，与 ItemController::add 一致）
    $itemCount = DB::table('item')
      ->where('uid', $uid)
      ->where('is_del', 0)
      ->count();

    // 开源版：无 VIP 功能，使用固定的项目数量限制
    $allowCount = 100000; // 开源版默认允许 100000 个项目

    // 检查项目数量是否超限（runapi默认项目除外）
    if ($itemCount >= $allowCount && $itemName !== 'runapi默认项目') {
      McpError::throw(
        McpError::OPERATION_FAILED,
        '你创建的项目数超出限制。如有需求请联系网站管理员'
      );
    }

    // 单用户项目数量绝对上限（防止死循环）
    $maxItemsPerUser = 100000;
    if ($itemCount >= $maxItemsPerUser) {
      McpError::throw(
        McpError::OPERATION_FAILED,
        "你创建的项目数已达到系统上限（{$maxItemsPerUser}个），无法继续创建。如有特殊需求，请联系网站管理员"
      );
    }

    // 开源版：无需邮箱绑定和支付实名认证即可创建公开项目

    // 创建项目
    $now = time();
    $data = [
      'item_name' => $itemName,
      'item_type' => $itemType,
      'item_description' => $itemDescription,
      'password' => $password,
      'uid' => $uid,
      'addtime' => $now,
      'last_update_time' => $now,
      'is_del' => 0,
    ];

    // 复用 Item::add 方法，确保与原后端逻辑一致
    $itemId = Item::add($data);
    if ($itemId <= 0) {
      McpError::throw(McpError::OPERATION_FAILED, '项目创建失败');
    }

    // 看板项目：初始化板面 page 及示例任务
    if ($itemType == 6) {
      $username = '';
      $userObj = \App\Model\User::findById($uid);
      if ($userObj) {
        $username = $userObj->username ?? '';
      }

      $defaultContent = json_encode([
        'lists' => [
          ['id' => 'list_default_1', 'title' => '待办', 'position' => 1],
          ['id' => 'list_default_2', 'title' => '进行中', 'position' => 2],
        ],
        'tasks_order' => [
          'list_default_1' => [],
          'list_default_2' => [],
        ],
        'archived_lists' => [],
        'archived_tasks_order' => [],
        'meta' => ['version' => 1, 'last_updated' => 0],
      ], JSON_UNESCAPED_UNICODE);

      $boardPageData = [
        'author_uid'      => $uid,
        'author_username' => $username,
        'page_title'      => '__kanban_board__',
        'item_id'         => $itemId,
        'cat_id'          => 0,
        'page_content'    => htmlspecialchars($defaultContent, ENT_QUOTES, 'UTF-8'),
        'addtime'         => time(),
      ];
      $boardPageId = \App\Model\Page::addPage($itemId, $boardPageData);

      DB::table('item')->where('item_id', $itemId)->update(['allow_comment' => 1]);

      $exampleTasks = [
        ['title' => '了解看板功能', 'list_id' => 'list_default_1', 'description' => '看板是一个轻量级的任务管理工具，你可以通过拖拽来管理任务的状态。', 'tags' => [['color' => 'blue', 'text' => '文档']], 'priority' => 'medium'],
        ['title' => '创建第一个任务', 'list_id' => 'list_default_1', 'description' => '点击列表底部的 + 按钮创建新任务。', 'tags' => [['color' => 'green', 'text' => '入门']], 'priority' => 'high'],
        ['title' => '尝试拖拽任务', 'list_id' => 'list_default_2', 'description' => '将任务从一个列表拖拽到另一个列表来更新状态。', 'tags' => [['color' => 'orange', 'text' => '技巧']], 'priority' => 'medium'],
        ['title' => '设置任务详情', 'list_id' => 'list_default_2', 'description' => '点击任务卡片打开详情，可以设置负责人、截止日期、优先级等。', 'tags' => [['color' => 'purple', 'text' => '进阶']], 'priority' => 'low'],
      ];

      $taskPageIds1 = [];
      $taskPageIds2 = [];
      foreach ($exampleTasks as $task) {
        $taskContent = json_encode([
          'list_id'           => $task['list_id'],
          'description'       => $task['description'],
          'assignee_uid'      => '',
          'assignee_username' => '',
          'creator_uid'       => $uid,
          'creator_username'  => $username,
          'due_date'          => '',
          'tags'              => $task['tags'],
          'priority'          => $task['priority'],
          'linked_pages'      => [],
          'completed'         => false,
        ], JSON_UNESCAPED_UNICODE);
        $extInfo = json_encode(['completed' => false, 'tags' => $task['tags']], JSON_UNESCAPED_UNICODE);
        $taskPageData = [
          'author_uid'      => $uid,
          'author_username' => $username,
          'page_title'      => $task['title'],
          'item_id'         => $itemId,
          'cat_id'          => 0,
          'page_content'    => htmlspecialchars($taskContent, ENT_QUOTES, 'UTF-8'),
          'ext_info'        => $extInfo,
          'addtime'         => time(),
        ];
        $taskPageId = \App\Model\Page::addPage($itemId, $taskPageData);
        if ($taskPageId) {
          if ($task['list_id'] === 'list_default_1') {
            $taskPageIds1[] = (string) $taskPageId;
          } else {
            $taskPageIds2[] = (string) $taskPageId;
          }
        }
      }

      if ($boardPageId && ($taskPageIds1 || $taskPageIds2)) {
        $boardContent = json_encode([
          'lists' => [
            ['id' => 'list_default_1', 'title' => '待办', 'position' => 1],
            ['id' => 'list_default_2', 'title' => '进行中', 'position' => 2],
          ],
          'tasks_order' => [
            'list_default_1' => $taskPageIds1,
            'list_default_2' => $taskPageIds2,
          ],
          'archived_lists' => [],
          'archived_tasks_order' => [],
          'meta' => ['version' => 1, 'last_updated' => 0],
        ], JSON_UNESCAPED_UNICODE);
        \App\Model\Page::savePage($boardPageId, $itemId, [
          'page_content' => htmlspecialchars($boardContent, ENT_QUOTES, 'UTF-8'),
        ]);
      }
    }

    // 将新项目添加到 Token 的权限范围
    $this->addCreatedItemToScope($itemId);

    return [
      'item_id' => $itemId,
      'item_name' => $itemName,
      'item_type' => $itemType,
      'item_description' => $itemDescription,
      'message' => '项目创建成功',
    ];
  }

  /**
   * 更新项目信息
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function updateItem(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }

    // 检查写入权限
    $this->requireWritePermission($itemId);

    // 检查是否有管理权限
    if (!$this->canManageItem($itemId)) {
      McpError::throw(McpError::NO_EDIT_PERMISSION, '只有项目管理员才能修改项目信息');
    }

    // 获取项目
    $item = Item::findById($itemId);
    if (!$item) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '项目不存在');
    }

    // 准备更新数据
    $updateData = [];
    if (isset($params['item_name'])) {
      $itemName = trim($params['item_name']);
      if ($itemName === '') {
        McpError::throw(McpError::INVALID_PARAMS, '项目名称不能为空');
      }
      $updateData['item_name'] = $itemName;
    }

    if (isset($params['item_description'])) {
      $updateData['item_description'] = trim($params['item_description']);
    }

    if (empty($updateData)) {
      McpError::throw(McpError::INVALID_PARAMS, '没有需要更新的内容');
    }

    $updateData['last_update_time'] = time();

    try {
      DB::table('item')
        ->where('item_id', $itemId)
        ->update($updateData);

      return [
        'item_id' => $itemId,
        'message' => '项目更新成功',
      ];
    } catch (\Throwable $e) {
      McpError::throw(McpError::OPERATION_FAILED, '项目更新失败: ' . $e->getMessage());
    }
  }

  /**
   * 删除项目（软删除）
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function deleteItem(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }

    // 检查 Token 是否允许删除项目
    if (!$this->canDeleteItem()) {
      McpError::throw(McpError::TOKEN_OPERATION_DENIED, 'Token 不允许删除项目');
    }

    // 获取项目
    $item = Item::findById($itemId);
    if (!$item) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '项目不存在');
    }

    // 只有项目创建者才能删除项目
    if ((int) $item->uid !== $this->getUid()) {
      McpError::throw(McpError::NO_EDIT_PERMISSION, '只有项目创建者才能删除项目');
    }

    try {
      // 软删除项目
      DB::table('item')
        ->where('item_id', $itemId)
        ->update([
          'is_del' => 1,
          'last_update_time' => time(),
        ]);

      // 软删除项目下的所有页面（使用分表）
      $tableName = \App\Model\Page::tableForItem($itemId);
      DB::table($tableName)
        ->where('item_id', $itemId)
        ->update(['is_del' => 1]);

      return [
        'item_id' => $itemId,
        'message' => '项目已删除',
      ];
    } catch (\Throwable $e) {
      McpError::throw(McpError::OPERATION_FAILED, '项目删除失败: ' . $e->getMessage());
    }
  }
}
