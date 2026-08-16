<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * ImportSwaggerController 单元测试
 *
 * 通过反射直接调用私有方法，不依赖数据库和 HTTP 请求。
 * 测试核心逻辑：responseParamsDesc（返回参数描述）和 responseExample（返回示例）的生成。
 */
class ImportSwaggerTest extends TestCase
{
    /** @var object ImportSwaggerController 实例 */
    private $controller;

    /** @var ReflectionClass */
    private $reflection;

    protected function setUp(): void
    {
        // 直接实例化 Controller，不经过 Slim Container
        $this->reflection = new ReflectionClass(
            \App\Api\Controller\ImportSwaggerController::class
        );
        $this->controller = $this->reflection->newInstanceWithoutConstructor();
    }

    // ---------------------------------------------------------------
    //  Helper：反射调用私有方法
    // ---------------------------------------------------------------

    private function invokeMethod(string $name, array $args = [])
    {
        $method = $this->reflection->getMethod($name);
        $method->setAccessible(true);
        $closure = $method->getClosure($this->controller);
        return $closure(...$args);
    }

    private function setPrivateProperty(string $name, $value): void
    {
        $prop = $this->reflection->getProperty($name);
        $prop->setAccessible(true);
        $prop->setValue($this->controller, $value);
    }

    private function getPrivateProperty(string $name)
    {
        $prop = $this->reflection->getProperty($name);
        $prop->setAccessible(true);
        return $prop->getValue($this->controller);
    }

    /**
     * 加载 fixture JSON 并解析为数组
     */
    private function loadFixture(string $filename): array
    {
        $path = __DIR__ . '/fixtures/' . $filename;
        $this->assertFileExists($path, "Fixture file missing: {$filename}");
        $json = json_decode(file_get_contents($path), true);
        $this->assertNotNull($json, "Invalid JSON in fixture: {$filename}");
        return $json;
    }

    /**
     * 初始化 Controller 的 jsonArray 并解析 $ref
     *
     * 这是 import() 方法中 jsonArray 赋值 + transferDefinitionOptimized 的简化版本，
     * 用于给 processXxxResponse 提供正确的运行时状态。
     */
    private function initControllerWithJson(array &$jsonArray): void
    {
        $this->setPrivateProperty('jsonArray', $jsonArray);
        $this->setPrivateProperty('resolvedRefs', []);
        $this->setPrivateProperty('toBRefPath', []);

        // 执行 $ref 替换（与 import() 一致）
        $ref = $this->reflection->getMethod('transferDefinitionOptimized');
        $ref->setAccessible(true);
        $closure = $ref->getClosure($this->controller);
        $closure($jsonArray);

        // 回写 jsonArray（引用传递后 definitions/components 已被替换为真实数据）
        $this->setPrivateProperty('jsonArray', $jsonArray);
    }

    /**
     * 调用 processOpenAPI3Response 并返回 response 部分
     */
    private function processOpenAPI3Response(array $request): array
    {
        $contentArray = ['response' => []];
        $this->invokeMethod('processOpenAPI3Response', [&$request, &$contentArray]);
        return $contentArray['response'];
    }

    /**
     * 调用 processSwagger2Response 并返回 response 部分
     */
    private function processSwagger2Response(array $request): array
    {
        $contentArray = ['response' => []];
        $this->invokeMethod('processSwagger2Response', [&$request, &$contentArray]);
        return $contentArray['response'];
    }

    /**
     * 调用 requestToApi 处理完整接口
     */
    private function requestToApi(string $method, string $url, array $request, array $jsonArray, string $swaggerVersion): array
    {
        return $this->invokeMethod('requestToApi', [$method, $url, $request, $jsonArray, $swaggerVersion]);
    }

    // ===============================================================
    //  OpenAPI 3 — 核心 Bug 修复验证
    // ===============================================================

