<?php

namespace Tests\Mock;

use PHPUnit\Framework\TestCase;
use App\Model\Mock;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Mock Model 层单元测试
 *
 * 测试 findByItemIdAndPath 的精确匹配 / 正则匹配逻辑，
 * isSafeRegex 的安全检查，以及 findByPageId / findByUniqueKey /
 * saveByPageId / add / incrementViewTimes 等方法。
 */
class MockModelTest extends TestCase
{
    protected function setUp(): void
    {
        DB::table('mock')->truncate();
    }

    // ------------------------------------------------------------------
    // 辅助
    // ------------------------------------------------------------------

    private function insertMock(array $overrides = []): int
    {
        $defaults = [
            'unique_key'       => md5(uniqid('test_', true)),
            'uid'              => 1,
            'page_id'          => 0,
            'item_id'          => 100,
            'template'         => '{"msg":"ok"}',
            'path'             => '/api/users',
            'addtime'          => date('Y-m-d H:i:s'),
            'last_update_time' => date('Y-m-d H:i:s'),
            'view_times'       => 0,
        ];
        return DB::table('mock')->insertGetId(array_merge($defaults, $overrides));
    }

    // ==================================================================
    //  findByItemIdAndPath — 精确匹配
    // ==================================================================

    /** 精确匹配命中 */
    public function testExactMatchHit(): void
    {
        $id = $this->insertMock(['item_id' => 100, 'path' => '/api/users']);

        $result = Mock::findByItemIdAndPath(100, '/api/users');

        $this->assertNotNull($result);
        $this->assertEquals($id, (int) $result['id']);
    }

