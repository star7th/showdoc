<?php

namespace App\Mcp;

use App\Model\UserAiToken;
use App\Model\Item;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * MCP Handler 基类
 * 
 * 所有 MCP Tool Handler 的基类，提供通用的权限检查和辅助方法
 */
abstract class McpHandler
{
  /**
   * 当前 Token 信息
   *
   * @var array|null
   */
  protected ?array $tokenInfo = null;

  /**
   * 当前用户 ID
   *
   * @var int
   */
  protected int $uid = 0;

  /**
   * 设置当前 Token 信息
   *
   * @param array $tokenInfo Token 信息数组
   * @return void
   */
  public function setTokenInfo(array $tokenInfo): void
  {
    $this->tokenInfo = $tokenInfo;
    $this->uid = (int) ($tokenInfo['uid'] ?? 0);
  }

  /**
   * 获取当前用户 ID
   *
   * @return int
   */
  protected function getUid(): int
  {
    return $this->uid;
  }

  /**
   * 检查 Token 是否有读取权限
   *
   * @return bool
   */
  protected function canRead(): bool
  {
    if (!$this->tokenInfo) {
      return false;
    }
    $permission = $this->tokenInfo['permission'] ?? 'read';
    return in_array($permission, ['read', 'write']);
  }

  /**
   * 检查 Token 是否有写入权限
   *
   * @return bool
   */
  protected function canWrite(): bool
  {
    if (!$this->tokenInfo) {
      return false;
    }
    return ($this->tokenInfo['permission'] ?? '') === 'write';
  }

  /**
   * 检查 Token 是否允许创建项目
   *
   * @return bool
   */
  protected function canCreateItem(): bool
  {
    if (!$this->tokenInfo) {
      return false;
    }
    return (bool) ($this->tokenInfo['can_create_item'] ?? false);
  }

  /**
   * 检查 Token 是否允许删除项目
   *
   * @return bool
   */
  protected function canDeleteItem(): bool
  {
    if (!$this->tokenInfo) {
      return false;
    }
    return (bool) ($this->tokenInfo['can_delete_item'] ?? false);
  }

  /**
   * 检查项目是否在 Token 的权限范围内
   *
   * @param int $itemId 项目 ID
   * @return bool
   */
  protected function isItemInScope(int $itemId): bool
  {
    if (!$this->tokenInfo) {
      return false;
    }

    $scope = $this->tokenInfo['scope'] ?? 'all';

    // 如果是全部项目范围，直接返回 true
    if ($scope === 'all') {
      return true;
    }

    // 如果是指定项目范围，检查项目是否在允许列表中
    $allowedItems = $this->tokenInfo['allowed_items'] ?? '';
    if (empty($allowedItems)) {
      return false;
    }

    $allowedIds = json_decode($allowedItems, true);
    if (!is_array($allowedIds)) {
      return false;
    }

    return in_array($itemId, $allowedIds);
  }

  /**
   * 检查用户是否是项目成员
   *
   * @param int $itemId 项目 ID
   * @return bool
   */
  protected function isItemMember(int $itemId): bool
  {
    if ($this->uid <= 0 || $itemId <= 0) {
      return false;
    }

    // 检查是否是项目创建者
    $item = Item::findById($itemId);
    if ($item && (int) $item->uid === $this->uid) {
      return true;
    }

    // 检查是否是项目成员
    $member = DB::table('item_member')
      ->where('item_id', $itemId)
      ->where('uid', $this->uid)
      ->first();
    if ($member) {
      return true;
    }

    // 检查是否是团队成员
    $teamMember = DB::table('team_item_member')
      ->where('item_id', $itemId)
      ->where('member_uid', $this->uid)
      ->first();
    if ($teamMember) {
      return true;
    }

    return false;
  }