    /**
     * 【Bug 修复验证】OpenAPI 3 components $ref — schema 的 description 必须出现在 responseParamsDesc
     */
    public function testOpenApi3ComponentsRefParamsDesc(): void
    {
        $jsonArray = $this->loadFixture('runapi-probe-openapi3-components.json');
        $this->initControllerWithJson($jsonArray);

        // 取第一个 path 的第一个 method
        $path = $jsonArray['paths']['/probe/components']['get'];
        $response = $this->processOpenAPI3Response($path);

        $this->assertArrayHasKey('responseParamsDesc', $response, 'responseParamsDesc must exist');
        $desc = $response['responseParamsDesc'];

        // 验证顶层参数有 description → remark
        $remarks = array_column($desc, 'remark', 'name');

        $this->assertEquals('状态码说明', $remarks['code'] ?? '', 'code.remark should come from schema description');
        $this->assertEquals('提示信息说明', $remarks['msg'] ?? '', 'msg.remark should come from schema description');

        // data 的子属性也应该有 description
        $this->assertEquals('ID说明', $remarks['id'] ?? '', 'data.id.remark should come from schema description');
        $this->assertEquals('名称说明', $remarks['name'] ?? '', 'data.name.remark should come from schema description');
    }

    /**
     * 【Bug 修复验证】OpenAPI 3 components $ref — responseExample 应来自 example 字段
     */
    public function testOpenApi3ComponentsRefExample(): void
    {
        $jsonArray = $this->loadFixture('runapi-probe-openapi3-components.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/probe/components']['get'];
        $response = $this->processOpenAPI3Response($path);

        $this->assertArrayHasKey('responseExample', $response, 'responseExample must exist');

        $example = json_decode($response['responseExample'], true);
        $this->assertNotNull($example, 'responseExample should be valid JSON');
        $this->assertEquals(200, $example['code'] ?? null, 'example.code should be 200');
        $this->assertEquals('success', $example['msg'] ?? null, 'example.msg should be "success"');
    }

    /**
     * OpenAPI 3 inline schema — responseParamsDesc 包含 description
     */
    public function testOpenApi3InlineParamsDesc(): void
    {
        $jsonArray = $this->loadFixture('runapi-probe-openapi3-inline.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/probe/inline']['get'];
        $response = $this->processOpenAPI3Response($path);

        $desc = $response['responseParamsDesc'];
        $remarks = array_column($desc, 'remark', 'name');

        $this->assertEquals('状态码说明', $remarks['code'] ?? '', 'code.remark from inline schema');
        $this->assertEquals('提示信息说明', $remarks['msg'] ?? '', 'msg.remark from inline schema');
        $this->assertEquals('数据对象说明', $remarks['data'] ?? '', 'data.remark from inline schema');
        $this->assertEquals('ID说明', $remarks['id'] ?? '', 'data.id.remark from inline schema');
        $this->assertEquals('名称说明', $remarks['name'] ?? '', 'data.name.remark from inline schema');
    }

    /**
     * OpenAPI 3 inline schema — responseExample 来自 example 字段
     */
    public function testOpenApi3InlineExample(): void
    {
        $jsonArray = $this->loadFixture('runapi-probe-openapi3-inline.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/probe/inline']['get'];
        $response = $this->processOpenAPI3Response($path);

        $example = json_decode($response['responseExample'], true);
        $this->assertNotNull($example);
        $this->assertEquals(200, $example['code'] ?? null);
    }

    // ===============================================================
    //  Swagger 2 — 验证
    // ===============================================================

    /**
     * Swagger 2 definitions $ref — responseParamsDesc 包含 description
     */
    public function testSwagger2DefinitionsParamsDesc(): void
    {
        $jsonArray = $this->loadFixture('runapi-probe-swagger2.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/probe/swagger2']['get'];
        $response = $this->processSwagger2Response($path);

        $desc = $response['responseParamsDesc'];
        $remarks = array_column($desc, 'remark', 'name');

        $this->assertEquals('状态码说明', $remarks['code'] ?? '', 'code.remark should come from definitions');
        $this->assertEquals('提示信息说明', $remarks['msg'] ?? '', 'msg.remark should come from definitions');
        $this->assertEquals('ID说明', $remarks['id'] ?? '', 'data.id.remark should come from definitions');
        $this->assertEquals('名称说明', $remarks['name'] ?? '', 'data.name.remark should come from definitions');
    }

    /**
     * Swagger 2 — responseExample 来自 examples 字段
     */
    public function testSwagger2Example(): void
    {
        $jsonArray = $this->loadFixture('runapi-probe-swagger2.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/probe/swagger2']['get'];
        $response = $this->processSwagger2Response($path);

        $example = json_decode($response['responseExample'], true);
        $this->assertNotNull($example);
        $this->assertEquals(200, $example['code'] ?? null);
        $this->assertEquals('success', $example['msg'] ?? null);
    }

