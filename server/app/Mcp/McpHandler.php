<?php

namespace App\Mcp;

use App\Model\UserAiToken;
use App\Model\Item;
use App\Common\Cache\CacheManager;
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
    return in_array($permission, ['read', 'write', 'admin']);
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
    $permission = $this->tokenInfo['permission'] ?? '';
    return in_array($permission, ['write', 'admin']);
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
   * 缓存 TTL（秒）
   */
  private const CACHE_TTL = 300;

  /**
   * 从缓存获取 Item 信息
   *
   * @param int $itemId 项目 ID
   * @return object|null
   */
  protected function getItemCached(int $itemId): ?object
  {
    if ($itemId <= 0) {
      return null;
    }

    $cache = CacheManager::getInstance();
    if ($cache->isEnabled()) {
      $key = 'mcp_item:' . $itemId;
      $cached = $cache->get($key);
      if ($cached !== null) {
        if ($cached === false) return null;
        return is_array($cached) ? (object) $cached : $cached;
      }

      $item = Item::findById($itemId);
      // 用 false 标记「不存在」，避免穿透
      $cache->set($key, $item ?? false, self::CACHE_TTL);
      return $item;
    }

    return Item::findById($itemId);
  }

  /**
   * 检查用户是否是项目成员（带 Redis 缓存）
   *
   * @param int $itemId 项目 ID
   * @return bool
   */
  protected function isItemMember(int $itemId): bool
  {
    // 游客：uid=0 但已通过 guest_token + item_id 验证，scope 检查即可替代成员检查
    if (($this->tokenInfo['auth_type'] ?? '') === 'guest') {
      return $this->isItemInScope($itemId);
    }

    if ($this->uid <= 0 || $itemId <= 0) {
      return false;
    }

    // 尝试 Redis 缓存
    $cache = CacheManager::getInstance();
    if ($cache->isEnabled()) {
      $key = 'mcp_item_member:' . $this->uid . ':' . $itemId;
      $cached = $cache->get($key);
      if ($cached !== null) {
        return $cached === true;
      }
    }

    // 以下为原始 DB 查询逻辑（改用 getItemCached）
    $item = $this->getItemCached($itemId);
    if ($item && (int) $item->uid === $this->uid) {
      $result = true;
    } else {
      // 检查是否是项目成员
      $member = DB::table('item_member')
        ->where('item_id', $itemId)
        ->where('uid', $this->uid)
        ->first();
      if ($member) {
        $result = true;
      } else {
        // 检查是否是团队成员
        $teamMember = DB::table('team_item_member')
          ->where('item_id', $itemId)
          ->where('member_uid', $this->uid)
          ->first();
        $result = (bool) $teamMember;
      }
    }

    // 写入缓存
    if ($cache->isEnabled()) {
      $cache->set('mcp_item_member:' . $this->uid . ':' . $itemId, $result, self::CACHE_TTL);
    }

    return $result;
  }

  /**
   * 获取用户在项目中的角色（带 Redis 缓存）
   *
   * @param int $itemId 项目 ID
   * @return string|null 角色：owner/admin/editor/readonly/null
   */
  protected function getItemRole(int $itemId): ?string
  {
    if ($this->uid <= 0 || $itemId <= 0) {
      return null;
    }

    // 尝试 Redis 缓存
    $cache = CacheManager::getInstance();
    if ($cache->isEnabled()) {
      $key = 'mcp_item_role:' . $this->uid . ':' . $itemId;
      $cached = $cache->get($key);
      if ($cached !== null) {
        // "__none__" 标记表示非成员（null 不能序列化区分）
        return $cached === '__none__' ? null : $cached;
      }
    }

    // 以下为原始 DB 查询逻辑（改用 getItemCached）
    $item = $this->getItemCached($itemId);
    if (!$item) {
      $result = null;
    } elseif ((int) $item->uid === $this->uid) {
      $result = 'owner';
    } else {
      // 检查是否是系统管理员
      $user = \App\Model\User::findById($this->uid);
      if ($user && (int) $user->groupid === 1) {
        $result = 'admin';
      } else {
        // 检查项目成员表
        $member = DB::table('item_member')
          ->where('item_id', $itemId)
          ->where('uid', $this->uid)
          ->first();
        if ($member) {
          $groupId = (int) $member->member_group_id;
          if ($groupId === 2) {
            $result = 'admin';
          } elseif ($groupId === 1) {
            $result = 'editor';
          } elseif ($groupId === 3) {
            $result = 'readonly';
          } else {
            $result = null;
          }
        } else {
          // 检查团队成员表
          $teamMember = DB::table('team_item_member')
            ->where('item_id', $itemId)
            ->where('member_uid', $this->uid)
            ->first();
          if ($teamMember) {
            $groupId = (int) $teamMember->member_group_id;
            if ($groupId === 2) {
              $result = 'admin';
            } elseif ($groupId === 1) {
              $result = 'editor';
            } elseif ($groupId === 3) {
              $result = 'readonly';
            } else {
              $result = null;
            }
          } else {
            $result = null;
          }
        }
      }
    }

    // 写入缓存（用 "__none__" 标记 null，避免每次穿透）
    if ($cache->isEnabled()) {
      $cache->set(
        'mcp_item_role:' . $this->uid . ':' . $itemId,
        $result ?? '__none__',
        self::CACHE_TTL
      );
    }

    return $result;
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
   * 页面级读取权限校验（防存在性探测）
   *
   * 与 requireReadPermission 相同的检查；但"非项目成员"这类拒绝统一改为
   * 与"页面不存在"完全一致的错误码/文案（RESOURCE_NOT_FOUND + 页面不存在或无权访问），
   * 避免非成员借差异化报错探测页面存在性。
   * Token 级错误（读权限/范围）不涉及页面存在性，保持原语义。
   */
  protected function requirePageReadPermission(int $itemId): void
  {
    try {
      $this->requireReadPermission($itemId);
    } catch (McpException $e) {
      if ($e->getCode() === McpError::NOT_ITEM_MEMBER) {
        McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在或无权访问');
      }
      throw $e;
    }
  }

  /**
   * 简单每分钟限流（开源版无 Limiter/Redis，用 Runtime 文件计数器）
   *
   * 文件名含分钟数，跨分钟自动失效；文件不可写时不拦截（与全站口径一致）。
   *
   * @param string $key   限流键（需自行保证粒度，如凭证指纹）
   * @param int    $limit 每分钟允许的最大次数
   * @return bool true=允许，false=超限
   */
  protected function perMinuteAllow(string $key, int $limit): bool
  {
    $dir = dirname(__DIR__, 2) . '/Runtime/McpRate';
    if (!is_dir($dir)) {
      @mkdir($dir, 0755, true);
    }
    $minute = gmdate('YmdHi');
    $file   = $dir . '/' . md5($key . '|' . $minute) . '.cnt';

    $count = 0;
    $fh = @fopen($file, 'c+');
    if (!$fh) {
      return true;
    }
    try {
      flock($fh, LOCK_EX);
      $raw   = (string) stream_get_contents($fh);
      $count = (int) $raw;
      $count++;
      ftruncate($fh, 0);
      rewind($fh);
      fwrite($fh, (string) $count);
    } finally {
      flock($fh, LOCK_UN);
      fclose($fh);
    }

    // 概率清理上一分钟的计数文件，避免堆积
    if ($count === 1 && random_int(1, 20) === 1) {
      $old = $dir . '/' . md5($key . '|' . gmdate('YmdHi', time() - 60)) . '.cnt';
      @unlink($old);
    }

    return $count <= $limit;
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
   * 要求管理权限（仅 owner/admin，用于删除类等高危操作）
   *
   * @param int $itemId 项目 ID
   * @throws McpException
   */
  protected function requireManagePermission(int $itemId): void
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

    if (!$this->canManageItem($itemId)) {
      McpError::throw(
        McpError::NO_EDIT_PERMISSION,
        '权限不足：该操作需要项目管理员权限'
      );
    }
  }

  /**
   * 清除指定用户和项目的权限缓存
   * TODO: 在成员变更时（加入/移除成员、变更角色、转让项目等）应调用此方法清除缓存
   *
   * @param int $uid 用户 ID
   * @param int $itemId 项目 ID
   * @return void
   */
  protected static function clearPermissionCache(int $uid, int $itemId): void
  {
    $cache = CacheManager::getInstance();
    if (!$cache->isEnabled()) {
      return;
    }

    $cache->delete('mcp_item_member:' . $uid . ':' . $itemId);
    $cache->delete('mcp_item_role:' . $uid . ':' . $itemId);
    $cache->delete('mcp_item:' . $itemId);
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
   * 解析目录ID（cat_id 优先于 cat_name）
   *
   * 当同时提供 cat_id 和 cat_name 时，cat_id 优先（精确无歧义），
   * cat_name 作为兜底（不存在则自动创建）。
   *
   * @param int $itemId 项目ID
   * @param int $catId 目录ID（可选，优先使用）
   * @param string $catName 目录名称（可选，cat_id 为空时作为兜底）
   * @return int 解析后的目录ID（0 表示根目录）
   * @throws McpException cat_id 不属于该项目时抛出异常
   */
  protected function resolveCatId(int $itemId, int $catId, string $catName): int
  {
    if ($catId > 0) {
      // 验证 cat_id 属于该项目（防止跨项目指定目录）
      $catalog = DB::table('catalog')
        ->where('cat_id', $catId)
        ->where('item_id', $itemId)
        ->first();
      if (!$catalog) {
        McpError::throw(McpError::INVALID_PARAMS, "目录ID {$catId} 不属于该项目或不存在");
      }
      return $catId;
    }

    if ($catName !== '') {
      return $this->getOrCreateCatalog($itemId, $catName);
    }

    return 0;
  }

  /**
   * 获取或创建目录
   *
   * @param int $itemId 项目ID
   * @param string $catName 目录名称（支持多级，用/分隔）
   * @return int 目录ID
   */
  protected function getOrCreateCatalog(int $itemId, string $catName): int
  {
    $catNames = array_map('trim', explode('/', $catName));
    $parentCatId = 0;
    $catId = 0;
    $depth = 0;

    for ($i = 0; $i < count($catNames); $i++) {
      $name = $catNames[$i];
      if ($name === '') {
        continue;
      }

      $depth++;
      $level = $depth + 1;

      // 查找目录（仅按 item_id + cat_name + parent_cat_id 匹配，不限制 level）
      $catalog = DB::table('catalog')
        ->where('item_id', $itemId)
        ->where('cat_name', $name)
        ->where('parent_cat_id', $parentCatId)
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
