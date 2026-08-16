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

// 安全加固：MCP 是固定入口，忽略请求 query string。
// 否则客户端可用 ?s=Api/xxx/xxx 覆盖上面的 REQUEST_URI，
// 使 server/index.php 的通用路由把请求分发给任意控制器/方法，绕过 MCP 鉴权。
$_SERVER['QUERY_STRING'] = '';
unset($_GET['s']);

// --- 兼容 GET：客户端 SSE 连接握手 ---
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    echo ": connected\n\n";
    ob_flush();
    flush();
    exit;
}

// 注意：不覆盖 REQUEST_METHOD，保持客户端原始方法（POST）
// 注意：不覆盖 POST body，MCP 参数在请求体中传递

// 加载主入口文件
require __DIR__ . '/server/index.php';
