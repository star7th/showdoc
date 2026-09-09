<?php

namespace Tests\Agent;

use PHPUnit\Framework\TestCase;
use App\Mcp\McpServer;
use App\Mcp\McpError;

/**
 * MCP 协议层测试（开源版）
 *
 * 直接实例化 McpServer，构造 JSON-RPC 请求数组，验证返回的响应结构。
 * 不依赖 HTTP，不依赖 Handler 实际执行（tools/call 只测路由和权限拦截）。
 *
 * 覆盖用例：
 *   TC-090  MCP JSON-RPC 协议
 *   TC-180  resources/list
 *   TC-181  resources/read URI 路由
 *   TC-182  resources/templates/list
 *   TC-183  prompts/list
 *   TC-184  prompts/get 消息构建
 *
 * 与主版的差异（已移除积分/权限相关测试）：
 *   - 移除 TC-091 MCP 认证（checkToolPermission）全部用例
 *     （开源版已删除 McpServer::checkToolPermission 方法，
 *      权限校验下沉到 Handler，且不再有 read/write/admin 层级模型，
 *      原 TC-091 断言的「权限不足」级别语义已不适用）
 *   - tools/list 断言数量为 59（开源版实际工具数：原 58 + 新增 search_help_docs；旧断言 53 为历史遗留未随工具增加更新）
 */
class McpProtocolTest extends TestCase
{
    /** @var McpServer */
    private McpServer $server;

    protected function setUp(): void
    {
        $this->server = new McpServer();
    }

    // ==================================================================
    //  Helper：构造 JSON-RPC 请求并发送
    // ==================================================================

    /**
     * 构造一个标准 JSON-RPC 2.0 请求并交给 McpServer 处理
     */
    private function send(string $method, array $params = [], $id = 'test-1'): array
    {
        return $this->server->handleRequest([
            'jsonrpc' => '2.0',
            'id'      => $id,
            'method'  => $method,
            'params'  => $params,
        ]);
    }

    /**
     * 构造一个不带 id 的通知（或自定义 jsonrpc 版本）
     */
    private function sendRaw(array $request): array
    {
        return $this->server->handleRequest($request);
    }

    /**
     * 设置 Token 信息（供 Handler 内部权限校验使用）
     */
    private function setToken(array $tokenInfo): void
    {
        $this->server->setTokenInfo($tokenInfo);
    }

    // ==================================================================
    //  TC-090  MCP JSON-RPC 协议
    // ==================================================================

    /**
     * TC-090.1 initialize 返回 protocolVersion / capabilities / serverInfo
     */
    public function testInitializeReturnsProtocolInfo(): void
    {
        $resp = $this->send('initialize');

        $this->assertEquals('2.0', $resp['jsonrpc']);
        $this->assertEquals('test-1', $resp['id']);
        $this->assertArrayHasKey('result', $resp);

        $result = $resp['result'];
        $this->assertArrayHasKey('protocolVersion', $result);
        $this->assertNotEmpty($result['protocolVersion']);

        $this->assertArrayHasKey('capabilities', $result);
        $this->assertIsArray($result['capabilities']);

        $this->assertArrayHasKey('serverInfo', $result);
        $this->assertArrayHasKey('name', $result['serverInfo']);
        $this->assertArrayHasKey('version', $result['serverInfo']);
    }

    /**
     * TC-090.2 tools/list 返回全部 59 个工具
     */
    public function testToolsListReturnsAllTools(): void
    {
        $resp = $this->send('tools/list');

        $this->assertArrayHasKey('result', $resp);
        $this->assertArrayHasKey('tools', $resp['result']);

        $this->assertCount(59, $resp['result']['tools']);

        // 每个工具必须有 name / description / inputSchema
        foreach ($resp['result']['tools'] as $tool) {
            $this->assertArrayHasKey('name', $tool);
            $this->assertArrayHasKey('description', $tool);
            $this->assertArrayHasKey('inputSchema', $tool);
        }
    }

