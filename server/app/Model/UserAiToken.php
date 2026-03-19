<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;
use App\Common\Helper\IpHelper;

/**
 * 用户级 AI Token Model
 * 
 * 用于 MCP 协议集成，支持 AI 编辑器访问用户的 ShowDoc 项目
 */
class UserAiToken
{
  /**
   * 表名
   */
  const TABLE = 'user_ai_token';

  /**
   * Token 前缀
   */
  const TOKEN_PREFIX = 'ai_';

  /**
   * Token 随机部分长度（字节）
   * 43 字节 = 86 个十六进制字符，总长度 89 字符（3 前缀 + 86 随机）
   */
  const TOKEN_RANDOM_BYTES = 43;

  /**
   * 创建新的 AI Token
   *
   * @param int $uid 用户 ID
   * @param array $options 选项
   * @return string|null Token 字符串，失败返回 null
   */
  public static function createToken(int $uid, array $options = []): ?string
  {
    if ($uid <= 0) {
      return null;
    }

    // 生成 Token
    $token = self::generateToken();

    $data = [
      'uid' => $uid,
      'token' => $token,
      'name' => $options['name'] ?? null,
      'permission' => $options['permission'] ?? 'write',
      'scope' => $options['scope'] ?? 'all',
      'allowed_items' => isset($options['allowed_items'])
        ? json_encode($options['allowed_items'])
        : null,
      'can_create_item' => $options['can_create_item'] ?? 1,
      'can_delete_item' => $options['can_delete_item'] ?? 0,
      'auto_add_created_item' => $options['auto_add_created_item'] ?? 1,
      'expires_at' => $options['expires_at'] ?? null,
      'created_at' => date('Y-m-d H:i:s'),
      'is_active' => 1,
    ];

    try {
      $ok = DB::table(self::TABLE)->insert($data);
      return $ok ? $token : null;
    } catch (\Throwable $e) {
      return null;
    }
  }

  /**
   * 生成 Token 字符串
   *
   * @return string
   */
  public static function generateToken(): string
  {
    return self::TOKEN_PREFIX . bin2hex(random_bytes(self::TOKEN_RANDOM_BYTES));
  }

  /**
   * 根据 Token 获取信息
   *
   * @param string $token Token 字符串
   * @return array|null Token 信息，不存在返回 null
   */
  public static function getToken(string $token): ?array
  {
    $token = trim($token);
    if ($token === '') {
      return null;
    }

    $row = DB::table(self::TABLE)
      ->where('token', $token)
      ->where('is_active', 1)
      ->first();

    if (!$row) {
      return null;
    }

    // 检查是否过期
    $expiresAt = $row->expires_at ?? null;
    if ($expiresAt && strtotime($expiresAt) < time()) {
      return null;
    }

    return (array) $row;
  }

  /**
   * 获取用户的所有 Token 列表
   *
   * @param int $uid 用户 ID
   * @return array Token 列表
   */
  public static function getTokensByUid(int $uid): array
  {
    if ($uid <= 0) {
      return [];
    }

    $rows = DB::table(self::TABLE)
      ->where('uid', $uid)
      ->orderBy('created_at', 'desc')
      ->get()
      ->all();

    $result = [];
    foreach ($rows as $row) {
      $data = (array) $row;
      // 生成 Token 预览（遮蔽部分）
      $data['token_preview'] = self::maskToken($data['token']);
      // 保留完整 token，前端复制功能需要
      $result[] = $data;
    }

    return $result;
  }

  /**
   * 获取 Token 详情（包含完整 Token）
   *
   * @param int $id Token ID
   * @param int $uid 用户 ID（用于权限验证）
   * @return array|null
   */
  public static function getTokenById(int $id, int $uid): ?array
  {
    if ($id <= 0 || $uid <= 0) {
      return null;
    }

    $row = DB::table(self::TABLE)
      ->where('id', $id)
      ->where('uid', $uid)
      ->first();

    return $row ? (array) $row : null;
  }