    // ===============================================================
    //  回归 — 只有 schema 无 example
    // ===============================================================

    /**
     * 【回归】只有 schema（无 example）— 最常见格式
     * responseParamsDesc 正常提取，responseExample 由 schema 自动生成
     */
    public function testSchemaOnlyResponse(): void
    {
        $jsonArray = $this->loadFixture('openapi3-schema-only.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/users']['get'];
        $response = $this->processOpenAPI3Response($path);

        $desc = $response['responseParamsDesc'];
        $remarks = array_column($desc, 'remark', 'name');

        $this->assertEquals('状态码', $remarks['code'] ?? '', 'code.remark from schema');
        $this->assertEquals('用户列表', $remarks['data'] ?? '', 'data.remark from schema');
        $this->assertEquals('用户ID', $remarks['id'] ?? '', 'items[].id.remark from schema');
        $this->assertEquals('用户名', $remarks['name'] ?? '', 'items[].name.remark from schema');

        // responseExample 应该由 schema 自动生成（toB）
        $this->assertArrayHasKey('responseExample', $response);
        $example = json_decode($response['responseExample'], true);
        $this->assertNotNull($example, 'Auto-generated responseExample should be valid JSON');
        $this->assertArrayHasKey('code', $example, 'Auto-generated example should have code field');
    }

    // ===============================================================
    //  回归 — 只有 example 无 schema
    // ===============================================================

    /**
     * 【回归】只有 example（无 schema）— 降级到 example 提取
     * remark 为空但不应报错
     */
    public function testExampleOnlyResponse(): void
    {
        $jsonArray = $this->loadFixture('openapi3-example-only.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/no-schema']['get'];
        $response = $this->processOpenAPI3Response($path);

        $this->assertArrayHasKey('responseParamsDesc', $response);
        $this->assertArrayHasKey('responseExample', $response);

        // responseParamsDesc 从 example 推导（无 description，remark 为空）
        $desc = $response['responseParamsDesc'];
        $this->assertNotEmpty($desc, 'Should derive params from example');

        // remark 应该为空（example 没有 description 信息）
        foreach ($desc as $param) {
            $this->assertEquals('', $param['remark'] ?? 'NOT_EMPTY', 'remark should be empty when no schema');
        }

        // responseExample 应该是原始 example
        $example = json_decode($response['responseExample'], true);
        $this->assertNotNull($example);
        $this->assertEquals(200, $example['code'] ?? null);
    }

    // ===============================================================
    //  回归 — 204 No Content
    // ===============================================================

    /**
     * 【回归】204 No Content — 无返回参数，不报错
     */
    public function testNoContentResponse(): void
    {
        $jsonArray = $this->loadFixture('openapi3-no-content.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/delete']['delete'];
        $response = $this->processOpenAPI3Response($path);

        // 204 没有 200 响应，response 应为空
        $this->assertEmpty($response, '204 response should produce empty response array');
    }

    // ===============================================================
    //  回归 — 多个 content type
    // ===============================================================

    /**
     * 【回归】多个 content type — application/json 优先
     */
    public function testMultiContentTypeResponse(): void
    {
        $jsonArray = $this->loadFixture('openapi3-multi-content-type.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/multi-content']['get'];
        $response = $this->processOpenAPI3Response($path);

        // 应该从 application/json 提取，不是 text/html
        $desc = $response['responseParamsDesc'];
        $remarks = array_column($desc, 'remark', 'name');
        $this->assertEquals('状态码', $remarks['code'] ?? '', 'Should extract from JSON content type');
        $this->assertEquals('消息', $remarks['msg'] ?? '', 'Should extract from JSON content type');

        // responseExample 来自 example
        $example = json_decode($response['responseExample'], true);
        $this->assertNotNull($example);
        $this->assertEquals(0, $example['code'] ?? null);
    }

    // ===============================================================
    //  回归 — 多层嵌套 $ref
    // ===============================================================

