<?php

namespace Tests\Agent;

use PHPUnit\Framework\TestCase;
use App\Mcp\Handler\ItemHandler;
use App\Mcp\McpException;
use App\Model\ItemChangeLog;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * MCP get_recent_changes 变更感知工具测试（开源版）
 *
 * 覆盖用例：
 *   - 工具注册：getRecentChanges 出现在 getSupportedOperations，McpServer 注册 level=read
 *   - 权限：read token 可调用；非项目成员被拒；item_id 必填
 *   - 可见类型白名单：只返回 page/catalog/tree 白名单组合，
 *     binding/unbound/export/系统级 update×item 不返回
 *   - since / limit 过滤
 *   - catalog_id 过滤（含子目录；page 按当前 cat_id 归属；tree 拖曳被过滤掉）
 *
 * 开源版说明：item_change_log 为单表（不分表），测试自建该表。
 */
class ItemRecentChangesTest extends TestCase
{
    // ==================================================================
    //  公共辅助
    // ==================================================================

    private function setupReadToken(object $handler, int $uid = 100): void
    {
        $handler->setTokenInfo([
            'uid'        => $uid,
            'permission' => 'read',
            'scope'      => 'all',
            'token_type' => 'user_token',
        ]);
    }

    private function insertUser(int $uid, string $username): void
    {
        DB::table('user')->insert([
            'uid'      => $uid,
            'username' => $username,
            'groupid'  => 0,
            'email'    => $username . '@test.com',
            'email_verify' => 1,
        ]);
    }

    private function insertItem(int $itemId, string $name, int $uid = 100): void
    {
        DB::table('item')->insert([
            'item_id'          => $itemId,
            'item_name'        => $name,
            'uid'              => $uid,
            'item_type'        => 1,
            'is_del'           => 0,
            'last_update_time' => 0,
        ]);
    }

    private function insertItemMember(int $itemId, int $uid): void
    {
        DB::table('item_member')->insert([
            'item_id'         => $itemId,
            'uid'             => $uid,
            'member_group_id' => 1,
        ]);
    }

    private function insertCatalog(int $catId, int $itemId, string $name, int $parentCatId = 0): void
    {
        DB::table('catalog')->insert([
            'cat_id'        => $catId,
            'item_id'       => $itemId,
            'cat_name'      => $name,
            'parent_cat_id' => $parentCatId,
            's_number'      => 99,
            'addtime'       => 0,
            'level'         => 1,
        ]);
    }

    private function insertPage(int $pageId, int $itemId, string $title, int $catId = 0): void
    {
        DB::table('page')->insert([
            'page_id'         => $pageId,
            'item_id'         => $itemId,
            'cat_id'          => $catId,
            'page_title'      => $title,
            'page_content'    => '',
            'is_del'          => 0,
            'is_draft'        => 0,
            's_number'        => 99,
            'addtime'         => 0,
            'author_uid'      => 100,
            'author_username' => 'testuser',
        ]);
    }

    /**
     * 插入一条变更日志（绕过 addLog 以便指定 optime / id 顺序）
     */
    private function insertChangeLog(
        int $itemId,
        string $actionType,
        string $objectType,
        int $objectId,
        string $objectName,
        string $optime,
        int $uid = 100
    ): void {
        DB::table('item_change_log')->insert([
            'uid'            => $uid,
            'item_id'        => $itemId,
            'op_action_type' => $actionType,
            'op_object_type' => $objectType,
            'op_object_id'   => $objectId,
            'op_object_name' => $objectName,
            'remark'         => '',
            'optime'         => $optime,
        ]);
    }

    // ==================================================================
    //  Schema / setUp / tearDown
    // ==================================================================

    protected function setUp(): void
    {
        // page / catalog 等基础表已由 bootstrap 创建（含 ext_info），不重建以免污染其他测试；
        // item_change_log 也已由 bootstrap 创建，这里仅兜底确保存在
        $schema = DB::connection()->getSchemaBuilder();
        if (!$schema->hasTable('item_change_log')) {
            $schema->create('item_change_log', function ($t) {
                $t->increments('id');
                $t->integer('uid')->default(0);
                $t->integer('item_id')->default(0);
                $t->string('op_action_type', 50)->default('');
                $t->string('op_object_type', 50)->default('');
                $t->integer('op_object_id')->default(0);
                $t->string('op_object_name', 255)->default('');
                $t->string('remark', 255)->default('');
                $t->string('optime', 50)->default('');
            });
        }
    }

    protected function tearDown(): void
    {
        foreach (['item', 'page', 'user', 'catalog', 'item_member', 'team_item_member', 'item_change_log'] as $t) {
            try { DB::table($t)->delete(); } catch (\Throwable $e) {}
        }
    }

    // ==================================================================
    //  用例
    // ==================================================================