  /**
   * 更新 Token 信息
   *
   * @param int $id Token ID
   * @param int $uid 用户 ID（用于权限验证）
   * @param array $data 更新数据
   * @return bool
   */
  public static function updateToken(int $id, int $uid, array $data): bool
  {
    if ($id <= 0 || $uid <= 0) {
      return false;
    }

    // 允许更新的字段
    $allowedFields = [
      'name',
      'permission',
      'scope',
      'allowed_items',
      'can_create_item',
      'can_delete_item',
      'auto_add_created_item',
      'expires_at',
      'is_active',
    ];

    $updateData = [];
    foreach ($allowedFields as $field) {
      if (isset($data[$field])) {
        if ($field === 'allowed_items' && is_array($data[$field])) {
          $updateData[$field] = json_encode($data[$field]);
        } else {
          $updateData[$field] = $data[$field];
        }
      }
    }

    if (empty($updateData)) {
      return false;
    }

    try {
      $affected = DB::table(self::TABLE)
        ->where('id', $id)
        ->where('uid', $uid)
        ->update($updateData);

      return $affected > 0;
    } catch (\Throwable $e) {
      return false;
    }
  }

  /**
   * 更新 Token 的 allowed_items 字段
   *
   * @param int $id Token ID
   * @param array $allowedItems 允许的项目 ID 列表
   * @return bool
   */
  public static function updateAllowedItems(int $id, array $allowedItems): bool
  {
    try {
      DB::table(self::TABLE)
        ->where('id', $id)
        ->update(['allowed_items' => json_encode($allowedItems)]);
      return true;
    } catch (\Throwable $e) {
      return false;
    }
  }

  /**
   * 重置 Token（生成新的 Token 字符串）
   *
   * @param int $id Token ID
   * @param int $uid 用户 ID
   * @return string|null 新的 Token 字符串，失败返回 null
   */
  public static function resetToken(int $id, int $uid): ?string
  {
    if ($id <= 0 || $uid <= 0) {
      return null;
    }

    $newToken = self::generateToken();

    try {
      $affected = DB::table(self::TABLE)
        ->where('id', $id)
        ->where('uid', $uid)
        ->update(['token' => $newToken]);

      return $affected > 0 ? $newToken : null;
    } catch (\Throwable $e) {
      return null;
    }
  }

  /**
   * 撤销 Token（软删除）
   *
   * @param int $id Token ID
   * @param int $uid 用户 ID
   * @return bool
   */
  public static function revokeToken(int $id, int $uid): bool
  {
    if ($id <= 0 || $uid <= 0) {
      return false;
    }

    try {
      $affected = DB::table(self::TABLE)
        ->where('id', $id)
        ->where('uid', $uid)
        ->update(['is_active' => 0]);

      return $affected > 0;
    } catch (\Throwable $e) {
      return false;
    }
  }

  /**
   * 删除 Token（硬删除）
   *
   * @param int $id Token ID
   * @param int $uid 用户 ID
   * @return bool
   */
  public static function deleteToken(int $id, int $uid): bool
  {
    if ($id <= 0 || $uid <= 0) {
      return false;
    }

    try {
      $affected = DB::table(self::TABLE)
        ->where('id', $id)
        ->where('uid', $uid)
        ->delete();

      return $affected > 0;
    } catch (\Throwable $e) {
      return false;
    }
  }

  /**
   * 更新最后使用时间
   *
   * @param string $token Token 字符串
   * @return void
   */
  public static function touchLastUsed(string $token): void
  {
    $token = trim($token);
    if ($token === '') {
      return;
    }

    DB::table(self::TABLE)
      ->where('token', $token)
      ->update(['last_used_at' => date('Y-m-d H:i:s')]);
  }

  /**
   * 遮蔽 Token（只显示前缀和后几位）
   *
   * @param string $token Token 字符串
   * @return string
   */
  public static function maskToken(string $token): string
  {
    if (strlen($token) <= 10) {
      return $token;
    }

    $prefix = substr($token, 0, 5);
    $suffix = substr($token, -6);

    return $prefix . '***' . $suffix;
  }

  /**
   * 验证 Token 格式
   *
   * @param string $token Token 字符串
   * @return bool
   */
  public static function isValidTokenFormat(string $token): bool
  {
    if (empty($token)) {
      return false;
    }

    // 检查前缀
    if (strpos($token, self::TOKEN_PREFIX) !== 0) {
      return false;
    }

    // 检查长度（前缀3字符 + 随机部分86字符 = 89字符）
    if (strlen($token) !== 89) {
      return false;
    }

    // 检查随机部分是否为有效的十六进制
    $randomPart = substr($token, strlen(self::TOKEN_PREFIX));
    return ctype_xdigit($randomPart);
  }

