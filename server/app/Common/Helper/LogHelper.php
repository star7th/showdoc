<?php

namespace App\Common\Helper;

/**
 * 日志辅助类
 *
 * 统一处理日志记录，将日志写入到项目目录下的 Runtime/Logs 目录
 */
class LogHelper
{
  /**
   * 获取日志文件路径
   *
   * @param string $module 模块名称（如 'Api', 'Home'），用于分类日志文件
   * @return string 日志文件完整路径
   */
  private static function getLogPath(string $module = 'Common'): string
  {
    // 优先使用已定义的 LOG_PATH 常量（在 server/index.php 中定义）
    if (defined('LOG_PATH')) {
      $logDir = LOG_PATH;
    } else {
      // 如果没有定义，使用相对于 app 目录的路径
      // LogHelper 位于 server/app/Common/Helper/LogHelper.php
      // 所以需要回到 app 目录，然后进入 Runtime/Logs
      $appDir = dirname(dirname(__DIR__)); // 从 Helper 回到 app 目录
      $logDir = $appDir . DIRECTORY_SEPARATOR . 'Runtime' . DIRECTORY_SEPARATOR . 'Logs' . DIRECTORY_SEPARATOR;
    }

    // 确保目录存在
    if (!is_dir($logDir)) {
      @mkdir($logDir, 0755, true);
    }

    // 按日期和模块生成日志文件名
    $date = date('Y_m_d');
    $logFile = $logDir . $module . '_' . $date . '.log';

    return $logFile;
  }

  /**
   * 记录日志
   *
   * @param string $message 日志消息
   * @param string $module 模块名称（可选，默认为 'Common'）
   * @param string $level 日志级别（可选，默认为 'INFO'）
   * @return bool 是否成功写入
   */
  public static function write(string $message, string $module = 'Common', string $level = 'INFO'): bool
  {
    $logFile = self::getLogPath($module);

    // 格式化日志内容：时间戳 [级别] 消息
    $timestamp = date('Y-m-d H:i:s');
    $logContent = sprintf(
      "[%s] [%s] %s\n",
      $timestamp,
      $level,
      $message
    );

    // 写入日志文件（追加模式）
    $result = @file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);

    // 如果写入失败，尝试输出到标准错误（作为后备）
    if ($result === false) {
      error_log($logContent);
    }

    return $result !== false;
  }

  /**
   * 记录错误日志
   *
   * @param string $message 错误消息
   * @param string $module 模块名称（可选）
   * @return bool
   */
  public static function error(string $message, string $module = 'Common'): bool
  {
    return self::write($message, $module, 'ERROR');
  }

  /**
   * 记录警告日志
   *
   * @param string $message 警告消息
   * @param string $module 模块名称（可选）
   * @return bool
   */
  public static function warning(string $message, string $module = 'Common'): bool
  {
    return self::write($message, $module, 'WARNING');
  }

  /**
   * 记录信息日志
   *
   * @param string $message 信息消息
   * @param string $module 模块名称（可选）
   * @return bool
   */
  public static function info(string $message, string $module = 'Common'): bool
  {
    return self::write($message, $module, 'INFO');
  }

  /**
   * 记录调试日志
   *
   * @param string $message 调试消息
   * @param string $module 模块名称（可选）
   * @return bool
   */
  public static function debug(string $message, string $module = 'Common'): bool
  {
    return self::write($message, $module, 'DEBUG');
  }

  /**
   * 记录异常日志
   *
   * @param \Throwable $exception 异常对象
   * @param string $module 模块名称（可选）
   * @return bool
   */
  public static function exception(\Throwable $exception, string $module = 'Common'): bool
  {
    $message = sprintf(
      "Exception: %s\nMessage: %s\nFile: %s:%d\nTrace:\n%s",
      get_class($exception),
      $exception->getMessage(),
      $exception->getFile(),
      $exception->getLine(),
      $exception->getTraceAsString()
    );

    return self::error($message, $module);
  }
}
