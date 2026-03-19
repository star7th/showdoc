<?php

namespace App\Mcp;

/**
 * MCP 异常类
 * 
 * 用于 MCP 操作中抛出的异常，携带错误码和附加数据
 */
class McpException extends \Exception
{
  /**
   * 附加数据
   *
   * @var mixed
   */
  protected $data;

  /**
   * 构造函数
   *
   * @param int $code 错误码
   * @param string $message 错误消息
   * @param mixed $data 附加数据
   */
  public function __construct(int $code, string $message = '', $data = null)
  {
    parent::__construct($message, $code);
    $this->data = $data;
  }

  /**
   * 获取附加数据
   *
   * @return mixed
   */
  public function getData()
  {
    return $this->data;
  }

  /**
   * 转换为 JSON-RPC 错误响应数组
   *
   * @param int|string|null $id 请求 ID
   * @return array
   */
  public function toResponse($id = null): array
  {
    return McpError::createResponse($this->code, $this->message, $this->data, $id);
  }
}