    /**
     * TC-090.3 tools/call 路由到正确的 Handler（通过权限拦截验证路由逻辑）
     *
     * 不需要真实 DB——我们用 admin token 让权限通过，
     * 然后检查返回的不是 METHOD_NOT_FOUND 即可（Handler 执行失败的 DB 错误不影响路由验证）。
     * 更好的做法：用无权限 token，预期返回权限错误（证明路由命中了正确的 tool）。
     */
    public function testToolsCallRoutesToHandler(): void
    {
        // 用 read-only token 调用 write 级工具，预期权限拒绝
        $this->setToken([
            'uid'        => 1,
            'permission' => 'read',
            'scope'      => 'all',
            'token_type' => 'ai_token',
        ]);

        $resp = $this->send('tools/call', [
            'name'      => 'create_item',
            'arguments' => ['item_name' => 'test'],
        ]);

        // 应该返回权限错误，不是 "Tool 不存在"
        $this->assertArrayHasKey('error', $resp);
        $this->assertNotEquals(
            McpError::METHOD_NOT_FOUND,
            $resp['error']['code'],
            'create_item 应该被路由到 handler，而非返回 METHOD_NOT_FOUND'
        );
    }

    /**
     * TC-090.4 resources/list 返回资源列表
     */
    public function testResourcesListReturnsResources(): void
    {
        $resp = $this->send('resources/list');

        $this->assertArrayHasKey('result', $resp);
        $this->assertArrayHasKey('resources', $resp['result']);
        $this->assertNotEmpty($resp['result']['resources']);
    }

    /**
     * TC-090.5 prompts/list 返回预定义 prompt 列表
     */
    public function testPromptsListReturnsPrompts(): void
    {
        $resp = $this->send('prompts/list');

        $this->assertArrayHasKey('result', $resp);
        $this->assertArrayHasKey('prompts', $resp['result']);
        $this->assertNotEmpty($resp['result']['prompts']);
    }

    /**
     * TC-090.6 ping 返回空成功响应
     */
    public function testPingReturnsEmptySuccess(): void
    {
        $resp = $this->send('ping');

        $this->assertEquals('2.0', $resp['jsonrpc']);
        $this->assertEquals('test-1', $resp['id']);
        $this->assertArrayHasKey('result', $resp);
        // ping 返回空对象 (stdClass)
        // McpServer 使用 (object) [] 构造空结果，PHP 中 empty((object)[]) === false
        $this->assertTrue(
            is_object($resp['result']) || is_array($resp['result']),
            'ping 应返回空成功响应对象'
        );
    }

    /**
     * TC-090.7 无效 JSON-RPC 版本返回 INVALID_REQUEST
     */
    public function testInvalidJsonRpcVersionReturnsInvalidRequest(): void
    {
        $resp = $this->sendRaw([
            'jsonrpc' => '1.0',
            'id'      => 'bad',
            'method'  => 'initialize',
        ]);

        $this->assertArrayHasKey('error', $resp);
        $this->assertEquals(McpError::INVALID_REQUEST, $resp['error']['code']);
    }

    /**
     * TC-090.8 未知方法返回 METHOD_NOT_FOUND
     */
    public function testUnknownMethodReturnsMethodNotFound(): void
    {
        $resp = $this->send('nonexistent/method');

        $this->assertArrayHasKey('error', $resp);
        $this->assertEquals(McpError::METHOD_NOT_FOUND, $resp['error']['code']);
    }

    // ==================================================================
    //  TC-180  resources/list
    // ==================================================================

    /**
     * TC-180.1 返回全部 8 个预定义资源
     */
    public function testResourcesListCount(): void
    {
        $resp = $this->send('resources/list');

        $this->assertCount(8, $resp['result']['resources']);
    }

    /**
     * TC-180.2 每个资源包含 uri / name / description / mimeType
     */
    public function testResourcesListStructure(): void
    {
        $resp = $this->send('resources/list');

        foreach ($resp['result']['resources'] as $resource) {
            $this->assertArrayHasKey('uri', $resource);
            $this->assertArrayHasKey('name', $resource);
            $this->assertArrayHasKey('description', $resource);
            $this->assertArrayHasKey('mimeType', $resource);
        }
    }

