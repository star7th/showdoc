<?php

namespace App\Mcp;

/**
 * MCP 错误处理类
 * 
 * 定义 MCP 协议相关的错误码和错误消息
 * 
 * JSON-RPC 2.0 标准错误码范围：-32700 ~ -32603
 * ShowDoc MCP 自定义错误码范围：-32001 ~ -32011
 */
class McpError
{
  // JSON-RPC 标准错误码
  const PARSE_ERROR = -32700;           // JSON 解析失败
  const INVALID_REQUEST = -32600;       // 请求对象无效
  const METHOD_NOT_FOUND = -32601;      // 方法不存在
  const INVALID_PARAMS = -32602;        // 参数无效
  const INTERNAL_ERROR = -32603;        // 服务器内部错误

  // ShowDoc MCP 自定义错误码
  const TOKEN_INVALID = -32001;         // Token 无效或已过期
  const TOKEN_SCOPE_DENIED = -32002;    // 项目不在 Token 范围内
  const NOT_ITEM_MEMBER = -32003;       // 用户不是项目成员
  const NO_EDIT_PERMISSION = -32004;    // 用户无项目编辑权限
  const TOKEN_OPERATION_DENIED = -32005; // Token 不允许此操作
  const RESOURCE_NOT_FOUND = -32006;    // 资源不存在
  const CONTENT_TOO_LARGE = -32007;     // 内容超限
  const RATE_LIMITED = -32008;          // 频率限制
  const VERSION_CONFLICT = -32009;      // 版本冲突（乐观锁）
  const OPERATION_FAILED = -32010;      // 操作失败
  const VALIDATION_ERROR = -32011;      // 参数校验失败

  /**
   * 错误码与消息映射
   */
  private static array $messages = [
    // JSON-RPC 标准错误
    self::PARSE_ERROR => 'JSON 解析失败',
    self::INVALID_REQUEST => '请求对象无效',
    self::METHOD_NOT_FOUND => '方法不存在',
    self::INVALID_PARAMS => '参数无效',
    self::INTERNAL_ERROR => '服务器内部错误',

    // ShowDoc MCP 自定义错误
    self::TOKEN_INVALID => 'Token 无效或已过期',
    self::TOKEN_SCOPE_DENIED => '项目不在 Token 的权限范围内',
    self::NOT_ITEM_MEMBER => '您不是该项目的成员',
    self::NO_EDIT_PERMISSION => '权限不足：您在该项目中无编辑权限',
    self::TOKEN_OPERATION_DENIED => 'Token 不允许执行此操作',
    self::RESOURCE_NOT_FOUND => '资源不存在',
    self::CONTENT_TOO_LARGE => '内容超出限制',
    self::RATE_LIMITED => '请求频率超限，请稍后重试',
    self::VERSION_CONFLICT => '版本冲突：文档已被其他人修改',
    self::OPERATION_FAILED => '操作失败',
    self::VALIDATION_ERROR => '参数校验失败',
  ];

  /**
   * 获取错误消息
   *
   * @param int $code 错误码
   * @return string 错误消息
   */
  public static function getMessage(int $code): string
  {
    return self::$messages[$code] ?? '未知错误';
  }

  /**
   * 创建 JSON-RPC 错误响应
   *
   * @param int $code 错误码
   * @param string|null $message 自定义错误消息
   * @param mixed $data 附加数据
   * @param int|string|null $id 请求 ID
   * @return array JSON-RPC 错误响应数组
   */
  public static function createResponse(int $code, ?string $message = null, $data = null, $id = null): array
  {
    $error = [
      'code' => $code,
      'message' => $message ?? self::getMessage($code),
    ];

    if ($data !== null) {
      $error['data'] = $data;
    }

    return [
      'jsonrpc' => '2.0',
      'id' => $id,
      'error' => $error,
    ];
  }

  /**
   * 创建成功响应
   *
   * @param mixed $result 结果数据
   * @param int|string|null $id 请求 ID
   * @return array JSON-RPC 成功响应数组
   */
  public static function createSuccessResponse($result, $id = null): array
  {
    return [
      'jsonrpc' => '2.0',
      'id' => $id,
      'result' => $result,
    ];
  }

  /**
   * 抛出 MCP 异常
   *
   * @param int $code 错误码
   * @param string|null $message 自定义错误消息
   * @param mixed $data 附加数据
   * @throws McpException
   */
  public static function throw(int $code, ?string $message = null, $data = null): void
  {
    throw new McpException($code, $message ?? self::getMessage($code), $data);
  }
}