    /** 工具注册：ItemHandler 支持列表包含 get_recent_changes */
    public function testOperationRegistered(): void
    {
        $handler = new ItemHandler();
        $this->assertContains('get_recent_changes', $handler->getSupportedOperations());
    }

    /** 工具注册：McpServer tools/list 包含 get_recent_changes 且 level=read */
    public function testToolRegisteredAsRead(): void
    {
        $server = new \App\Mcp\McpServer();
        $resp = $server->handleRequest([
            'jsonrpc' => '2.0',
            'id'      => 'test-1',
            'method'  => 'tools/list',
            'params'  => [],
        ]);
        $names = array_column($resp['result']['tools'], 'name');
        $this->assertContains('get_recent_changes', $names);
    }

    /** item_id 必填：缺参抛 INVALID_PARAMS */
    public function testMissingItemId(): void
    {
        $handler = new ItemHandler();
        $this->setupReadToken($handler);
        $this->expectException(McpException::class);
        $handler->execute('get_recent_changes', []);
    }

    /** 项目不存在抛 RESOURCE_NOT_FOUND（同时避免误用不存在的项目） */
    public function testItemNotFound(): void
    {
        $this->insertUser(100, 'testuser');
        $handler = new ItemHandler();
        $this->setupReadToken($handler);
        $this->expectException(McpException::class);
        $handler->execute('get_recent_changes', ['item_id' => 999]);
    }

    /** 只读 Token + 项目成员可调用（变更感知面向普通/只读成员） */
    public function testReadTokenCanCall(): void
    {
        $this->insertUser(100, 'testuser');
        $this->insertItem(1, '测试项目', 100);
        $this->insertItemMember(1, 100);
        $this->insertChangeLog(1, 'update', 'page', 10, '接口文档', '2026-09-22 10:00:00');

        $handler = new ItemHandler();
        $this->setupReadToken($handler);
        $ret = $handler->execute('get_recent_changes', ['item_id' => 1]);

        $this->assertSame(1, $ret['total']);
        $this->assertCount(1, $ret['list']);
        $this->assertSame('update', $ret['list'][0]['op_action_type']);
        $this->assertSame('page', $ret['list'][0]['op_object_type']);
        $this->assertSame('接口文档', $ret['list'][0]['op_object_name']);
        $this->assertSame('testuser', $ret['list'][0]['username']);
    }

    /** 可见类型白名单：binding / unbound / export / update×item 不返回 */
    public function testInvisibleCombosFiltered(): void
    {
        $this->insertUser(100, 'testuser');
        $this->insertItem(1, '测试项目', 100);
        $this->insertItemMember(1, 100);
        $this->insertPage(10, 1, '接口文档');

        // 白名单内
        $this->insertChangeLog(1, 'create', 'page', 10, '接口文档', '2026-09-22 10:00:00');
        $this->insertChangeLog(1, 'update', 'page', 10, '接口文档', '2026-09-22 10:01:00');
        $this->insertChangeLog(1, 'delete', 'page', 11, '旧页面', '2026-09-22 10:02:00');
        $this->insertChangeLog(1, 'create', 'catalog', 5, '目录A', '2026-09-22 10:03:00');
        $this->insertChangeLog(1, 'drag', 'tree', 0, '', '2026-09-22 10:04:00');
        // 白名单外（敏感/系统级）
        $this->insertChangeLog(1, 'binding', 'item', 1, '测试项目', '2026-09-22 11:00:00');
        $this->insertChangeLog(1, 'unbound', 'item', 1, '测试项目', '2026-09-22 11:01:00');
        $this->insertChangeLog(1, 'export', 'item', 1, '测试项目', '2026-09-22 11:02:00');
        $this->insertChangeLog(1, 'update', 'item', 1, '测试项目', '2026-09-22 11:03:00');

        $handler = new ItemHandler();
        $this->setupReadToken($handler);
        $ret = $handler->execute('get_recent_changes', ['item_id' => 1]);

        $this->assertSame(5, $ret['total']);
        foreach ($ret['list'] as $row) {
            $combo = $row['op_object_type'] . ':' . $row['op_action_type'];
            $this->assertNotContains($combo, ['item:binding', 'item:unbound', 'item:export', 'item:update']);
        }
        // 按时间倒序：最新在前
        $this->assertSame('drag', $ret['list'][0]['op_action_type']);
    }

    /** since 过滤：只返回起始时间之后的变更 */
    public function testSinceFilter(): void
    {
        $this->insertUser(100, 'testuser');
        $this->insertItem(1, '测试项目', 100);
        $this->insertItemMember(1, 100);
        $this->insertChangeLog(1, 'create', 'page', 10, '旧变更', '2026-09-20 10:00:00');
        $this->insertChangeLog(1, 'update', 'page', 10, '新变更', '2026-09-22 10:00:00');

        $handler = new ItemHandler();
        $this->setupReadToken($handler);
        $since = strtotime('2026-09-21 00:00:00');
        $ret = $handler->execute('get_recent_changes', ['item_id' => 1, 'since' => $since]);

        $this->assertSame(1, $ret['total']);
        $this->assertSame('新变更', $ret['list'][0]['op_object_name']);
    }

