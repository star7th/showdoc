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

// --- 兼容 GET：MCP Streamable HTTP 的 SSE 握手 ---
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    // 构造绝对入口 URL，用于 SSE endpoint 事件
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['REQUEST_SCHEME'] ?? '') === 'https'
        || ($_SERVER['SERVER_PORT'] ?? '') == 443;
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/mcp.php');
    if ($script === '' || $script[0] !== '/') {
        $script = '/' . $script;
    }
    $endpoint = $scheme . '://' . $host . $script;

    // 按 Streamable HTTP 规范返回 endpoint 事件，并保持连接发送心跳注释
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');

    echo "event: endpoint\n";
    echo "data: " . $endpoint . "\n\n";
    ob_flush();
    flush();

    // 保持连接，直到客户端断开或超过最长保活时间（防止连接被无限占用）
    ignore_user_abort(true);
    $maxKeepAliveSeconds = 300;
    $startedAt = time();
    while (!connection_aborted() && (time() - $startedAt) < $maxKeepAliveSeconds) {
        echo ": keep-alive\n\n";
        ob_flush();
        flush();
        sleep(15);
    }
    exit;
}

// 注意：不覆盖 REQUEST_METHOD，保持客户端原始方法（POST）
// 注意：不覆盖 POST body，MCP 参数在请求体中传递

// 加载主入口文件
require __DIR__ . '/server/index.php';
