<?php

/**
 * ShowDoc MCP 独立入口
 * 
 * MCP（Model Context Protocol）服务入口，用于 AI 编辑器集成。
 * 支持 Cursor、Windsurf、Cline、Claude Desktop 等 AI 编辑器。
 * 
 * 访问地址：https://your-showdoc.com/mcp.php
 * 
 * MCP 使用 JSON-RPC 2.0 协议，参数通过 HTTP POST 请求体传递：
 * {
 *   "jsonrpc": "2.0",
 *   "id": 1,
 *   "method": "tools/call",
 *   "params": {
 *     "name": "get_page",
 *     "arguments": { "page_id": 123 }
 *   }
 * }
 * 
 * 认证方式：Authorization: Bearer user_abc123...
 * 
 * @package ShowDoc
 * @author  ShowDoc Team
 * @since   2026-03-18
 */

// MCP 独立入口，主版和开源版统一使用
// 将请求路由到 MCP 控制器
$_SERVER['REQUEST_URI'] = '/server/Api/Mcp/index';

// --- 极简兼容：应付一下客户端的 GET 请求 ---
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
  header('Content-Type: text/event-stream');
  header('Cache-Control: no-cache');
  echo ": connected\n\n"; // 发一个空注释就够了
  ob_flush();
  flush();
  // 直接退出，不需要保持连接（视情况而定）
  exit;
}

// 注意：不覆盖 REQUEST_METHOD，保持客户端原始方法（POST）
// 注意：不覆盖 POST body，MCP 参数在请求体中传递

// 加载主入口文件
require __DIR__ . '/server/index.php';