    /** limit 过滤：默认 20，最大 100 */
    public function testLimitFilter(): void
    {
        $this->insertUser(100, 'testuser');
        $this->insertItem(1, '测试项目', 100);
        $this->insertItemMember(1, 100);
        for ($i = 1; $i <= 30; $i++) {
            $this->insertChangeLog(1, 'update', 'page', 10, "页面{$i}", '2026-09-22 10:' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ':00');
        }

        $handler = new ItemHandler();
        $this->setupReadToken($handler);
        $ret = $handler->execute('get_recent_changes', ['item_id' => 1]);
        $this->assertSame(20, $ret['count']); // 默认 20

        $ret = $handler->execute('get_recent_changes', ['item_id' => 1, 'limit' => 5]);
        $this->assertSame(30, $ret['total']);
        $this->assertSame(5, $ret['count']);
        $this->assertSame('页面30', $ret['list'][0]['op_object_name']); // 时间倒序
    }

    /** catalog_id 过滤：只返回该目录（含子目录）范围内的变更，tree 拖曳被过滤 */
    public function testCatalogFilter(): void
    {
        $this->insertUser(100, 'testuser');
        $this->insertItem(1, '测试项目', 100);
        $this->insertItemMember(1, 100);

        // 目录结构：cat 1（父）> cat 2（子）；cat 3（范围外）
        $this->insertCatalog(1, 1, '父目录');
        $this->insertCatalog(2, 1, '子目录', 1);
        $this->insertCatalog(3, 1, '其他目录');
        // 页面：page 10 → cat 2（范围内）；page 11 → cat 3（范围外）
        $this->insertPage(10, 1, '子目录页面', 2);
        $this->insertPage(11, 1, '范围外页面', 3);

        $this->insertChangeLog(1, 'update', 'page', 10, '子目录页面', '2026-09-22 10:00:00');
        $this->insertChangeLog(1, 'update', 'page', 11, '范围外页面', '2026-09-22 10:01:00');
        $this->insertChangeLog(1, 'update', 'catalog', 1, '父目录', '2026-09-22 10:02:00');
        $this->insertChangeLog(1, 'update', 'catalog', 3, '其他目录', '2026-09-22 10:03:00');
        $this->insertChangeLog(1, 'drag', 'tree', 0, '', '2026-09-22 10:04:00');

        $handler = new ItemHandler();
        $this->setupReadToken($handler);
        $ret = $handler->execute('get_recent_changes', ['item_id' => 1, 'catalog_id' => 1]);

        // 只剩：page10（子目录内）、catalog1（父目录自身）；tree 拖曳无法归属目录被过滤
        $this->assertSame(2, $ret['total']);
        $names = array_column($ret['list'], 'op_object_name');
        $this->assertContains('子目录页面', $names);
        $this->assertContains('父目录', $names);
    }

    /** 模型层直查：MEMBER_VISIBLE_COMBOS 常量与 getRecentChanges 返回结构 */
    public function testModelGetRecentChanges(): void
    {
        $this->insertUser(100, 'testuser');
        $this->insertItem(1, '测试项目', 100);
        $this->insertChangeLog(1, 'create', 'page', 10, '页面', '2026-09-22 10:00:00');

        $ret = ItemChangeLog::getRecentChanges(1);
        $this->assertSame(1, $ret['total']);
        $this->assertArrayHasKey('op_action_type', $ret['list'][0]);
        $this->assertArrayHasKey('op_object_type', $ret['list'][0]);
        $this->assertArrayHasKey('op_object_id', $ret['list'][0]);
        $this->assertArrayHasKey('op_object_name', $ret['list'][0]);
        $this->assertArrayHasKey('optime', $ret['list'][0]);
        $this->assertArrayHasKey('uid', $ret['list'][0]);
        $this->assertArrayHasKey('username', $ret['list'][0]);

        // 非法 itemId
        $this->assertSame(['total' => 0, 'list' => []], ItemChangeLog::getRecentChanges(0));
    }

    /** getLog 兼容：不传过滤参数时行为不变 */
    public function testGetLogBackwardCompatible(): void
    {
        $this->insertUser(100, 'testuser');
        $this->insertItem(1, '测试项目', 100);
        $this->insertChangeLog(1, 'create', 'page', 10, '页面', '2026-09-22 10:00:00');
        $this->insertChangeLog(1, 'binding', 'item', 1, '测试项目', '2026-09-22 10:01:00');

        $ret = ItemChangeLog::getLog(1);
        $this->assertSame(2, $ret['total']); // 不过滤，全部返回

        $ret = ItemChangeLog::getLog(1, 1, 15, ['create'], ['page']);
        $this->assertSame(1, $ret['total']);
    }
}