    /**
     * 【回归】多层嵌套 $ref — 每层 description 都应提取到
     */
    public function testNestedRefParamsDesc(): void
    {
        $jsonArray = $this->loadFixture('openapi3-nested-ref.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/nested']['get'];
        $response = $this->processOpenAPI3Response($path);

        $desc = $response['responseParamsDesc'];
        $remarks = array_column($desc, 'remark', 'name');

        $this->assertEquals('请求状态', $remarks['status'] ?? '', 'status.remark');
        // OuterObj 层
        $this->assertEquals('总数', $remarks['total'] ?? '', 'total.remark');
        $this->assertEquals('条目列表', $remarks['items'] ?? '', 'items.remark');
        // InnerItem 层
        $this->assertEquals('条目ID', $remarks['itemId'] ?? '', 'itemId.remark');
        $this->assertEquals('条目标签', $remarks['label'] ?? '', 'label.remark');
    }

    /**
     * 【回归】嵌套 $ref — responseExample 自动生成
     */
    public function testNestedRefExample(): void
    {
        $jsonArray = $this->loadFixture('openapi3-nested-ref.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/nested']['get'];
        $response = $this->processOpenAPI3Response($path);

        $example = json_decode($response['responseExample'], true);
        $this->assertNotNull($example);
        $this->assertArrayHasKey('status', $example);
        $this->assertArrayHasKey('result', $example);
    }

    // ===============================================================
    //  回归 — Swagger 2 只有 example 无 definitions
    // ===============================================================

    /**
     * 【回归】Swagger 2 只有 example — 降级处理，不报错
     */
    public function testSwagger2ExampleOnlyResponse(): void
    {
        $jsonArray = $this->loadFixture('swagger2-example-only.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/example-only']['get'];
        $response = $this->processSwagger2Response($path);

        $this->assertArrayHasKey('responseParamsDesc', $response);
        $this->assertArrayHasKey('responseExample', $response);

        $example = json_decode($response['responseExample'], true);
        $this->assertNotNull($example);
        $this->assertEquals(200, $example['code'] ?? null);

        // 降级到 example，remark 为空
        $desc = $response['responseParamsDesc'];
        foreach ($desc as $param) {
            $this->assertEquals('', $param['remark'] ?? 'NOT_EMPTY');
        }
    }

    // ===============================================================
    //  回归 — requestParamsDesc 不受影响
    // ===============================================================

    /**
     * 【回归】requestParamsDesc 不受 response 处理影响
     */
    public function testRequestParamsDescUnaffected(): void
    {
        $jsonArray = $this->loadFixture('openapi3-with-request-params.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/search']['post'];
        $result = $this->requestToApi('post', '/search', $path, $jsonArray, '3.0.3');

        $content = json_decode($result['page_content'], true);
        $this->assertNotNull($content);

        // 验证 query 参数提取正确
        $query = $content['request']['query'] ?? [];
        $queryNames = array_column($query, 'name');
        $this->assertContains('page', $queryNames, 'query param "page" should exist');

        // 验证 query 参数的 remark 正确
        $queryRemarks = array_column($query, 'remark', 'name');
        $this->assertEquals('页码', $queryRemarks['page'] ?? '', 'page.remark should be correct');

        // 验证 header 参数提取正确
        $headers = $content['request']['headers'] ?? [];
        $headerNames = array_column($headers, 'name');
        $this->assertContains('X-Request-Id', $headerNames, 'header param should exist');

        $headerRemarks = array_column($headers, 'remark', 'name');
        $this->assertEquals('请求追踪ID', $headerRemarks['X-Request-Id'] ?? '', 'header.remark should be correct');

        // 验证 json body 的 jsonDesc
        $jsonDesc = $content['request']['params']['jsonDesc'] ?? [];
        $this->assertNotEmpty($jsonDesc, 'jsonDesc should not be empty');
        $jsonDescRemarks = array_column($jsonDesc, 'remark', 'name');
        $this->assertEquals('搜索关键词', $jsonDescRemarks['keyword'] ?? '', 'keyword.remark should be correct');
        $this->assertEquals('返回数量', $jsonDescRemarks['limit'] ?? '', 'limit.remark should be correct');

        // 验证 response 不受影响
        $response = $content['response'];
        $this->assertArrayHasKey('responseParamsDesc', $response);

        $respRemarks = array_column($response['responseParamsDesc'], 'remark', 'name');
        $this->assertEquals('总条数', $respRemarks['total'] ?? '', 'response total.remark');
        $this->assertEquals('结果列表', $respRemarks['results'] ?? '', 'response results.remark');
        $this->assertEquals('记录ID', $respRemarks['id'] ?? '', 'response id.remark');
        $this->assertEquals('标题', $respRemarks['title'] ?? '', 'response title.remark');
    }