    /**
     * TC-180.3 URI 以 showdoc:// 前缀开头
     */
    public function testResourcesListUriPrefix(): void
    {
        $resp = $this->send('resources/list');

        foreach ($resp['result']['resources'] as $resource) {
            $this->assertStringStartsWith(
                'showdoc://',
                $resource['uri'],
                "URI {$resource['uri']} 应以 showdoc:// 开头"
            );
        }
    }

    // ==================================================================
    //  TC-181  resources/read URI 路由
    // ==================================================================

    /**
     * TC-181.1 无效前缀 → INVALID_PARAMS
     */
    public function testResourcesReadInvalidPrefix(): void
    {
        $resp = $this->send('resources/read', [
            'uri' => 'http://example.com/foo',
        ]);

        $this->assertArrayHasKey('error', $resp);
        $this->assertEquals(McpError::INVALID_PARAMS, $resp['error']['code']);
    }

    /**
     * TC-181.2 不匹配路由 → RESOURCE_NOT_FOUND
     */
    public function testResourcesReadUnmatchedRoute(): void
    {
        $resp = $this->send('resources/read', [
            'uri' => 'showdoc://unknown/path',
        ]);

        $this->assertArrayHasKey('error', $resp);
        $this->assertEquals(McpError::RESOURCE_NOT_FOUND, $resp['error']['code']);
    }

    /**
     * TC-181.3 resources/read 对需要 Handler 的 URI 做路由验证
     *
     * 由于缺少真实 DB，Handler 执行可能失败（INTERNAL_ERROR 或资源未找到），
     * 但我们能通过错误码区分「路由未匹配」vs「路由匹配但 Handler 执行失败」。
     *
     * 路由匹配成功后，Handler 可能返回：
     *   - INTERNAL_ERROR（DB 连接问题）
     *   - RESOURCE_NOT_FOUND（DB 中无对应记录，如 page_id=1 不存在）
     *   - NOT_ITEM_MEMBER / TOKEN_SCOPE_DENIED 等业务错误
     *
     * 路由未匹配时只返回 RESOURCE_NOT_FOUND 且消息含 "资源不存在: {uri}"
     * 我们通过检查错误消息区分这两种情况。
     */
    public function testResourcesReadRoutesToHandler(): void
    {
        $this->setToken([
            'uid'        => 1,
            'permission' => 'write',
            'scope'      => 'all',
            'token_type' => 'user_token',
        ]);

        // 路由匹配的 URI：Handler 执行后不应返回 INVALID_PARAMS（无效 URI 格式）
        // 也不应返回「路由未匹配」类型的 RESOURCE_NOT_FOUND（消息含特定文本）
        $uris = [
            'showdoc://items',
            'showdoc://items/1',
            'showdoc://items/1/catalogs',
            'showdoc://items/1/pages',
            'showdoc://pages/1',
            'showdoc://pages/1/history',
            'showdoc://pages/1/versions/1',
            'showdoc://catalogs/1',
        ];

        foreach ($uris as $uri) {
            $resp = $this->send('resources/read', ['uri' => $uri], 'read-' . md5($uri));

            // 关键验证：不应返回 INVALID_PARAMS（URI 格式正确）
            if (isset($resp['error'])) {
                $this->assertNotEquals(
                    McpError::INVALID_PARAMS,
                    $resp['error']['code'],
                    "URI {$uri} 是有效格式，不应返回 INVALID_PARAMS"
                );

                // 如果是 RESOURCE_NOT_FOUND，检查消息不应含「资源不存在: {uri}」
                // 因为那种消息意味着路由本身未匹配（而非 Handler 内部判断资源不存在）
                if ($resp['error']['code'] === McpError::RESOURCE_NOT_FOUND) {
                    $this->assertStringNotContainsString(
                        "资源不存在: {$uri}",
                        $resp['error']['message'],
                        "URI {$uri} 应匹配到路由（不应是路由未匹配的 RESOURCE_NOT_FOUND）"
                    );
                }
            }
        }
    }

