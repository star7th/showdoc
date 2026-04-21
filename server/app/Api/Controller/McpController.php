<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Mcp\McpServer;
use App\Mcp\McpError;
use App\Model\UserAiToken;
use App\Common\Helper\IpHelper;
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
   * MCP 入口
   *
   * @param Request $request 请求对象
   * @param Response $response 响应对象
   * @return Response
   */
  public function index(Request $request, Response $response): Response
  {
    // 获取请求体
    $body = (string) $request->getBody();

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
      $tokenInfo = $this->validateToken($request);
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

      // 频率限制检查
      $token = $this->extractToken($request);
      if ($token) {
        $rateLimit = UserAiToken::checkRateLimit($token);
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
      }

      // 更新最后使用时间
      if ($token) {
        UserAiToken::touchLastUsed($token);
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
    $results = [];

    foreach ($requests as $jsonRequest) {
      $method = $jsonRequest['method'] ?? '';
      $isNotification = $this->isNotification($jsonRequest);
      $tokenInfo = null;

      if ($this->shouldValidateToken($method)) {
        $tokenInfo = $this->validateTokenFromGlobals();
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
   * 验证 Token
   *
   * @param Request $request 请求对象
   * @return array|null Token 信息，无效返回 null
   */
  private function validateToken(Request $request): ?array
  {
    $token = $this->extractToken($request);

    if ($token === null) {
      return null;
    }

    // 验证 Token 格式
    if (!UserAiToken::isValidTokenFormat($token)) {
      return null;
    }

    // 获取 Token 信息
    $tokenInfo = UserAiToken::getToken($token);

    return $tokenInfo;
  }

  /**
   * 从全局变量验证 Token（用于批量请求）
   *
   * @return array|null
   */
  private function validateTokenFromGlobals(): ?array
  {
    $token = null;

    // 1. 从 Authorization Header 获取
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($auth !== '' && preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
      $token = trim($m[1]);
    }

    // 2. 从 X-API-Token Header 获取
    if ($token === null) {
      $token = trim($_SERVER['HTTP_X_API_TOKEN'] ?? '');
    }

    // 3. 从查询参数获取
    if ($token === null) {
      $token = trim($_GET['user_token'] ?? '');
    }

    if ($token === '' || $token === null) {
      return null;
    }

    // 验证 Token 格式
    if (!UserAiToken::isValidTokenFormat($token)) {
      return null;
    }

    // 获取 Token 信息
    return UserAiToken::getToken($token);
  }

  /**
   * 从请求中提取 Token
   *
   * @param Request $request 请求对象
   * @return string|null
   */
  private function extractToken(Request $request): ?string
  {
    // 1. 从 Authorization Header 获取
    $auth = $request->getHeaderLine('Authorization');
    if ($auth !== '' && preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
      return trim($m[1]);
    }

    // 2. 从 X-API-Token Header 获取
    $token = trim($request->getHeaderLine('X-API-Token'));
    if ($token !== '') {
      return $token;
    }

    // 3. 从查询参数获取
    $token = trim($this->getParam($request, 'user_token', ''));
    if ($token !== '') {
      return $token;
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
      ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Token');
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