    // ===============================================================
    //  回归 — 接口标题/路径/方法/描述
    // ===============================================================

    /**
     * 【回归】接口标题、路径、方法、描述正确提取
     */
    public function testPageMetadataExtraction(): void
    {
        $jsonArray = $this->loadFixture('runapi-probe-openapi3-components.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/probe/components']['get'];
        $result = $this->requestToApi('get', '/probe/components', $path, $jsonArray, '3.0.3');

        $this->assertEquals('OpenAPI3 components ref 测试', $result['page_title']);

        $content = json_decode($result['page_content'], true);
        $this->assertNotNull($content);

        $info = $content['info'];
        $this->assertEquals('get', $info['method']);
        $this->assertStringContainsString('/probe/components', $info['url']);
        $this->assertEquals('OpenAPI3 components ref 测试', $info['title']);
        $this->assertEquals('OpenAPI3 components ref 测试', $info['description']);
    }

    /**
     * 【回归】Swagger 2 接口元数据提取
     */
    public function testSwagger2MetadataExtraction(): void
    {
        $jsonArray = $this->loadFixture('runapi-probe-swagger2.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/probe/swagger2']['get'];
        $result = $this->requestToApi('get', '/probe/swagger2', $path, $jsonArray, '2.0');

        $this->assertEquals('Swagger2 definitions 测试', $result['page_title']);

        $content = json_decode($result['page_content'], true);
        $this->assertNotNull($content);

        $info = $content['info'];
        $this->assertEquals('get', $info['method']);
        $this->assertStringContainsString('/probe/swagger2', $info['url']);
    }

    // ===============================================================
    //  回归 — 无 application/json content type
    // ===============================================================

    /**
     * 【回归】无 application/json 的 content type
     */
    public function testNoJsonContentType(): void
    {
        $jsonArray = $this->loadFixture('openapi3-multi-content-type.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/multi-content']['get'];
        $response = $this->processOpenAPI3Response($path);

        // 应该能正常处理（包含 text/html + application/json）
        $this->assertNotEmpty($response, 'Should handle response with multiple content types');
    }

    // ===============================================================
    //  回归 — 空响应
    // ===============================================================

    /**
     * 【回归】responses 中没有 200 — 应该不报错
     */
    public function testEmptyResponses(): void
    {
        $jsonArray = $this->loadFixture('openapi3-no-content.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/delete']['delete'];
        $response = $this->processOpenAPI3Response($path);

        // 没有 200 响应，response 为空
        $this->assertEmpty($response);
    }

    // ===============================================================
    //  单元测试 — definitionToJsonArray()
    // ===============================================================