    /**
     * TC-181.4 resources/read 不含 token 时应返回错误（不能访问）
     */
    public function testResourcesReadWithoutToken(): void
    {
        // 不设置 token
        $resp = $this->send('resources/read', [
            'uri' => 'showdoc://items',
        ]);

        // Handler 会因缺少 token 抛出异常或返回错误
        // 但路由本身是匹配的（不是 RESOURCE_NOT_FOUND）
        $this->assertNotEquals(
            McpError::RESOURCE_NOT_FOUND,
            $resp['error']['code'] ?? 0,
            'showdoc://items 应匹配到路由'
        );
    }

    // ==================================================================
    //  TC-182  resources/templates/list
    // ==================================================================

    /**
     * TC-182.1 返回含占位符的模板资源（7 个）
     */
    public function testResourcesTemplatesListCount(): void
    {
        $resp = $this->send('resources/templates/list');

        $this->assertArrayHasKey('result', $resp);
        $this->assertArrayHasKey('resourceTemplates', $resp['result']);
        $this->assertCount(7, $resp['result']['resourceTemplates']);
    }

    /**
     * TC-182.2 每个模板包含 uriTemplate / name / description / mimeType
     */
    public function testResourcesTemplatesListStructure(): void
    {
        $resp = $this->send('resources/templates/list');

        foreach ($resp['result']['resourceTemplates'] as $template) {
            $this->assertArrayHasKey('uriTemplate', $template);
            $this->assertArrayHasKey('name', $template);
            $this->assertArrayHasKey('description', $template);
            $this->assertArrayHasKey('mimeType', $template);

            // uriTemplate 应包含 { 占位符
            $this->assertStringContainsString('{', $template['uriTemplate']);
        }
    }

    /**
     * TC-182.3 模板的 uriTemplate 以 showdoc:// 开头
     */
    public function testResourcesTemplatesUriPrefix(): void
    {
        $resp = $this->send('resources/templates/list');

        foreach ($resp['result']['resourceTemplates'] as $template) {
            $this->assertStringStartsWith('showdoc://', $template['uriTemplate']);
        }
    }

    // ==================================================================
    //  TC-183  prompts/list
    // ==================================================================

    /**
     * TC-183.1 返回全部 9 个预定义 prompt
     */
    public function testPromptsListCount(): void
    {
        $resp = $this->send('prompts/list');

        $this->assertArrayHasKey('result', $resp);
        $this->assertArrayHasKey('prompts', $resp['result']);
        $this->assertCount(9, $resp['result']['prompts']);
    }

    /**
     * TC-183.2 每个 prompt 包含 name / description / arguments
     */
    public function testPromptsListStructure(): void
    {
        $resp = $this->send('prompts/list');

        foreach ($resp['result']['prompts'] as $prompt) {
            $this->assertArrayHasKey('name', $prompt);
            $this->assertArrayHasKey('description', $prompt);
            $this->assertArrayHasKey('arguments', $prompt);
            $this->assertIsArray($prompt['arguments']);
        }
    }

    /**
     * TC-183.3 generate_client_code 的 arguments 含 page_id(required=true)
     */
    public function testGenerateClientCodePromptHasPageIdRequired(): void
    {
        $resp = $this->send('prompts/list');

        $found = false;
        foreach ($resp['result']['prompts'] as $prompt) {
            if ($prompt['name'] === 'generate_client_code') {
                $found = true;
                $pageIdArg = null;
                foreach ($prompt['arguments'] as $arg) {
                    if ($arg['name'] === 'page_id') {
                        $pageIdArg = $arg;
                        break;
                    }
                }
                $this->assertNotNull($pageIdArg, 'generate_client_code 应有 page_id 参数');
                $this->assertTrue($pageIdArg['required'], 'page_id 应为 required');
                break;
            }
        }
        $this->assertTrue($found, '应存在 generate_client_code prompt');
    }

    // ==================================================================
    //  TC-184  prompts/get 消息构建
    // ==================================================================

    /**
     * TC-184.1 generate_client_code 返回正确的引导消息
     */
    public function testPromptsGetGenerateClientCode(): void
    {
        $resp = $this->send('prompts/get', [
            'name'      => 'generate_client_code',
            'arguments' => [
                'page_id'   => '123',
                'language'  => 'python',
                'framework' => 'requests',
            ],
        ]);

        $this->assertArrayHasKey('result', $resp);
        $this->assertArrayHasKey('messages', $resp['result']);
        $this->assertNotEmpty($resp['result']['messages']);

        $text = $resp['result']['messages'][0]['content']['text'] ?? '';
        $this->assertStringContainsString('python', $text);
        $this->assertStringContainsString('123', $text);
    }