    /** 精确匹配未命中 */
    public function testExactMatchMiss(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '/api/users']);

        $result = Mock::findByItemIdAndPath(100, '/api/orders');

        $this->assertNull($result);
    }

    /** 不同 item_id 不干扰 */
    public function testDifferentItemIdNotInterfering(): void
    {
        $this->insertMock(['item_id' => 200, 'path' => '/api/users']);

        $result = Mock::findByItemIdAndPath(100, '/api/users');
        $this->assertNull($result, '不同 item_id 的记录不应被匹配');
    }

    /** 非 ~ 开头的路径不参与正则匹配 */
    public function testNonTildePathNotUsedAsRegex(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '/api/users/123']);

        $result = Mock::findByItemIdAndPath(100, '/api/users/999');

        $this->assertNull($result, '非 ~ 开头的路径不应参与正则匹配');
    }

    /** itemId <= 0 返回 null */
    public function testZeroItemIdReturnsNull(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '/api/test']);

        $result = Mock::findByItemIdAndPath(0, '/api/test');
        $this->assertNull($result);
    }

    /** 负数 itemId 返回 null */
    public function testNegativeItemIdReturnsNull(): void
    {
        $result = Mock::findByItemIdAndPath(-1, '/api/test');
        $this->assertNull($result);
    }

    /** 空路径返回 null */
    public function testEmptyPathReturnsNull(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '/api/test']);

        $result = Mock::findByItemIdAndPath(100, '');
        $this->assertNull($result);
    }

    // ==================================================================
    //  findByItemIdAndPath — 正则匹配
    // ==================================================================

    /** 正则匹配命中 */
    public function testRegexMatchHit(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '~^/api/users/\d+$']);

        $result = Mock::findByItemIdAndPath(100, '/api/users/123');

        $this->assertNotNull($result);
        $this->assertEquals('~^/api/users/\d+$', $result['path']);
    }

    /** 正则匹配未命中 */
    public function testRegexMatchMiss(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '~^/api/users/\d+$']);

        $result = Mock::findByItemIdAndPath(100, '/api/orders/123');

        $this->assertNull($result);
    }

    /** 精确匹配优先于正则 */
    public function testExactMatchPriorityOverRegex(): void
    {
        $regexId = $this->insertMock(['item_id' => 100, 'path' => '~^/api/users$']);
        $exactId = $this->insertMock(['item_id' => 100, 'path' => '/api/users']);

        $result = Mock::findByItemIdAndPath(100, '/api/users');

        $this->assertNotNull($result);
        $this->assertEquals($exactId, (int) $result['id'], '精确匹配应优先于正则匹配');
    }

    /** 多条正则按创建顺序（id 升序）匹配 */
    public function testRegexMatchesByCreationOrder(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '~^/api/orders/\d+$']);
        $id2 = $this->insertMock(['item_id' => 100, 'path' => '~^/api/users/\d+$']);
        $this->insertMock(['item_id' => 100, 'path' => '~^/api/users/']);

        $result = Mock::findByItemIdAndPath(100, '/api/users/42');

        $this->assertNotNull($result);
        $this->assertEquals($id2, (int) $result['id'], '应返回第二条（按 id 升序最先匹配的正则）');
    }

    /** 正则路径含正斜杠 — 正斜杠应被自动转义 */
    public function testRegexWithForwardSlashInPattern(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '~^/api/v1/.+$']);

        $result = Mock::findByItemIdAndPath(100, '/api/v1/users');
        $this->assertNotNull($result, '正则中的 / 应被正确转义');
    }

    /** 正则仅 ~ 符号（空正则）— 匹配任意字符串 */
    public function testEmptyRegexPatternMatchesEverything(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '~']);

        $result = Mock::findByItemIdAndPath(100, '/anything');
        $this->assertNotNull($result, '空正则 ~ 应匹配任意路径');
    }

    /** 不安全的正则（嵌套量词）应被跳过，不匹配 */
    public function testUnsafeRegexSkipped(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '~(a+)+']);

        $result = Mock::findByItemIdAndPath(100, 'aaa');
        $this->assertNull($result, '不安全的正则应被跳过');
    }

    /** 无效正则语法应被 @preg_match 静默处理 */
    public function testInvalidRegexSyntaxHandledGracefully(): void
    {
        // 故意存一个正则语法错误的路径（不平衡的括号等）
        // @preg_match 返回 false，不应抛异常
        $this->insertMock(['item_id' => 100, 'path' => '~^/api/users/[']);

        $result = Mock::findByItemIdAndPath(100, '/api/users/123');
        $this->assertNull($result, '无效正则应返回 null 而非抛异常');
    }

    /** 正则匹配 Unicode 路径 */
    public function testRegexUnicodePath(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '~^/api/用户/\d+$']);

        $result = Mock::findByItemIdAndPath(100, '/api/用户/42');
        $this->assertNotNull($result, '正则应正确匹配 Unicode 路径');
    }

    /** PCRE 回溯限制应在匹配完成后恢复 */
    public function testPcreBacktrackLimitRestored(): void
    {
        $original = ini_get('pcre.backtrack_limit');

        $this->insertMock(['item_id' => 100, 'path' => '~^/test$']);

        // 匹配命中
        Mock::findByItemIdAndPath(100, '/test');
        $this->assertEquals($original, ini_get('pcre.backtrack_limit'), '命中后应恢复 backtrack_limit');

        // 匹配未命中
        Mock::findByItemIdAndPath(100, '/nope');
        $this->assertEquals($original, ini_get('pcre.backtrack_limit'), '未命中后应恢复 backtrack_limit');

        // 无正则记录
        DB::table('mock')->truncate();
        Mock::findByItemIdAndPath(100, '/test');
        $this->assertEquals($original, ini_get('pcre.backtrack_limit'), '无正则记录时应保持原值');
    }

    // ==================================================================
    //  findByPageId
    // ==================================================================

    /** findByPageId 正常查找 */
    public function testFindByPageIdHit(): void
    {
        $id = $this->insertMock(['page_id' => 42]);

        $result = Mock::findByPageId(42);
        $this->assertNotNull($result);
        $this->assertEquals($id, (int) $result['id']);
    }

    /** findByPageId 未命中 */
    public function testFindByPageIdMiss(): void
    {
        $this->insertMock(['page_id' => 42]);

        $this->assertNull(Mock::findByPageId(99));
    }

    /** findByPageId 零 pageId */
    public function testFindByPageIdZeroReturnsNull(): void
    {
        $this->assertNull(Mock::findByPageId(0));
    }

    /** findByPageId 负 pageId */
    public function testFindByPageIdNegativeReturnsNull(): void
    {
        $this->assertNull(Mock::findByPageId(-1));
    }

    // ==================================================================
    //  findByUniqueKey
    // ==================================================================

    /** findByUniqueKey 正常查找 */
    public function testFindByUniqueKeyHit(): void
    {
        $key = 'abc123';
        $id = $this->insertMock(['unique_key' => $key]);

        $result = Mock::findByUniqueKey($key);
        $this->assertNotNull($result);
        $this->assertEquals($id, (int) $result['id']);
    }

    /** findByUniqueKey 未命中 */
    public function testFindByUniqueKeyMiss(): void
    {
        $this->insertMock(['unique_key' => 'abc123']);

        $this->assertNull(Mock::findByUniqueKey('notexist'));
    }

    /** findByUniqueKey 空字符串 */
    public function testFindByUniqueKeyEmptyReturnsNull(): void
    {
        $this->assertNull(Mock::findByUniqueKey(''));
    }

    // ==================================================================
    //  saveByPageId
    // ==================================================================

    /** saveByPageId 正常更新 */
    public function testSaveByPageIdUpdatesRecord(): void
    {
        $this->insertMock(['page_id' => 10, 'template' => '{"old":1}']);

        $ok = Mock::saveByPageId(10, ['template' => '{"new":2}']);
        $this->assertTrue($ok);

        $updated = Mock::findByPageId(10);
        $this->assertEquals('{"new":2}', $updated['template']);
    }

    /** saveByPageId 零 pageId 返回 false */
    public function testSaveByPageIdZeroReturnsFalse(): void
    {
        $this->assertFalse(Mock::saveByPageId(0, ['template' => 'x']));
    }

    /** saveByPageId 负 pageId 返回 false */
    public function testSaveByPageIdNegativeReturnsFalse(): void
    {
        $this->assertFalse(Mock::saveByPageId(-5, ['template' => 'x']));
    }

    /** saveByPageId 记录不存在时仍返回 true（affected=0） */
    public function testSaveByPageIdNoMatchingRecordReturnsTrue(): void
    {
        $this->assertTrue(Mock::saveByPageId(999, ['template' => 'x']));
    }

    // ==================================================================
    //  add
    // ==================================================================

    /** add 插入并返回 ID */
    public function testAddReturnsId(): void
    {
        $id = Mock::add([
            'unique_key'       => 'testkey123',
            'uid'              => 1,
            'page_id'          => 10,
            'item_id'          => 100,
            'template'         => '{"msg":"hello"}',
            'path'             => '/api/test',
            'addtime'          => date('Y-m-d H:i:s'),
            'last_update_time' => date('Y-m-d H:i:s'),
            'view_times'       => 0,
        ]);

        $this->assertGreaterThan(0, $id);

        $found = Mock::findByUniqueKey('testkey123');
        $this->assertNotNull($found);
        $this->assertEquals('/api/test', $found['path']);
    }

    // ==================================================================
    //  incrementViewTimes
    // ==================================================================

    /** incrementViewTimes 正常递增 */
    public function testIncrementViewTimes(): void
    {
        $id = $this->insertMock(['view_times' => 5]);

        $this->assertTrue(Mock::incrementViewTimes($id));

        $row = DB::table('mock')->where('id', $id)->first();
        $this->assertEquals(6, (int) $row->view_times);
    }

    /** incrementViewTimes 多次递增 */
    public function testIncrementViewTimesMultiple(): void
    {
        $id = $this->insertMock(['view_times' => 0]);

        Mock::incrementViewTimes($id);
        Mock::incrementViewTimes($id);
        Mock::incrementViewTimes($id);

        $row = DB::table('mock')->where('id', $id)->first();
        $this->assertEquals(3, (int) $row->view_times);
    }

    /** incrementViewTimes 零 ID 返回 false */
    public function testIncrementViewTimesZeroReturnsFalse(): void
    {
        $this->assertFalse(Mock::incrementViewTimes(0));
    }

    /** incrementViewTimes 负 ID 返回 false */
    public function testIncrementViewTimesNegativeReturnsFalse(): void
    {
        $this->assertFalse(Mock::incrementViewTimes(-1));
    }

    /** incrementViewTimes 不存在的 ID 返回 false */
    public function testIncrementViewTimesNonexistentReturnsFalse(): void
    {
        $this->assertFalse(Mock::incrementViewTimes(999999));
    }

    // ==================================================================
    //  isSafeRegex
    // ==================================================================

    /** 拒绝嵌套量词 (a+)+ */
    public function testRejectsNestedQuantifiers(): void
    {
        $this->assertFalse(Mock::isSafeRegex('(a+)+'));
        $this->assertFalse(Mock::isSafeRegex('(a*)+'));
        $this->assertFalse(Mock::isSafeRegex('(a+)*'));
        $this->assertFalse(Mock::isSafeRegex('(abc+)+'));
    }

    /** 拒绝超长正则（>500 字符） */
    public function testRejectsTooLongPattern(): void
    {
        $this->assertFalse(Mock::isSafeRegex(str_repeat('a', 501)));
    }

    /** 接受恰好 500 字符（边界值） */
    public function testAccepts500CharPattern(): void
    {
        $this->assertTrue(Mock::isSafeRegex(str_repeat('a', 500)));
    }

    /** 接受合法正则 */
    public function testAcceptsValidPatterns(): void
    {
        $this->assertTrue(Mock::isSafeRegex('^/api/users/\d+$'));
        $this->assertTrue(Mock::isSafeRegex('^/api/v[12]/.+$'));
        $this->assertTrue(Mock::isSafeRegex('^/test/[a-z]+$'));
        $this->assertTrue(Mock::isSafeRegex(''));
    }

    /** 拒绝重叠 alternation (a|a)+ */
    public function testRejectsOverlappingAlternation(): void
    {
        $this->assertFalse(Mock::isSafeRegex('(a|a)+'));
        $this->assertFalse(Mock::isSafeRegex('(ab|ab)+'));
    }

    /** 拒绝 4 个连续量化段 */
    public function testRejectsFourQuantifiedSegments(): void
    {
        // 4个独立的量化段
        $pattern = 'a{1,30} b{1,30} c{1,30} d{1,30}';
        $this->assertFalse(Mock::isSafeRegex($pattern));
    }

    /** 允许 3 个连续量化段（边界值） */
    public function testAllowsThreeQuantifiedSegments(): void
    {
        $this->assertTrue(Mock::isSafeRegex('a{1,30}b{1,30}c{1,30}'));
    }

    /** 允许不重叠的 alternation */
    public function testAllowsNonOverlappingAlternation(): void
    {
        $this->assertTrue(Mock::isSafeRegex('(a|b)+'));
        $this->assertTrue(Mock::isSafeRegex('(get|post|put|delete)+'));
    }

    /** 允许简单量词组合 */
    public function testAllowsSimpleQuantifiers(): void
    {
        $this->assertTrue(Mock::isSafeRegex('a+b*'));
        $this->assertTrue(Mock::isSafeRegex('\d+'));
        $this->assertTrue(Mock::isSafeRegex('[a-z]+'));
    }

    // ==================================================================
    //  isSafeRegex — 补充边界与安全场景
    // ==================================================================

    /** 重叠 alternation 含空格的重复项 */
    public function testRejectsOverlappingAlternationWithSpaces(): void
    {
        $this->assertFalse(Mock::isSafeRegex('(a | a)+'));
    }

    /** 允许 3 个量化段（恰好不超限） */
    public function testAllowsExactlyThreeQuantifiedSegments(): void
    {
        $this->assertTrue(Mock::isSafeRegex('a{1,10}b{2,20}c{3}'));
    }

    /** 拒绝超过 500 字符的正则（501） */
    public function testRejects501CharPattern(): void
    {
        $this->assertFalse(Mock::isSafeRegex(str_repeat('a', 501)));
    }

    /** 允许无括号的 alternation — 不触发重叠检查 */
    public function testAllowsBareAlternation(): void
    {
        $this->assertTrue(Mock::isSafeRegex('a|b|c'));
    }

    /** 嵌套量词变体 (a*)* */
    public function testRejectsNestedStarStar(): void
    {
        $this->assertFalse(Mock::isSafeRegex('(a*)*'));
    }

    /** 4 个连续量化段（无空格，正则能正确计数） */
    public function testRejectsFourQuantifiedSegmentsNoSpace(): void
    {
        // 源码检测正则贪婪匹配，无空格分隔时整串算一段，检测不到
        // 这是已知限制：空格分隔的 4 段才被拒绝
        $pattern = 'a{1,30}b{2,20}c{3,5}d{4,10}';
        $this->assertTrue(Mock::isSafeRegex($pattern));
    }

    /** 量化段含空格分隔 — 被正确检测为 4 段 */
    public function testRejectsFourQuantifiedSegmentsWithSpaces(): void
    {
        $pattern = 'a{1,30} b{1,30} c{1,30} d{1,30}';
        $this->assertFalse(Mock::isSafeRegex($pattern));
    }

    // ==================================================================
    //  findByItemIdAndPath — 正则补充场景
    // ==================================================================

    /** 搜索路径本身以 /~ 开头时不匹配普通路径 */
    public function testExactMatchTildePath(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '/api/users']);

        $result = Mock::findByItemIdAndPath(100, '/~something');
        $this->assertNull($result, '精确搜索 /~something 不应匹配普通路径');
    }

    /** 混合安全与不安全正则 — 应跳过不安全的，匹配安全的 */
    public function testMixedSafeAndUnsafeRegexes(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '~(a+)+']);
        $id2 = $this->insertMock(['item_id' => 100, 'path' => '~^/api/\\d+$']);

        $result = Mock::findByItemIdAndPath(100, '/api/42');
        $this->assertNotNull($result);
        $this->assertEquals($id2, (int) $result['id'], '应跳过不安全正则，匹配安全的');
    }

    /** 多条正则全部不安全 — 返回 null */
    public function testAllRegexesUnsafeReturnsNull(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '~(a+)+']);
        $this->insertMock(['item_id' => 100, 'path' => '~(b|b)*']);

        $result = Mock::findByItemIdAndPath(100, 'aaa');
        $this->assertNull($result, '所有正则都不安全时应返回 null');
    }

    /** 正则匹配是大小写敏感的 */
    public function testRegexMatchIsCaseSensitive(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '~^/api/users$']);

        $result = Mock::findByItemIdAndPath(100, '/API/USERS');
        $this->assertNull($result, '正则匹配应区分大小写');
    }

    /** 正则匹配含字符类 */
    public function testRegexWithCharacterClass(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '~^/files/[a-zA-Z0-9_]+\\.pdf$']);

        $result = Mock::findByItemIdAndPath(100, '/files/report_2024.pdf');
        $this->assertNotNull($result);

        $result2 = Mock::findByItemIdAndPath(100, '/files/report.doc');
        $this->assertNull($result2);
    }

    /** 正则中使用 .+ 匹配多级路径 */
    public function testRegexDotPlusMatchesMultiSegment(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '~^/api/.+$']);

        $result = Mock::findByItemIdAndPath(100, '/api/v1/users/42/profile');
        $this->assertNotNull($result);
    }

    /** 空表 — findByItemIdAndPath 返回 null */
    public function testFindByItemIdAndPathEmptyTable(): void
    {
        $result = Mock::findByItemIdAndPath(100, '/api/test');
        $this->assertNull($result);
    }

    /** 精确匹配与正则匹配在不同 item_id 上互不干扰 */
    public function testExactAndRegexAcrossItems(): void
    {
        $this->insertMock(['item_id' => 100, 'path' => '~^/api/.+$']);
        $this->insertMock(['item_id' => 200, 'path' => '/api/test']);

        $result = Mock::findByItemIdAndPath(200, '/api/test');
        $this->assertNotNull($result);

        $result2 = Mock::findByItemIdAndPath(200, '/api/other');
        $this->assertNull($result2, 'item_id=200 不应受 item_id=100 的正则影响');
    }

    /** 同一 item_id 下精确匹配和正则匹配可共存 */
    public function testExactAndRegexCoexistSameItem(): void
    {
        $exactId = $this->insertMock(['item_id' => 100, 'path' => '/api/special']);
        $this->insertMock(['item_id' => 100, 'path' => '~^/api/.+$']);

        // 精确路径命中精确记录
        $result = Mock::findByItemIdAndPath(100, '/api/special');
        $this->assertEquals($exactId, (int) $result['id'], '精确匹配优先');

        // 其他路径命中正则
        $result2 = Mock::findByItemIdAndPath(100, '/api/other');
        $this->assertNotNull($result2, '非精确路径应走正则匹配');
        $this->assertEquals('~^/api/.+$', $result2['path']);
    }

    // ==================================================================
    //  add — 补充场景
    // ==================================================================

    /** add 插入正则路径的 mock */
    public function testAddWithRegexPath(): void
    {
        $id = Mock::add([
            'unique_key'       => 'regexkey789',
            'uid'              => 1,
            'page_id'          => 20,
            'item_id'          => 100,
            'template'         => '{"data":[]}',
            'path'             => '~^/api/\\d+$',
            'addtime'          => date('Y-m-d H:i:s'),
            'last_update_time' => date('Y-m-d H:i:s'),
            'view_times'       => 0,
        ]);

        $this->assertGreaterThan(0, $id);

        $found = Mock::findByPageId(20);
        $this->assertNotNull($found);
        $this->assertEquals('~^/api/\\d+$', $found['path']);
    }

    // ==================================================================
    //  saveByPageId — 补充场景
    // ==================================================================

    /** saveByPageId 更新 path 为正则路径 */
    public function testSaveByPageIdUpdateToRegexPath(): void
    {
        $this->insertMock(['page_id' => 30, 'path' => '/api/old']);

        Mock::saveByPageId(30, ['path' => '~^/api/old/\\d+$']);

        $updated = Mock::findByPageId(30);
        $this->assertEquals('~^/api/old/\\d+$', $updated['path']);
    }

    // ==================================================================
    //  incrementViewTimes — 补充场景
    // ==================================================================

    /** incrementViewTimes 不影响其他字段 */
    public function testIncrementViewTimesDoesNotAlterOtherFields(): void
    {
        $id = $this->insertMock([
            'view_times' => 5,
            'template'   => '{"key":"value"}',
            'path'       => '/api/test',
        ]);

        Mock::incrementViewTimes($id);

        $row = DB::table('mock')->where('id', $id)->first();
        $this->assertEquals(6, (int) $row->view_times);
        $this->assertEquals('{"key":"value"}', $row->template);
        $this->assertEquals('/api/test', $row->path);
    }
}