    /**
     * 回归测试：toB 生成示例 JSON 时，数组/对象字段应优先使用字段自身的
     * example/default，而不是用 type 占位值（如 ["string"] / [[]]）。
     * 对应官方 issue #2569。
     */
    public function testToBArrayExamplePreference(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'baseDate' => [
                    'type' => 'string',
                    'example' => '2026-07-28',
                    'default' => '2026-07-28',
                ],
                'countryCode' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'example' => ['DE'],
                    'default' => ['DE'],
                ],
                'bikeSeries' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'example' => ['AGO'],
                ],
                'emptyList' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'example' => [],
                    'default' => [],
                ],
            ],
        ];

        $result = $this->invokeMethod('toB', [$schema]);

        $this->assertEquals('2026-07-28', $result['baseDate'] ?? null);
        $this->assertEquals(['DE'], $result['countryCode'] ?? null, 'array field should use its example');
        $this->assertEquals(['AGO'], $result['bikeSeries'] ?? null, 'array field should use its example');
        $this->assertEquals([], $result['emptyList'] ?? null, 'empty array example should be preserved');
    }

    /**
     * 直接测试 definitionToJsonArray — 基本属性提取
     */
    public function testDefinitionToJsonArrayBasic(): void
    {
        $schema = [
            'type' => 'object',
            'required' => ['code'],
            'properties' => [
                'code' => [
                    'type' => 'integer',
                    'description' => '状态码',
                ],
                'msg' => [
                    'type' => 'string',
                    'description' => '消息',
                ],
            ],
        ];

        $result = $this->invokeMethod('definitionToJsonArray', [$schema]);

        $this->assertCount(2, $result);

        $this->assertEquals('code', $result[0]['name']);
        $this->assertEquals('integer', $result[0]['type']);
        $this->assertEquals('1', $result[0]['require'], 'code should be required');
        $this->assertEquals('状态码', $result[0]['remark']);

        $this->assertEquals('msg', $result[1]['name']);
        $this->assertEquals('0', $result[1]['require'], 'msg should not be required');
        $this->assertEquals('消息', $result[1]['remark']);
    }

    /**
     * 直接测试 definitionToJsonArray — 嵌套 properties
     */
    public function testDefinitionToJsonArrayNested(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'data' => [
                    'type' => 'object',
                    'description' => '数据',
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                            'description' => 'ID',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->invokeMethod('definitionToJsonArray', [$schema]);

        $names = array_column($result, 'name');
        $this->assertContains('data', $names);
        $this->assertContains('id', $names);

        $remarks = array_column($result, 'remark', 'name');
        $this->assertEquals('数据', $remarks['data']);
        $this->assertEquals('ID', $remarks['id']);
    }

    /**
     * 直接测试 definitionToJsonArray — items 数组
     */
    public function testDefinitionToJsonArrayItems(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => '列表',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'description' => '名称',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->invokeMethod('definitionToJsonArray', [$schema]);

        $names = array_column($result, 'name');
        $this->assertContains('items', $names);
        $this->assertContains('name', $names);

        $remarks = array_column($result, 'remark', 'name');
        $this->assertEquals('列表', $remarks['items']);
        $this->assertEquals('名称', $remarks['name']);
    }

    /**
     * 直接测试 definitionToJsonArray — title 优先于 description
     */
    public function testDefinitionToJsonArrayTitlePriority(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'field' => [
                    'type' => 'string',
                    'title' => '标题说明',
                    'description' => '描述说明',
                ],
            ],
        ];

        $result = $this->invokeMethod('definitionToJsonArray', [$schema]);

        // title 优先（代码中: $remark = $value['title'] ?? $value['description'] ?? '';）
        $this->assertEquals('标题说明', $result[0]['remark']);
    }

    /**
     * 直接测试 definitionToJsonArray — 空属性
     */
    public function testDefinitionToJsonArrayEmpty(): void
    {
        $schema = ['type' => 'object'];
        $result = $this->invokeMethod('definitionToJsonArray', [$schema]);
        $this->assertEmpty($result);
    }

    // ===============================================================
    //  单元测试 — exampleToParamsDesc()
    // ===============================================================

    /**
     * 直接测试 exampleToParamsDesc — 对象 example
     */
    public function testExampleToParamsDescObject(): void
    {
        $example = ['code' => 200, 'msg' => 'ok'];
        $result = $this->invokeMethod('exampleToParamsDesc', [$example]);

        $this->assertCount(2, $result);
        $this->assertEquals('code', $result[0]['name']);
        $this->assertEquals('integer', $result[0]['type']);
        $this->assertEquals('', $result[0]['remark'], 'remark should be empty from example');
    }

    /**
     * 直接测试 exampleToParamsDesc — 数组 example
     */
    public function testExampleToParamsDescArray(): void
    {
        $example = [
            ['id' => 1, 'name' => 'test'],
        ];
        $result = $this->invokeMethod('exampleToParamsDesc', [$example]);

        $names = array_column($result, 'name');
        $this->assertNotEmpty($result, 'Should derive params from array example');
        // 第一层是 array 类型
        $this->assertNotEmpty($result);
    }

    /**
     * 直接测试 exampleToParamsDesc — 嵌套对象
     */
    public function testExampleToParamsDescNested(): void
    {
        $example = [
            'data' => [
                'id' => 1,
                'name' => 'test',
            ],
        ];
        $result = $this->invokeMethod('exampleToParamsDesc', [$example]);

        $names = array_column($result, 'name');
        $this->assertContains('data', $names);
        $this->assertContains('data.id', $names);
        $this->assertContains('data.name', $names);
    }

    // ===============================================================
    //  单元测试 — getTypeFromValue()
    // ===============================================================

    /**
     * @dataProvider typeFromValueProvider
     */
    public function testGetTypeFromValue($value, string $expected): void
    {
        $result = $this->invokeMethod('getTypeFromValue', [$value]);
        $this->assertEquals($expected, $result);
    }

    public function typeFromValueProvider(): array
    {
        return [
            'null'     => [null, 'null'],
            'bool'     => [true, 'boolean'],
            'int'      => [42, 'integer'],
            'float'    => [3.14, 'number'],
            'string'   => ['hello', 'string'],
            'array'    => [[1, 2], 'array'],
        ];
    }

    // ===============================================================
    //  集成 — responseExample 格式正确性
    // ===============================================================

    /**
     * responseExample 是合法 JSON 字符串
     */
    public function testResponseExampleIsValidJson(): void
    {
        $fixtures = [
            'runapi-probe-openapi3-components.json' => '/probe/components',
            'runapi-probe-openapi3-inline.json'     => '/probe/inline',
            'runapi-probe-swagger2.json'            => '/probe/swagger2',
        ];

        foreach ($fixtures as $filename => $pathKey) {
            $jsonArray = $this->loadFixture($filename);
            $this->initControllerWithJson($jsonArray);

            $path = $jsonArray['paths'][$pathKey];
            $firstMethod = array_keys($path)[0];
            $methodDef = $path[$firstMethod];

            $isSwagger2 = isset($jsonArray['swagger']);
            $response = $isSwagger2
                ? $this->processSwagger2Response($methodDef)
                : $this->processOpenAPI3Response($methodDef);

            if (!empty($response)) {
                $this->assertArrayHasKey('responseExample', $response);
                // responseExample 应该是合法 JSON
                $decoded = json_decode($response['responseExample'], true);
                $this->assertNotNull(
                    $decoded,
                    "responseExample for {$filename} should be valid JSON, got: {$response['responseExample']}"
                );
            }

            // 重置
            $this->setUp();
        }
    }

    // ===============================================================
    //  同时有 schema 和 example — schema 优先
    // ===============================================================

    /**
     * 【回归】同时有 schema 和 example — schema 提取 description，example 生成 responseExample
     */
    public function testSchemaAndExampleSchemaPriority(): void
    {
        $jsonArray = $this->loadFixture('runapi-probe-openapi3-components.json');
        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/probe/components']['get'];
        $response = $this->processOpenAPI3Response($path);

        // responseParamsDesc 来自 schema（有 description/remark）
        $desc = $response['responseParamsDesc'];
        $hasNonEmptyRemark = false;
        foreach ($desc as $param) {
            if (!empty($param['remark'])) {
                $hasNonEmptyRemark = true;
                break;
            }
        }
        $this->assertTrue($hasNonEmptyRemark, 'responseParamsDesc should have non-empty remarks from schema');

        // responseExample 来自 example
        $example = json_decode($response['responseExample'], true);
        $this->assertEquals(200, $example['code'] ?? null, 'responseExample should come from example field');
        $this->assertEquals('success', $example['msg'] ?? null);
    }

    // ===============================================================
    //  int → integer 类型转换
    // ===============================================================

    /**
     * type 为 "int" 时应转换为 "integer"
     */
    public function testIntTypeNormalization(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'count' => [
                    'type' => 'int',
                    'description' => '计数',
                ],
            ],
        ];

        $result = $this->invokeMethod('definitionToJsonArray', [$schema]);
        $this->assertEquals('integer', $result[0]['type'], '"int" should be normalized to "integer"');
    }

    // ===============================================================
    //  URL 前缀设置
    // ===============================================================

    /**
     * OpenAPI 3 的 server URL 应正确设置
     */
    public function testOpenApi3UrlPrefix(): void
    {
        $jsonArray = $this->loadFixture('runapi-probe-openapi3-components.json');
        $this->setPrivateProperty('jsonArray', $jsonArray);

        // 模拟 import() 中的 urlPre 设置逻辑
        if (!empty($jsonArray['servers'][0]['url'])) {
            $this->setPrivateProperty('urlPre', $jsonArray['servers'][0]['url']);
        }

        $this->initControllerWithJson($jsonArray);

        $path = $jsonArray['paths']['/probe/components']['get'];
        $result = $this->requestToApi('get', '/probe/components', $path, $jsonArray, '3.0.3');

        $content = json_decode($result['page_content'], true);
        $url = $content['info']['url'] ?? '';
        $this->assertStringContainsString('/probe/components', $url);
    }
}