    /**
     * TC-184.2 generate_docs_from_code 返回包含代码片段的消息
     */
    public function testPromptsGetGenerateDocsFromCode(): void
    {
        $resp = $this->send('prompts/get', [
            'name'      => 'generate_docs_from_code',
            'arguments' => [
                'code_snippet' => 'function test() {}',
                'doc_type'     => 'markdown',
            ],
        ]);

        $this->assertArrayHasKey('result', $resp);
        $text = $resp['result']['messages'][0]['content']['text'] ?? '';
        $this->assertStringContainsString('function test()', $text);
        $this->assertStringContainsString('markdown', $text);
    }

    /**
     * TC-184.3 kanban_pick_task 返回包含项目 ID 的消息
     */
    public function testPromptsGetKanbanPickTask(): void
    {
        $resp = $this->send('prompts/get', [
            'name'      => 'kanban_pick_task',
            'arguments' => [
                'item_id' => '42',
            ],
        ]);

        $this->assertArrayHasKey('result', $resp);
        $text = $resp['result']['messages'][0]['content']['text'] ?? '';
        $this->assertStringContainsString('42', $text);
        $this->assertStringContainsString('kanban_get_board', $text);
    }

    /**
     * TC-184.4 所有 prompt 都能正确构建消息
     */
    public function testAllPromptsBuildMessages(): void
    {
        $promptsToTest = [
            ['name' => 'generate_client_code', 'arguments' => ['page_id' => '1']],
            ['name' => 'generate_server_code', 'arguments' => ['page_id' => '1']],
            ['name' => 'generate_docs_from_code', 'arguments' => ['code_snippet' => 'echo 1;']],
            ['name' => 'sync_api_docs', 'arguments' => ['item_id' => '1']],
            ['name' => 'compare_impl_and_doc', 'arguments' => ['page_id' => '1', 'code_path' => '/tmp/x.php']],
            ['name' => 'suggest_doc_structure', 'arguments' => ['item_id' => '1']],
            ['name' => 'find_outdated_docs', 'arguments' => ['item_id' => '1']],
            ['name' => 'kanban_pick_task', 'arguments' => ['item_id' => '1']],
            ['name' => 'kanban_report_progress', 'arguments' => ['page_id' => '1', 'status' => 'done']],
        ];

        foreach ($promptsToTest as $test) {
            $resp = $this->send('prompts/get', $test, 'prompt-' . $test['name']);

            $this->assertArrayHasKey('result', $resp, "prompt {$test['name']} 应返回 result");
            $this->assertArrayHasKey('messages', $resp['result'], "prompt {$test['name']} 应有 messages");
            $this->assertNotEmpty($resp['result']['messages'], "prompt {$test['name']} messages 不为空");

            // 验证消息结构
            foreach ($resp['result']['messages'] as $msg) {
                $this->assertArrayHasKey('role', $msg);
                $this->assertArrayHasKey('content', $msg);
                $this->assertArrayHasKey('type', $msg['content']);
                $this->assertArrayHasKey('text', $msg['content']);
                $this->assertNotEmpty($msg['content']['text']);
            }
        }
    }

    /**
     * TC-184.5 不存在的 prompt name → METHOD_NOT_FOUND
     */
    public function testPromptsGetUnknownPrompt(): void
    {
        $resp = $this->send('prompts/get', [
            'name'      => 'nonexistent_prompt',
            'arguments' => [],
        ]);

        $this->assertArrayHasKey('error', $resp);
        $this->assertEquals(McpError::METHOD_NOT_FOUND, $resp['error']['code']);
    }

    // ==================================================================
    //  额外：notifications/initialized
    // ==================================================================

    /**
     * notifications/initialized 返回成功响应
     */
    public function testInitializedNotification(): void
    {
        $resp = $this->send('notifications/initialized');

        $this->assertArrayHasKey('result', $resp);
    }
}
