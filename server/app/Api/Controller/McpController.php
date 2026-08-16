<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Mcp\McpServer;
use App\Mcp\McpError;
use App\Model\UserAiToken;
use App\Model\UserToken;
use App\Model\User;
use App\Model\AiChatSession;
use App\Common\Helper\IpHelper;
use App\Common\Cache\CacheManager;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * MCP 控制器
 * 
 * 处理 MCP 协议的 HTTP 请求入口
 */
class McpController extends BaseController
{
  /**
   * 单次批量请求最大工具调用数量
   */
  private const MAX_BATCH_REQUESTS = 10;

  /**
   * 请求体大小上限（字节）。JSON-RPC 消息体应很小，防止超大 POST 占用内存。
   */
  private const MAX_REQUEST_BODY_SIZE = 1048576; // 1 MB

  /**
   * MCP 入口
   *
   * @param Request $request 请求对象
   * @param Response $response 响应对象
   * @return Response
   */
  public function index(Request $request, Response $response): Response
  {
    // 请求体大小限制（Content-Length 为空时（如 chunked）以实际读取长度二次校验）
    $contentLength = (int) $request->getHeaderLine('Content-Length');
    if ($contentLength > self::MAX_REQUEST_BODY_SIZE) {
      return $this->jsonResponse($response, McpError::createResponse(
        McpError::INVALID_REQUEST,
        '请求体过大，最大 ' . self::MAX_REQUEST_BODY_SIZE . ' 字节'
      ));
    }

    // 获取请求体
    $body = (string) $request->getBody();

    if (strlen($body) > self::MAX_REQUEST_BODY_SIZE) {
      return $this->jsonResponse($response, McpError::createResponse(
        McpError::INVALID_REQUEST,
        '请求体过大，最大 ' . self::MAX_REQUEST_BODY_SIZE . ' 字节'
      ));
    }

    // 解析 JSON
    $jsonRequest = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      return $this->jsonResponse($response, McpError::createResponse(
        McpError::PARSE_ERROR,
        'JSON 解析失败: ' . json_last_error_msg()
      ));
    }

    // 验证是否是数组
    if (!is_array($jsonRequest)) {
      return $this->jsonResponse($response, McpError::createResponse(
        McpError::INVALID_REQUEST,
        '请求必须是 JSON 对象'
      ));
    }

    // 处理批量请求
    if (isset($jsonRequest[0])) {
      return $this->handleBatchRequest($response, $jsonRequest);
    }