  /**
   * 获取用户的 Token 数量
   *
   * @param int $uid 用户 ID
   * @return int
   */
  public static function getTokenCount(int $uid): int
  {
    if ($uid <= 0) {
      return 0;
    }

    return DB::table(self::TABLE)
      ->where('uid', $uid)
      ->where('is_active', 1)
      ->count();
  }

  /**
   * 清理过期 Token
   *
   * @param int $days 过期天数（默认 365 天）
   * @return int 删除的数量
   */
  public static function cleanExpiredTokens(int $days = 365): int
  {
    $expireDate = date('Y-m-d H:i:s', time() - $days * 86400);

    try {
      return DB::table(self::TABLE)
        ->where('expires_at', '<', $expireDate)
        ->where('is_active', 0)
        ->delete();
    } catch (\Throwable $e) {
      return 0;
    }
  }

  /**
   * 频率限制配置
   */
  const RATE_LIMIT_WINDOW = 600; // 10 分钟（秒）
  const RATE_LIMIT_MAX_REQUESTS = 10000; // 最大请求数

  /**
   * 检查并增加请求计数（频率限制）
   *
   * @param string $token Token 字符串
   * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => int]
   */
  public static function checkRateLimit(string $token): array
  {
    $token = trim($token);
    if ($token === '') {
      return ['allowed' => false, 'remaining' => 0, 'reset_at' => 0];
    }

    // 使用 Token 的哈希作为缓存键（避免文件名过长）
    $cacheKey = 'mcp_rate_' . md5($token);
    $cacheFile = self::getRateLimitCachePath($cacheKey);
    $now = time();

    // 读取当前计数
    $data = self::readRateLimitCache($cacheFile);

    // 如果窗口已过期，重置计数
    if ($data && isset($data['reset_at']) && $data['reset_at'] <= $now) {
      $data = null;
    }

    if (!$data) {
      // 新窗口
      $data = [
        'count' => 0,
        'reset_at' => $now + self::RATE_LIMIT_WINDOW,
      ];
    }

    // 检查是否超过限制
    if ($data['count'] >= self::RATE_LIMIT_MAX_REQUESTS) {
      return [
        'allowed' => false,
        'remaining' => 0,
        'reset_at' => $data['reset_at'],
      ];
    }

    // 增加计数
    $data['count']++;
    self::writeRateLimitCache($cacheFile, $data);

    return [
      'allowed' => true,
      'remaining' => self::RATE_LIMIT_MAX_REQUESTS - $data['count'],
      'reset_at' => $data['reset_at'],
    ];
  }

  /**
   * 获取频率限制缓存目录
   *
   * @return string
   */
  private static function getRateLimitCacheDir(): string
  {
    // 兼容 RUNTIME_PATH 未定义的情况
    $runtimePath = defined('RUNTIME_PATH') ? RUNTIME_PATH : dirname(__DIR__, 2) . '/app/Runtime/';
    $dir = $runtimePath . 'mcp_rate_limit';
    if (!is_dir($dir)) {
      @mkdir($dir, 0755, true);
    }
    return $dir;
  }

  /**
   * 获取频率限制缓存文件路径
   *
   * @param string $key 缓存键
   * @return string
   */
  private static function getRateLimitCachePath(string $key): string
  {
    return self::getRateLimitCacheDir() . DIRECTORY_SEPARATOR . $key . '.json';
  }

  /**
   * 读取频率限制缓存
   *
   * @param string $file 文件路径
   * @return array|null
   */
  private static function readRateLimitCache(string $file): ?array
  {
    if (!file_exists($file)) {
      return null;
    }

    $content = @file_get_contents($file);
    if ($content === false) {
      return null;
    }

    $data = json_decode($content, true);
    return is_array($data) ? $data : null;
  }

  /**
   * 写入频率限制缓存
   *
   * @param string $file 文件路径
   * @param array $data 数据
   * @return bool
   */
  private static function writeRateLimitCache(string $file, array $data): bool
  {
    $content = json_encode($data);
    return @file_put_contents($file, $content) !== false;
  }
}