  /**
   * 获取用户在项目中的角色
   *
   * @param int $itemId 项目 ID
   * @return string|null 角色：owner/admin/editor/readonly/null
   */
  protected function getItemRole(int $itemId): ?string
  {
    if ($this->uid <= 0 || $itemId <= 0) {
      return null;
    }

    // 检查是否是项目创建者
    $item = Item::findById($itemId);
    if (!$item) {
      return null;
    }
    if ((int) $item->uid === $this->uid) {
      return 'owner';
    }

    // 检查是否是系统管理员
    $user = \App\Model\User::findById($this->uid);
    if ($user && (int) $user->groupid === 1) {
      return 'admin';
    }

    // 检查项目成员表
    $member = DB::table('item_member')
      ->where('item_id', $itemId)
      ->where('uid', $this->uid)
      ->first();
    if ($member) {
      // member_group_id: 1=编辑, 2=管理员, 3=只读
      $groupId = (int) $member->member_group_id;
      if ($groupId === 2) {
        return 'admin';
      } elseif ($groupId === 1) {
        return 'editor';
      } elseif ($groupId === 3) {
        return 'readonly';
      }
    }

    // 检查团队成员表
    $teamMember = DB::table('team_item_member')
      ->where('item_id', $itemId)
      ->where('member_uid', $this->uid)
      ->first();
    if ($teamMember) {
      $groupId = (int) $teamMember->member_group_id;
      if ($groupId === 2) {
        return 'admin';
      } elseif ($groupId === 1) {
        return 'editor';
      } elseif ($groupId === 3) {
        return 'readonly';
      }
    }

    return null;
  }

  /**
   * 检查用户是否有项目编辑权限
   *
   * @param int $itemId 项目 ID
   * @return bool
   */
  protected function canEditItem(int $itemId): bool
  {
    $role = $this->getItemRole($itemId);
    return in_array($role, ['owner', 'admin', 'editor']);
  }

  /**
   * 检查用户是否有项目管理权限
   *
   * @param int $itemId 项目 ID
   * @return bool
   */
  protected function canManageItem(int $itemId): bool
  {
    $role = $this->getItemRole($itemId);
    return in_array($role, ['owner', 'admin']);
  }

  /**
   * 要求读取权限（无权限时抛出异常）
   *
   * @param int $itemId 项目 ID
   * @throws McpException
   */
  protected function requireReadPermission(int $itemId): void
  {
    if (!$this->canRead()) {
      McpError::throw(
        McpError::TOKEN_OPERATION_DENIED,
        'Token 不允许执行读取操作'
      );
    }

    if (!$this->isItemInScope($itemId)) {
      McpError::throw(
        McpError::TOKEN_SCOPE_DENIED,
        '该项目不在 Token 的权限范围内'
      );
    }

    if (!$this->isItemMember($itemId)) {
      McpError::throw(
        McpError::NOT_ITEM_MEMBER,
        '您不是该项目的成员'
      );
    }
  }

  /**
   * 要求写入权限（无权限时抛出异常）
   *
   * @param int $itemId 项目 ID
   * @throws McpException
   */
  protected function requireWritePermission(int $itemId): void
  {
    if (!$this->canWrite()) {
      McpError::throw(
        McpError::TOKEN_OPERATION_DENIED,
        'Token 不允许执行写入操作'
      );
    }

    if (!$this->isItemInScope($itemId)) {
      McpError::throw(
        McpError::TOKEN_SCOPE_DENIED,
        '该项目不在 Token 的权限范围内'
      );
    }

    if (!$this->isItemMember($itemId)) {
      McpError::throw(
        McpError::NOT_ITEM_MEMBER,
        '您不是该项目的成员'
      );
    }

    if (!$this->canEditItem($itemId)) {
      McpError::throw(
        McpError::NO_EDIT_PERMISSION,
        '权限不足：您在该项目中无编辑权限'
      );
    }
  }

  /**
   * 将新创建的项目添加到 Token 的权限范围
   *
   * @param int $itemId 新创建的项目 ID
   * @return void
   */
  protected function addCreatedItemToScope(int $itemId): void
  {
    if (!$this->tokenInfo) {
      return;
    }

    $scope = $this->tokenInfo['scope'] ?? 'all';
    $autoAdd = (bool) ($this->tokenInfo['auto_add_created_item'] ?? true);

    // 仅当 scope=selected 且 auto_add_created_item=1 时才添加
    if ($scope !== 'selected' || !$autoAdd) {
      return;
    }

    $allowedItems = $this->tokenInfo['allowed_items'] ?? '[]';
    $allowedIds = json_decode($allowedItems, true) ?: [];

    if (!in_array($itemId, $allowedIds)) {
      $allowedIds[] = $itemId;
      UserAiToken::updateAllowedItems($this->tokenInfo['id'], $allowedIds);
    }
  }

  /**
   * 获取 Handler 支持的操作列表
   *
   * @return array
   */
  abstract public function getSupportedOperations(): array;

  /**
   * 执行操作
   *
   * @param string $operation 操作名称
   * @param array $params 参数
   * @return mixed
   * @throws McpException
   */
  abstract public function execute(string $operation, array $params = []);
}