    // 处理单个请求
    return $this->handleSingleRequest($request, $response, $jsonRequest);
  }

  /**
   * 处理单个请求
   *
   * @param Request $request 请求对象
   * @param Response $response 响应对象
   * @param array $jsonRequest JSON 请求数据
   * @return Response
   */
  private function handleSingleRequest(Request $request, Response $response, array $jsonRequest): Response
  {
    $method = $jsonRequest['method'] ?? '';
    $isNotification = $this->isNotification($jsonRequest);
    $tokenInfo = null;

    if ($this->shouldValidateToken($method)) {
      $tokenExtract = $this->extractToken($request);
      $tokenInfo = $tokenExtract === null ? null : $this->verifyToken($tokenExtract);
      if ($tokenInfo === null) {
        if ($isNotification) {
          return $this->acceptedNotificationResponse($response);
        }

        return $this->jsonResponse($response, McpError::createResponse(
          McpError::TOKEN_INVALID,
          'Token 无效或已过期',
          null,
          $jsonRequest['id'] ?? null
        ));
      }

      // 频率限制检查（仅对外部 ai_token；user_token/guest 为 Agent 内部回环，不受此限）
      if ($tokenExtract !== null && in_array($tokenExtract['type'], ['bearer', 'api_token'], true)) {
        $rateLimit = UserAiToken::checkRateLimit($tokenExtract['token']);
        if (!$rateLimit['allowed']) {
          if ($isNotification) {
            return $this->acceptedNotificationResponse($response);
          }

          return $this->jsonResponse($response, McpError::createResponse(
            McpError::RATE_LIMITED,
            '请求频率超限，请稍后重试',
            [
              'reset_at' => $rateLimit['reset_at'],
              'retry_after' => $rateLimit['reset_at'] - time(),
            ],
            $jsonRequest['id'] ?? null
          ));
        }

        // 更新最后使用时间
        UserAiToken::touchLastUsed($tokenExtract['token']);
      }
    }

    // 创建 MCP Server 实例
    $mcpServer = new McpServer();

    if ($tokenInfo !== null) {
      $mcpServer->setTokenInfo($tokenInfo);
    }

    // 处理请求
    $result = $mcpServer->handleRequest($jsonRequest);

    if ($isNotification) {
      return $this->acceptedNotificationResponse($response);
    }

    return $this->jsonResponse($response, $result);
  }

  /**
   * 处理批量请求
   *
   * @param Response $response 响应对象
   * @param array $requests 请求数组
   * @return Response
   */
  private function handleBatchRequest(Response $response, array $requests): Response
  {
    // 限制单次批量请求的最大数量，防止绕过频率限制
    if (count($requests) > self::MAX_BATCH_REQUESTS) {
      return $this->jsonResponse($response, McpError::createResponse(
        McpError::INVALID_REQUEST,
        '批量请求数量超出限制（最多 ' . self::MAX_BATCH_REQUESTS . ' 个），当前: ' . count($requests)
      ));
    }

    $results = [];

    foreach ($requests as $jsonRequest) {
      $method = $jsonRequest['method'] ?? '';
      $isNotification = $this->isNotification($jsonRequest);
      $tokenInfo = null;

      if ($this->shouldValidateToken($method)) {
        $tokenExtract = $this->extractTokenFromGlobals();
        $tokenInfo = $tokenExtract === null ? null : $this->verifyToken($tokenExtract);
        if ($tokenInfo === null) {
          if (!$isNotification) {
            $results[] = McpError::createResponse(
              McpError::TOKEN_INVALID,
              'Token 无效或已过期',
              null,
              $jsonRequest['id'] ?? null
            );
          }

          continue;
        }

        // 频率限制检查（仅对外部 ai_token；user_token/guest 为 Agent 内部回环，不受此限）
        if ($tokenExtract !== null && in_array($tokenExtract['type'], ['bearer', 'api_token'], true)) {
          $rateLimit = UserAiToken::checkRateLimit($tokenExtract['token']);
          if (!$rateLimit['allowed']) {
            if (!$isNotification) {
              $results[] = McpError::createResponse(
                McpError::RATE_LIMITED,
                '请求频率超限，请稍后重试',
                [
                  'reset_at'   => $rateLimit['reset_at'],
                  'retry_after' => $rateLimit['reset_at'] - time(),
                ],
                $jsonRequest['id'] ?? null
              );
            }

            continue;
          }

          // 更新最后使用时间
          UserAiToken::touchLastUsed($tokenExtract['token']);
        }
      }

      $mcpServer = new McpServer();
      if ($tokenInfo !== null) {
        $mcpServer->setTokenInfo($tokenInfo);
      }

      $result = $mcpServer->handleRequest($jsonRequest);
      if (!$isNotification) {
        $results[] = $result;
      }
    }

    if ($results === []) {
      return $this->acceptedNotificationResponse($response);
    }

    return $this->jsonResponse($response, $results);
  }

  /**
   * 是否需要鉴权
   *
   * @param string $method MCP 方法名
   * @return bool
   */
  private function shouldValidateToken(string $method): bool
  {
    return !in_array($method, ['initialize', 'notifications/initialized', 'ping'], true);
  }

  /**
   * 判断请求是否为 JSON-RPC notification
   *
   * @param array $jsonRequest JSON 请求体
   * @return bool
   */
  private function isNotification(array $jsonRequest): bool
  {
    return !array_key_exists('id', $jsonRequest);
  }

  /**
   * 验证 Token（按提取类型分发）
   *
   * @param array $tokenExtract extractToken 返回的结构 {type, token, [item_id]}
   * @return array|null Token 信息，无效返回 null
   */
  private function verifyToken(array $tokenExtract): ?array
  {
    $type = $tokenExtract['type'] ?? '';
    $token = $tokenExtract['token'] ?? '';

    if ($type === 'guest') {
      return $this->verifyGuestToken($token, (int) ($tokenExtract['item_id'] ?? 0));
    }

    if ($type === 'user_token') {
      return $this->verifyUserToken($token);
    }

    // bearer / api_token 均为 ai_token
    return $this->verifyAiToken($token);
  }

  /**
   * 验证 ai_token（带 Redis 缓存）
   *
   * @param string $token Token 字符串
   * @return array|null Token 信息，无效返回 null
   */
  private function verifyAiToken(string $token): ?array
  {
    // 验证 Token 格式
    if (!UserAiToken::isValidTokenFormat($token)) {
      return null;
    }

    // 查 Redis 缓存
    $cacheKey = 'mcp_token_cache:' . md5($token);
    $cache = CacheManager::getInstance();
    $cached = $cache->get($cacheKey);

    if ($cached !== null) {
      // 缓存中标记为无效的 Token
      if ($cached === false) {
        return null;
      }
      return $cached;
    }

    // 查数据库
    $tokenInfo = UserAiToken::getToken($token);

    // 写入缓存（有效 token 缓存 300 秒，无效 token 也缓存 300 秒防止穿透）
    $cache->set($cacheKey, $tokenInfo ?? false, 300);

    return $tokenInfo;
  }

  /**
   * 验证 showdoc user_token（AI Agent 回环调用时传的是登录用户 user_token）
   *
   * 验证通过后构造与 ai_token 相同结构的返回数组，授予与登录用户匹配的权限。
   * Handler 层的 requireReadPermission/requireWritePermission/requireManagePermission
   * 会基于真实 uid 的项目角色（owner/admin/editor/readonly）做二次校验。
   *
   * @param string $token 登录 user_token
   * @return array|null
   */
  private function verifyUserToken(string $token): ?array
  {
    $cacheKey = 'mcp_user_token_cache:' . md5($token);
    $cache = CacheManager::getInstance();
    $cached = $cache->get($cacheKey);

    if ($cached !== null) {
      if ($cached === false) {
        return null;
      }
      return $cached;
    }

    $row = UserToken::getToken($token);

    if (!$row) {
      $cache->set($cacheKey, false, 60);
      return null;
    }

    // 检查过期
    $expire = (int) ($row['token_expire'] ?? 0);
    if ($expire > 0 && $expire < time()) {
      $cache->set($cacheKey, false, 60);
      return null;
    }

    $uid = (int) ($row['uid'] ?? 0);
    if ($uid <= 0) {
      $cache->set($cacheKey, false, 60);
      return null;
    }

    // 系统管理员（groupid=1）→ admin；其他已登录用户 → write
    // 项目级管理权限由 Handler 层 requireManagePermission 通过 canManageItem（项目角色）二次校验
    $user = User::findById($uid);
    $isSystemAdmin = $user && (int) ($user->groupid ?? 0) === 1;

    $tokenInfo = [
      'uid'             => $uid,
      'token'           => $token,
      'scope'           => 'all',
      'permission'      => $isSystemAdmin ? 'admin' : 'write',
      'token_type'      => 'user_token',
      'allowed_items'   => '[]',
      'can_create_item' => true,
      'can_delete_item' => true,
    ];

    $cache->set($cacheKey, $tokenInfo, 60);

    return $tokenInfo;
  }

  /**
   * 验证游客 Token
   *
   * 从 ai_chat_sessions 表查询 guest_token + item_id 的绑定关系，验证 session 存在且未删除。
   * 游客仅授予只读权限，限定在单个项目。
   *
   * @param string $guestToken 游客 token
   * @param int $itemId 项目 ID
   * @return array|null
   */
  private function verifyGuestToken(string $guestToken, int $itemId): ?array
  {
    if ($guestToken === '' || $itemId <= 0) {
      return null;
    }

    $cacheKey = 'mcp_guest_token_cache:' . md5($guestToken . ':' . $itemId);
    $cache = CacheManager::getInstance();
    $cached = $cache->get($cacheKey);

    if ($cached !== null) {
      if ($cached === false) {
        return null;
      }
      return $cached;
    }

    $session = AiChatSession::getActiveSessionForGuest($guestToken, $itemId);

    if (!$session) {
      $cache->set($cacheKey, false, 60);
      return null;
    }

    $tokenInfo = [
      'uid'             => 0,
      'token'           => $guestToken,
      'scope'           => 'single_item',
      'permission'      => 'read',
      'allowed_items'   => json_encode([$itemId]),
      'can_create_item' => false,
      'can_delete_item' => false,
      'auth_type'       => 'guest',
    ];

    $cache->set($cacheKey, $tokenInfo, 60);

    return $tokenInfo;
  }


  /**
   * 从请求中提取 Token（含游客 / user_token 分支）
   *
   * 返回结构：
   *  - 游客：['type' => 'guest', 'token' => $guestToken, 'item_id' => $itemId]
   *  - 其他：['type' => 'bearer'|'api_token'|'user_token', 'token' => $tokenString]
   *
   * @param Request $request 请求对象
   * @return array|null
   */
  private function extractToken(Request $request): ?array
  {
    $guestToken = trim($request->getHeaderLine('X-Guest-Token'));
    $guestItemId = (int) trim($request->getHeaderLine('X-Guest-Item-Id'));
    if ($guestToken !== '' && $guestItemId > 0) {
      return ['type' => 'guest', 'token' => $guestToken, 'item_id' => $guestItemId];
    }

    // 1. 从 Authorization Header 获取
    $auth = $request->getHeaderLine('Authorization');
    if ($auth !== '' && preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
      return ['type' => 'bearer', 'token' => trim($m[1])];
    }

    // 2. 从 X-API-Token Header 获取
    $token = trim($request->getHeaderLine('X-API-Token'));
    if ($token !== '') {
      return ['type' => 'api_token', 'token' => $token];
    }

    // 3. 从 X-User-Token Header 获取（AI Agent 内部回环：登录用户 user_token）
    $token = trim($request->getHeaderLine('X-User-Token'));
    if ($token !== '') {
      return ['type' => 'user_token', 'token' => $token];
    }

    return null;
  }

  /**
   * 从全局变量提取 Token（用于批量请求）
   *
   * @return array|null
   */
  private function extractTokenFromGlobals(): ?array
  {
    $guestToken = trim($_SERVER['HTTP_X_GUEST_TOKEN'] ?? '');
    $guestItemId = (int) trim($_SERVER['HTTP_X_GUEST_ITEM_ID'] ?? '0');
    if ($guestToken !== '' && $guestItemId > 0) {
      return ['type' => 'guest', 'token' => $guestToken, 'item_id' => $guestItemId];
    }

    // 1. 从 Authorization Header 获取
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($auth !== '' && preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
      return ['type' => 'bearer', 'token' => trim($m[1])];
    }

    // 2. 从 X-API-Token Header 获取
    $token = trim($_SERVER['HTTP_X_API_TOKEN'] ?? '');
    if ($token !== '') {
      return ['type' => 'api_token', 'token' => $token];
    }

    // 3. 从 X-User-Token Header 获取
    $token = trim($_SERVER['HTTP_X_USER_TOKEN'] ?? '');
    if ($token !== '') {
      return ['type' => 'user_token', 'token' => $token];
    }

    return null;
  }

  /**
   * 返回 JSON 响应
   *
   * @param Response $response 响应对象
   * @param array $data 响应数据
   * @return Response
   */
  private function jsonResponse(Response $response, array $data): Response
  {
    $data = $this->convertLargeIntegersToString($data);
    $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $response->getBody()->write($payload);

    return $this->withCorsHeaders(
      $response->withHeader('Content-Type', 'application/json')
    );
  }

  /**
   * 返回 notification 接收成功响应
   *
   * @param Response $response 响应对象
   * @return Response
   */
  private function acceptedNotificationResponse(Response $response): Response
  {
    return $this->withCorsHeaders($response)->withStatus(202);
  }

  /**
   * 附加 CORS 响应头
   *
   * @param Response $response 响应对象
   * @return Response
   */
  private function withCorsHeaders(Response $response): Response
  {
    return $response
      ->withHeader('Access-Control-Allow-Origin', '*')
      ->withHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
      ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Token, X-User-Token, X-Guest-Token, X-Guest-Item-Id');
  }

  /**
   * 处理 OPTIONS 预检请求
   *
   * @param Request $request 请求对象
   * @param Response $response 响应对象
   * @return Response
   */
  public function options(Request $request, Response $response): Response
  {
    return $this->withCorsHeaders($response)->withStatus(204);
  }
}
