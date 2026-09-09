<?php

namespace Tests\Agent;

use PHPUnit\Framework\TestCase;

/**
 * AgentHelper trait 单元测试（开源版）
 *
 * 通过 Closure::bind 将 AgentHelper 的 private 方法绑定到 Host 实例上调用，
 * 在隔离环境中测试纯逻辑方法。
 *
 * 不测试数据库相关方法（countDailyUsage / countMonthlyUsage），
 * 那些在 AgentIntegrationTest 中用 SQLite 内存库覆盖。
 *
 * 与主版的差异（已移除积分/敏感词相关测试）：
 *   - 移除 resolveConsumerUid / buildQuotaInfo / buildOverLimitMessage 用例
 *     （开源版 AgentHelper 已删除这些积分相关方法）
 *   - 移除 createUserCreditsTable 及 setUp 中的调用（开源版无积分体系）
 *   - 移除 McpServer::checkToolPermission 用例（开源版已删除该权限校验方法）
 *   - 移除 PageHandler::extractSnippet 用例（开源版 PageHandler 已删除该方法）
 *   - convertLargeIntegersToString 用例保留（开源版 McpServer 仍有该方法）
 */
class AgentHelperTest extends TestCase
{
    /** @var object Host 实例（use AgentHelper） */
    private $host;

    protected function setUp(): void
    {
        $this->host = new class {
            use \App\Common\Helper\AgentHelper;

            public string $aiSystemPrompt = '';
            public string $aiServiceUrl = '';
            public string $aiServiceToken = '';
            public string $aiModelName = '';
            public string $aiWelcomeMessage = '';

            /**
             * Minimal checkItemManage for resolveRole testing
             * Mirrors BaseController::checkItemManage logic
             */
            public function checkItemManage(int $uid, int $itemId): bool
            {
                if (!$uid) return false;
                $item = \App\Model\Item::findById($itemId);
                if (!$item) return false;
                if ($item->uid && (int) $item->uid == $uid) return true;

                $member = \Illuminate\Database\Capsule\Manager::table('item_member')
                    ->where('item_id', $itemId)->where('uid', $uid)
                    ->where('member_group_id', 2)->first();
                if ($member) return true;

                $teamMember = \Illuminate\Database\Capsule\Manager::table('team_item_member')
                    ->where('item_id', $itemId)->where('member_uid', $uid)
                    ->where('member_group_id', 2)->first();
                if ($teamMember) return true;

                $user = \App\Model\User::findById($uid);
                if ($user && ($user->groupid ?? 0) == 1) return true;

                return false;
            }

            /**
             * Minimal checkItemEdit for resolveRole testing
             * Mirrors BaseController::checkItemEdit logic
             */
            public function checkItemEdit(int $uid, int $itemId): bool
            {
                if (!$uid) return false;
                $item = \App\Model\Item::findById($itemId);
                if ($item && $item->uid && (int) $item->uid == $uid) return true;

                $member = \Illuminate\Database\Capsule\Manager::table('item_member')
                    ->where('item_id', $itemId)->where('uid', $uid)
                    ->where('member_group_id', 1)->first();
                if ($member) return true;

                $teamMember = \Illuminate\Database\Capsule\Manager::table('team_item_member')
                    ->where('item_id', $itemId)->where('member_uid', $uid)
                    ->where('member_group_id', 1)->first();
                if ($teamMember) return true;

                if ($this->checkItemManage($uid, $itemId)) return true;

                return false;
            }
        };

        $this->createItemTable();
        $this->createUserTables();
        $this->createPageTable();
        $this->createOptionsTable();
        $this->createMessageTable();
    }

    // ------------------------------------------------------------------
    //  辅助：通过 Closure 绑定调用 private 方法
    // ------------------------------------------------------------------

    private function invoke(string $method, array $args = [])
    {
        $ref = new \ReflectionMethod($this->host, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($this->host, $args);
    }

    /** Create minimal item table in SQLite for tests that need Item::findById */
    private function createItemTable(): void
    {
        $schema = \Illuminate\Database\Capsule\Manager::connection()->getSchemaBuilder();
        if (!$schema->hasTable('item')) {
            $schema->create('item', function ($table) {
                $table->increments('item_id');
                $table->string('item_name', 255)->default('');
                $table->integer('uid')->default(0);
                $table->integer('item_type')->default(1);
                $table->integer('is_del')->default(0);
                $table->string('password', 255)->nullable();
                $table->string('item_domain', 255)->nullable();
            });
        }
        if (!$schema->hasTable('item_ai_config')) {
            $schema->create('item_ai_config', function ($table) {
                $table->increments('id');
                $table->integer('item_id')->default(0);
                $table->tinyInteger('enabled')->default(0);
                $table->tinyInteger('dialog_collapsed')->default(1);
                $table->text('welcome_message')->nullable();
                $table->text('system_prompt')->nullable();
            });
        }
    }

    /** Create user + member tables for resolveRole tests */
    private function createUserTables(): void
    {
        $schema = \Illuminate\Database\Capsule\Manager::connection()->getSchemaBuilder();
        if (!$schema->hasTable('user')) {
            $schema->create('user', function ($table) {
                $table->increments('uid');
                $table->string('username', 255)->default('');
                $table->string('password', 255)->default('');
                $table->integer('groupid')->default(0);
            });
        }
        if (!$schema->hasTable('item_member')) {
            $schema->create('item_member', function ($table) {
                $table->increments('id');
                $table->integer('item_id')->default(0);
                $table->integer('uid')->default(0);
                $table->integer('member_group_id')->default(0);
            });
        }
        if (!$schema->hasTable('team_item_member')) {
            $schema->create('team_item_member', function ($table) {
                $table->increments('id');
                $table->integer('item_id')->default(0);
                $table->integer('member_uid')->default(0);
                $table->integer('member_group_id')->default(0);
            });
        }
    }

    /** Create options table for tests that read Options::get */
    private function createOptionsTable(): void
    {
        $schema = \Illuminate\Database\Capsule\Manager::connection()->getSchemaBuilder();
        if (!$schema->hasTable('options')) {
            $schema->create('options', function ($table) {
                $table->increments('id');
                $table->string('option_name', 255)->default('');
                $table->text('option_value')->nullable();
            });
        }
    }

    /** Create message table for notifyVipDowngrade tests */
    private function createMessageTable(): void
    {
        $schema = \Illuminate\Database\Capsule\Manager::connection()->getSchemaBuilder();
        if (!$schema->hasTable('message')) {
            $schema->create('message', function ($table) {
                $table->increments('id');
                $table->integer('from_uid')->default(0);
                $table->string('from_username', 255)->default('');
                $table->integer('to_uid')->default(0);
                $table->string('to_username', 255)->default('');
                $table->string('type', 50)->default('');
                $table->string('title', 255)->default('');
                $table->text('content')->nullable();
                $table->integer('addtime')->default(0);
                $table->string('extra', 255)->default('');
            });
        }
    }

    /**
     * Create page table for getPageContentSnippet tests
     *
     * 开源版 Page 模型使用单表存储（page 表含 page_content），
     * 不再使用主版的 page_NN 分表架构。分表由 bootstrap 创建，
     * 此处仅保证 page 表存在且含 page_content 列。
     */
    private function createPageTable(): void
    {
        $schema = \Illuminate\Database\Capsule\Manager::connection()->getSchemaBuilder();
        if (!$schema->hasTable('page')) {
            $schema->create('page', function ($table) {
                $table->increments('page_id');
                $table->integer('item_id')->default(0);
                $table->integer('cat_id')->default(0);
                $table->string('page_title', 255)->default('');
                $table->text('page_content')->nullable();
                $table->integer('is_del')->default(0);
            });
        }
    }

    // ==================================================================
    //  buildSystemPrompt
    // ==================================================================

    /** 基础场景：无项目、无页面、无编辑器内容 */
    public function testBuildSystemPromptBasic(): void
    {
        $result = $this->invoke('buildSystemPrompt', [[
            'item_id' => 0,
            'item_name' => '',
            'page_id' => 0,
            'page_title' => '',
            'editor_content' => '',
            'role' => 'reader',
            'item_type' => 'regular',
        ]]);

        $this->assertStringContainsString('ShowDoc 的 AI 助手', $result);
        $this->assertStringNotContainsString('当前项目：', $result);
        $this->assertStringNotContainsString('当前页面：', $result);
        $this->assertStringContainsString('全局对话模式', $result);
        $this->assertStringContainsString('搜索文档时请注意', $result);
    }

    /** 有项目上下文 */
    public function testBuildSystemPromptWithItem(): void
    {
        $result = $this->invoke('buildSystemPrompt', [[
            'item_id' => 123,
            'item_name' => 'API文档',
            'page_id' => 0,
            'page_title' => '',
            'editor_content' => '',
            'role' => 'admin',
            'item_type' => 'regular',
        ]]);

        $this->assertStringContainsString('当前项目：API文档', $result);
        $this->assertStringContainsString('ID: 123', $result);
        $this->assertStringNotContainsString('全局对话模式', $result);
    }

    /** 有页面上下文（标题注入，无实际页面内容） */
    public function testBuildSystemPromptWithPage(): void
    {
        $result = $this->invoke('buildSystemPrompt', [[
            'item_id' => 1,
            'item_name' => 'Test',
            'page_id' => 456,
            'page_title' => '登录接口',
            'editor_content' => '',
            'role' => 'reader',
            'item_type' => 'regular',
        ]]);

        $this->assertStringContainsString('当前页面：登录接口', $result);
        $this->assertStringContainsString('ID: 456', $result);
    }

    /** 编辑器模式注入 */
    public function testBuildSystemPromptWithEditorContent(): void
    {
        $editorContent = '# 标题\n这是编辑器内容';
        $result = $this->invoke('buildSystemPrompt', [[
            'item_id' => 1,
            'item_name' => 'Test',
            'page_id' => 0,
            'page_title' => '',
            'editor_content' => $editorContent,
            'role' => 'writer',
            'item_type' => 'regular',
        ]]);

        $this->assertStringContainsString('<user_context>', $result);
        $this->assertStringContainsString('<<<EDIT_START>>>', $result);
        $this->assertStringContainsString('<<<EDIT_END>>>', $result);
    }

    /** 编辑器内容超长时截断到3000字 */
    public function testBuildSystemPromptEditorContentTruncation(): void
    {
        $longContent = str_repeat('A', 15000);
        $result = $this->invoke('buildSystemPrompt', [[
            'item_id' => 1,
            'item_name' => 'Test',
            'page_id' => 0,
            'page_title' => '',
            'editor_content' => $longContent,
            'role' => 'writer',
            'item_type' => 'regular',
        ]]);

        $this->assertStringContainsString('编辑器内容已截断', $result);
    }

    /** RunAPI 项目提示 */
    public function testBuildSystemPromptRunapiProject(): void
    {
        $result = $this->invoke('buildSystemPrompt', [[
            'item_id' => 1,
            'item_name' => 'Test',
            'page_id' => 0,
            'page_title' => '',
            'editor_content' => '',
            'role' => 'writer',
            'item_type' => 'runapi',
        ]]);

        $this->assertStringContainsString('RunAPI', $result);
        $this->assertStringContainsString('请到 RunAPI 客户端编辑', $result);
    }

    /** 非 RunAPI 项目不出现 RunAPI 提示 */
    public function testBuildSystemPromptRegularProjectNoRunapiHint(): void
    {
        $result = $this->invoke('buildSystemPrompt', [[
            'item_id' => 1,
            'item_name' => 'Test',
            'page_id' => 0,
            'page_title' => '',
            'editor_content' => '',
            'role' => 'writer',
            'item_type' => 'regular',
        ]]);

        $this->assertStringNotContainsString('RunAPI 客户端编辑', $result);
    }

    /** 引用标记说明始终存在 */
    public function testBuildSystemPromptAlwaysHasReferenceGuide(): void
    {
        $result = $this->invoke('buildSystemPrompt', [[
            'item_id' => 0,
            'item_name' => '',
            'page_id' => 0,
            'page_title' => '',
            'editor_content' => '',
            'role' => 'reader',
            'item_type' => 'regular',
        ]]);

        $this->assertStringContainsString('[[page:', $result);
        $this->assertStringContainsString('[[item:', $result);
    }

    /** 全局自定义 system prompt 追加 */
    public function testBuildSystemPromptWithCustomPrompt(): void
    {
        $this->host->aiSystemPrompt = '请使用中文回复';

        $result = $this->invoke('buildSystemPrompt', [[
            'item_id' => 0,
            'item_name' => '',
            'page_id' => 0,
            'page_title' => '',
            'editor_content' => '',
            'role' => 'reader',
            'item_type' => 'regular',
        ]]);

        $this->assertStringContainsString('请使用中文回复', $result);
    }

    /** 全局会话（item_id=0）显示全局对话模式提示 */
    public function testBuildSystemPromptGlobalMode(): void
    {
        $result = $this->invoke('buildSystemPrompt', [[
            'item_id' => 0,
            'item_name' => '',
            'page_id' => 0,
            'page_title' => '',
            'editor_content' => '',
            'role' => 'writer',
            'item_type' => 'regular',
        ]]);

        $this->assertStringContainsString('全局对话模式', $result);
    }

    /** 角色信息始终包含 */
    public function testBuildSystemPromptIncludesRole(): void
    {
        $result = $this->invoke('buildSystemPrompt', [[
            'item_id' => 0,
            'item_name' => '',
            'page_id' => 0,
            'page_title' => '',
            'editor_content' => '',
            'role' => 'admin',
            'item_type' => 'regular',
        ]]);

        $this->assertStringContainsString('用户角色：admin', $result);
    }

    // ==================================================================
    //  parseReferences（捕获 SSE 输出，验证文本清理）
    // ==================================================================

    /** 无引用标记时原样返回 */
    public function testParseReferencesNoTags(): void
    {
        $text = '这是一段普通文本，没有引用标记';
        ob_start();
        $result = $this->invoke('parseReferences', [$text]);
        ob_end_clean();

        $this->assertEquals($text, $result);
    }

    /** 系统级引用标记（item_list 等）替换为标记名 */
    public function testParseReferencesSystemLevelTag(): void
    {
        ob_start();
        $result = $this->invoke('parseReferences', ['查看 [[item_list]] 获取项目列表']);
        ob_end_clean();

        $this->assertStringContainsString('item_list', $result);
        $this->assertStringNotContainsString('[[item_list]]', $result);
    }

    /** 多个系统级引用标记 */
    public function testParseReferencesMultipleSystemTags(): void
    {
        ob_start();
        $result = $this->invoke('parseReferences', ['请访问 [[login]] 或 [[home]]']);
        ob_end_clean();

        $this->assertStringContainsString('login', $result);
        $this->assertStringContainsString('home', $result);
        $this->assertStringNotContainsString('[[login]]', $result);
        $this->assertStringNotContainsString('[[home]]', $result);
    }

    /** page 引用标记 — 无 Page 模型时回退到 "页面#ID" */
    public function testParseReferencesPageTagFallback(): void
    {
        ob_start();
        $result = $this->invoke('parseReferences', ['请查看 [[page:999]] 了解详情']);
        ob_end_clean();

        $this->assertStringContainsString('页面#999', $result);
        $this->assertStringNotContainsString('[[page:999]]', $result);
    }

    /** item 引用标记 — 无 Item 时回退到 "项目#ID" */
    public function testParseReferencesItemTagFallback(): void
    {
        // Need to create item table so Item::findById doesn't crash
        $this->createItemTable();

        ob_start();
        $result = $this->invoke('parseReferences', ['参考 [[item:888]] 项目']);
        ob_end_clean();

        $this->assertStringContainsString('项目#888', $result);
        $this->assertStringNotContainsString('[[item:888]]', $result);
    }

    /** 空字符串输入 */
    public function testParseReferencesEmptyInput(): void
    {
        ob_start();
        $result = $this->invoke('parseReferences', ['']);
        ob_end_clean();

        $this->assertEquals('', $result);
    }

    /** 混合文本和引用标记 */
    public function testParseReferencesMixedContent(): void
    {
        $input = '开头文本 [[item_list]] 中间文本 [[login]] 结尾文本';
        ob_start();
        $result = $this->invoke('parseReferences', [$input]);
        ob_end_clean();

        $this->assertStringContainsString('开头文本', $result);
        $this->assertStringContainsString('中间文本', $result);
        $this->assertStringContainsString('结尾文本', $result);
        $this->assertStringNotContainsString('[[', $result);
    }

    /** 支持所有系统级标记 */
    public function testParseReferencesAllSystemLevelTags(): void
    {
        $input = '[[item_list]] [[user_center]] [[messages]] [[login]] [[register]] [[home]]';
        ob_start();
        $result = $this->invoke('parseReferences', [$input]);
        ob_end_clean();

        $this->assertStringContainsString('item_list', $result);
        $this->assertStringContainsString('user_center', $result);
        $this->assertStringContainsString('messages', $result);
        $this->assertStringContainsString('login', $result);
        $this->assertStringContainsString('register', $result);
        $this->assertStringContainsString('home', $result);
    }

    // ==================================================================
    //  extractEditContent
    // ==================================================================

    /** 正常提取 EDIT 标记内容 */
    public function testExtractEditContentNormal(): void
    {
        $content = "这是前言\n<<<EDIT_START>>>\n修改后的内容\n<<<EDIT_END>>>\n这是后语";

        $result = $this->invoke('extractEditContent', [$content]);

        $this->assertNotNull($result);
        $this->assertEquals('修改后的内容', $result['content']);
        $this->assertEquals("这是前言\n\n这是后语", $result['cleaned']);
    }

    /** 没有 EDIT 标记时返回 null */
    public function testExtractEditContentNoMarkers(): void
    {
        $content = '这是一段普通回复，没有任何编辑标记';
        $result = $this->invoke('extractEditContent', [$content]);
        $this->assertNull($result);
    }

    /** 只有开始标记没有结束标记 */
    public function testExtractEditContentOnlyStart(): void
    {
        $content = "<<<EDIT_START>>>\n一些内容但没有结束标记";
        $result = $this->invoke('extractEditContent', [$content]);
        $this->assertNull($result);
    }

    /** 只有结束标记没有开始标记 */
    public function testExtractEditContentOnlyEnd(): void
    {
        $content = "一些内容<<<EDIT_END>>>";
        $result = $this->invoke('extractEditContent', [$content]);
        $this->assertNull($result);
    }

    /** 结束标记在开始标记之前 */
    public function testExtractEditContentEndBeforeStart(): void
    {
        $content = "<<<EDIT_END>>><<<EDIT_START>>>内容";
        $result = $this->invoke('extractEditContent', [$content]);
        $this->assertNull($result);
    }

    /** 空编辑内容 */
    public function testExtractEditContentEmptyBetweenMarkers(): void
    {
        $content = "<<<EDIT_START>>><<<EDIT_END>>>";
        $result = $this->invoke('extractEditContent', [$content]);

        // 空编辑内容 → trim 后为空 → 返回 null
        $this->assertNull($result);
    }

    /** 多行编辑内容 */
    public function testExtractEditContentMultiline(): void
    {
        $editPart = "# 标题\n\n第一段\n\n第二段\n\n- 列表项";
        $content = "请查看修改：\n<<<EDIT_START>>>\n{$editPart}\n<<<EDIT_END>>>\n已为您修改完毕。";

        $result = $this->invoke('extractEditContent', [$content]);

        $this->assertNotNull($result);
        $this->assertEquals($editPart, $result['content']);
    }

    /** 前后无额外文本 */
    public function testExtractEditContentOnlyEditPart(): void
    {
        $content = "<<<EDIT_START>>>纯编辑内容<<<EDIT_END>>>";
        $result = $this->invoke('extractEditContent', [$content]);

        $this->assertNotNull($result);
        $this->assertEquals('纯编辑内容', $result['content']);
        $this->assertEquals('', $result['cleaned']);
    }

    // ==================================================================
    //  buildLlmMessages
    // ==================================================================

    /** 空消息列表 */
    public function testBuildLlmMessagesEmpty(): void
    {
        $result = $this->invoke('buildLlmMessages', [[]]);
        $this->assertEquals([], $result);
    }

    /** 少于20条：全部保留原文 */
    public function testBuildLlmMessagesLessThan20(): void
    {
        $messages = [];
        for ($i = 1; $i <= 10; $i++) {
            $messages[] = ['role' => 'user', 'content' => "msg {$i}"];
            $messages[] = ['role' => 'assistant', 'content' => "reply {$i}"];
        }

        $result = $this->invoke('buildLlmMessages', [$messages]);

        $this->assertCount(20, $result);
        $this->assertEquals('msg 1', $result[0]['content']);
        $this->assertEquals('reply 10', $result[19]['content']);
    }

    /** 恰好20条：全部保留 */
    public function testBuildLlmMessagesExactly20(): void
    {
        $messages = [];
        for ($i = 1; $i <= 20; $i++) {
            $messages[] = ['role' => 'user', 'content' => "m{$i}"];
        }

        $result = $this->invoke('buildLlmMessages', [$messages]);

        $this->assertCount(20, $result);
        $this->assertEquals('m1', $result[0]['content']);
        $this->assertEquals('m20', $result[19]['content']);
    }

    /** 21-40条：assistant 消息截断到300字 */
    public function testBuildLlmMessagesTruncation21to40(): void
    {
        $messages = [];

        // 最旧的消息：assistant（超长，会被截断）
        $longContent = str_repeat('X', 500);
        $messages[] = ['role' => 'assistant', 'content' => $longContent];
        // 再放 21 条 user 消息
        for ($i = 1; $i <= 21; $i++) {
            $messages[] = ['role' => 'user', 'content' => "user msg {$i}"];
        }
        // 共 22 条

        $result = $this->invoke('buildLlmMessages', [$messages]);

        $this->assertCount(22, $result);

        // 最旧的 assistant 消息在时间正序中排第0位（在 reversed 数组中是索引 21）
        // 21 >= 20 所以进入截断区域
        $this->assertStringContainsString('历史对话已压缩', $result[0]['content']);
    }

    /** user 消息在21-40范围不被截断 */
    public function testBuildLlmMessagesUserNotTruncated(): void
    {
        $messages = [];
        $longContent = str_repeat('Y', 500);

        // 最旧的消息：user（超长，不应截断）
        $messages[] = ['role' => 'user', 'content' => $longContent];
        for ($i = 1; $i <= 21; $i++) {
            $messages[] = ['role' => 'user', 'content' => "msg{$i}"];
        }
        // 共 22 条

        $result = $this->invoke('buildLlmMessages', [$messages]);

        // 最旧的 user 消息不被截断（在21-40区域，但 user 不截断）
        $this->assertStringNotContainsString('历史对话已压缩', $result[0]['content']);
        $this->assertEquals($longContent, $result[0]['content']);
    }

    /** 超过40条：41条以后丢弃 */
    public function testBuildLlmMessagesOver40Discarded(): void
    {
        $messages = [];
        for ($i = 1; $i <= 50; $i++) {
            $messages[] = ['role' => 'user', 'content' => "msg{$i}"];
        }

        $result = $this->invoke('buildLlmMessages', [$messages]);

        $this->assertCount(40, $result);
        $this->assertEquals('msg11', $result[0]['content']);
        $this->assertEquals('msg50', $result[39]['content']);
    }

    // ==================================================================
    //  isConsecutiveLimit
    // ==================================================================

    /** 第一次调用不超限 */
    public function testIsConsecutiveLimitFirstCall(): void
    {
        $this->invoke('isConsecutiveLimit', ['search_pages']);
        $this->assertFalse($this->invoke('isConsecutiveLimit', ['search_pages']));
    }

    /** 连续调用同一工具，第4次超限（默认limit=3） */
    public function testIsConsecutiveLimitExceedsDefault(): void
    {
        $this->invoke('isConsecutiveLimit', ['search_pages']);
        $this->invoke('isConsecutiveLimit', ['search_pages']);
        $this->invoke('isConsecutiveLimit', ['search_pages']);
        $result = $this->invoke('isConsecutiveLimit', ['search_pages']);
        $this->assertTrue($result);
    }

    /** 连续3次不超限（limit=3，刚好等于） */
    public function testIsConsecutiveLimitAtLimit(): void
    {
        $this->invoke('isConsecutiveLimit', ['search_pages']);
        $this->invoke('isConsecutiveLimit', ['search_pages']);
        $result = $this->invoke('isConsecutiveLimit', ['search_pages']);
        $this->assertFalse($result, '连续3次不应超限（limit=3时 count=3 不大于 3）');
    }

    /** 切换工具后计数重置 */
    public function testIsConsecutiveLimitDifferentToolResets(): void
    {
        $this->invoke('isConsecutiveLimit', ['search_pages']);
        $this->invoke('isConsecutiveLimit', ['search_pages']);
        $this->invoke('isConsecutiveLimit', ['get_page']);
        $result = $this->invoke('isConsecutiveLimit', ['search_pages']);
        $this->assertFalse($result, '切换工具后再回来，计数应重置为1');
    }

    /** 自定义 limit */
    public function testIsConsecutiveLimitCustomLimit(): void
    {
        $this->invoke('isConsecutiveLimit', ['tool_a', 5]);
        $this->invoke('isConsecutiveLimit', ['tool_a', 5]);
        $this->invoke('isConsecutiveLimit', ['tool_a', 5]);
        $this->invoke('isConsecutiveLimit', ['tool_a', 5]);
        $this->invoke('isConsecutiveLimit', ['tool_a', 5]);
        $result = $this->invoke('isConsecutiveLimit', ['tool_a', 5]);
        $this->assertTrue($result);
    }

    /** 交替调用不同工具不触发限制 */
    public function testIsConsecutiveLimitAlternatingTools(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->invoke('isConsecutiveLimit', ['tool_a']);
            $this->invoke('isConsecutiveLimit', ['tool_b']);
        }
        $this->assertFalse($this->invoke('isConsecutiveLimit', ['tool_a']));
    }

    // ==================================================================
    //  trimToolResult
    // ==================================================================

    /** 短文本不截断 */
    public function testTrimToolResultShortText(): void
    {
        $result = $this->invoke('trimToolResult', ['短文本', 2000]);
        $this->assertEquals('短文本', $result);
    }

    /** 恰好2000字不截断 */
    public function testTrimToolResultExactLimit(): void
    {
        $text = str_repeat('A', 2000);
        $result = $this->invoke('trimToolResult', [$text, 2000]);
        $this->assertEquals($text, $result);
    }

    /** 超过上限：保头尾+中间省略标记（从主版同步的新裁剪策略） */
    public function testTrimToolResultOverLimit(): void
    {
        $text = str_repeat('B', 2500);
        $result = $this->invoke('trimToolResult', [$text, 2000]);
        $this->assertStringContainsString('中间内容已省略', $result);
        $this->assertLessThanOrEqual(2100, mb_strlen($result));
    }

    /** 自定义最大长度：上限过小时退化为只保头部（兼容旧标记） */
    public function testTrimToolResultCustomMax(): void
    {
        $text = str_repeat('C', 200);
        $result = $this->invoke('trimToolResult', [$text, 100]);
        // headChars=60 + tailChars=20 >= 200 才退化；100 上限时 60+20=80 < 200，走头尾保留分支
        $this->assertStringContainsString('中间内容已省略', $result);
    }

    /** 空字符串 */
    public function testTrimToolResultEmpty(): void
    {
        $result = $this->invoke('trimToolResult', ['', 2000]);
        $this->assertEquals('', $result);
    }

    /** Unicode 字符截断（头尾保留策略） */
    public function testTrimToolResultUnicode(): void
    {
        $text = str_repeat('你好', 1500);
        $result = $this->invoke('trimToolResult', [$text, 2000]);
        $this->assertStringContainsString('中间内容已省略', $result);
        $this->assertLessThanOrEqual(2100, mb_strlen($result));
    }

    // ==================================================================
    //  isToolAllowed
    // ==================================================================

    /** admin 角色所有工具可用 */
    public function testIsToolAllowedAdminAllAllowed(): void
    {
        $this->assertTrue($this->invoke('isToolAllowed', ['delete_item', 'admin']));
        $this->assertTrue($this->invoke('isToolAllowed', ['create_page', 'admin']));
        $this->assertTrue($this->invoke('isToolAllowed', ['search_pages', 'admin']));
        $this->assertTrue($this->invoke('isToolAllowed', ['list_items', 'admin']));
    }

    /** 管理员专用工具只有 admin 可用 */
    public function testIsToolAllowedAdminOnlyTools(): void
    {
        $this->assertFalse($this->invoke('isToolAllowed', ['delete_item', 'writer']));
        $this->assertFalse($this->invoke('isToolAllowed', ['delete_page', 'writer']));
        $this->assertFalse($this->invoke('isToolAllowed', ['delete_catalog', 'writer']));
        $this->assertFalse($this->invoke('isToolAllowed', ['delete_item', 'reader']));
        $this->assertFalse($this->invoke('isToolAllowed', ['delete_item', 'guest']));
    }

    /** 写权限工具 writer 可用 */
    public function testIsToolAllowedWriteToolsForWriter(): void
    {
        $this->assertTrue($this->invoke('isToolAllowed', ['create_page', 'writer']));
        $this->assertTrue($this->invoke('isToolAllowed', ['update_page', 'writer']));
        $this->assertTrue($this->invoke('isToolAllowed', ['create_item', 'writer']));
        $this->assertTrue($this->invoke('isToolAllowed', ['upload_attachment', 'writer']));
    }

    /** 写权限工具 reader 不可用 */
    public function testIsToolAllowedWriteToolsForReader(): void
    {
        $this->assertFalse($this->invoke('isToolAllowed', ['create_page', 'reader']));
        $this->assertFalse($this->invoke('isToolAllowed', ['update_page', 'reader']));
        $this->assertFalse($this->invoke('isToolAllowed', ['create_item', 'reader']));
    }

    /** 写权限工具 guest 不可用 */
    public function testIsToolAllowedWriteToolsForGuest(): void
    {
        $this->assertFalse($this->invoke('isToolAllowed', ['create_page', 'guest']));
        $this->assertFalse($this->invoke('isToolAllowed', ['update_page', 'guest']));
    }

    /** 只读工具所有角色可用 */
    public function testIsToolAllowedReadToolsForAll(): void
    {
        $readTools = ['search_pages', 'list_items', 'get_page', 'list_pages', 'get_item'];
        foreach ($readTools as $tool) {
            $this->assertTrue($this->invoke('isToolAllowed', [$tool, 'admin']),
                "admin should have access to {$tool}");
            $this->assertTrue($this->invoke('isToolAllowed', [$tool, 'writer']),
                "writer should have access to {$tool}");
            $this->assertTrue($this->invoke('isToolAllowed', [$tool, 'reader']),
                "reader should have access to {$tool}");
            $this->assertTrue($this->invoke('isToolAllowed', [$tool, 'guest']),
                "guest should have access to {$tool}");
        }
    }

    /** 未知工具名默认允许 */
    public function testIsToolAllowedUnknownTool(): void
    {
        $this->assertTrue($this->invoke('isToolAllowed', ['unknown_tool', 'admin']));
        $this->assertTrue($this->invoke('isToolAllowed', ['unknown_tool', 'reader']));
        $this->assertTrue($this->invoke('isToolAllowed', ['unknown_tool', 'guest']));
    }

    // ==================================================================
    //  trackMcpWriteOp
    // ==================================================================

    /** 只读工具返回 null */
    public function testTrackMcpWriteOpReadToolReturnsNull(): void
    {
        $result = $this->invoke('trackMcpWriteOp', [
            'search_pages', ['keyword' => 'test'], '{"results":[]}', 1,
        ]);
        $this->assertNull($result);
    }

    /** update_page 写操作追踪 */
    public function testTrackMcpWriteOpUpdatePage(): void
    {
        $result = $this->invoke('trackMcpWriteOp', [
            'update_page', ['page_id' => 10], '{"ok":true}', 1,
        ]);

        $this->assertNotNull($result);
        $this->assertEquals('update', $result['action']);
        $this->assertEquals(1, $result['item_id']);
        $this->assertEquals(10, $result['page_id']);
    }

    /** delete_page 写操作追踪 */
    public function testTrackMcpWriteOpDeletePage(): void
    {
        $result = $this->invoke('trackMcpWriteOp', [
            'delete_page', ['page_id' => 5], '{"ok":true}', 1,
        ]);

        $this->assertNotNull($result);
        $this->assertEquals('delete', $result['action']);
        $this->assertEquals(5, $result['page_id']);
    }

    /** create_item 项目级操作 */
    public function testTrackMcpWriteOpCreateItem(): void
    {
        $this->createItemTable();

        $result = $this->invoke('trackMcpWriteOp', [
            'create_item', ['item_id' => 99], '{"ok":true}', 1,
        ]);

        $this->assertNotNull($result);
        $this->assertEquals('create_item', $result['action']);
        $this->assertArrayNotHasKey('page_id', $result);
    }

    /** get_page 不是写操作 */
    public function testTrackMcpWriteOpGetPageNotTracked(): void
    {
        $result = $this->invoke('trackMcpWriteOp', [
            'get_page', ['page_id' => 10], '{"content":"..."}', 1,
        ]);
        $this->assertNull($result);
    }

    /** list_items 不是写操作 */
    public function testTrackMcpWriteOpListItemsNotTracked(): void
    {
        $result = $this->invoke('trackMcpWriteOp', [
            'list_items', [], '{"items":[]}', 0,
        ]);
        $this->assertNull($result);
    }

    /** create_catalog 写操作追踪 */
    public function testTrackMcpWriteOpCreateCatalog(): void
    {
        $result = $this->invoke('trackMcpWriteOp', [
            'create_catalog', ['cat_id' => 7], '{"ok":true}', 1,
        ]);

        $this->assertNotNull($result);
        $this->assertEquals('create', $result['action']);
        $this->assertEquals(7, $result['cat_id']);
    }

    // ==================================================================
    //  getToolStatusText
    // ==================================================================

    /** 已知工具返回中文状态 */
    public function testGetToolStatusTextKnownTools(): void
    {
        $this->assertEquals('正在搜索文档...', $this->invoke('getToolStatusText', ['search_pages']));
        $this->assertEquals('正在创建页面...', $this->invoke('getToolStatusText', ['create_page']));
        $this->assertEquals('正在更新页面...', $this->invoke('getToolStatusText', ['update_page']));
        $this->assertEquals('正在获取项目列表...', $this->invoke('getToolStatusText', ['list_items']));
    }

    /** 未知工具返回默认状态 */
    public function testGetToolStatusTextUnknownTool(): void
    {
        $this->assertEquals('正在处理...', $this->invoke('getToolStatusText', ['unknown_tool']));
    }

    // ==================================================================
    //  parsePageContext（TC-006）
    // ==================================================================

    /** TC-006: /page/edit/{item_id}/{page_id} → item_id, page_id */
    public function testParsePageContextEditRoute(): void
    {
        $result = $this->invoke('parsePageContext', ['/page/edit/100/200']);
        $this->assertSame(100, $result['item_id']);
        $this->assertSame(200, $result['page_id']);
    }

    /** TC-006: /page/{page_id} → page_id only */
    public function testParsePageContextPageRoute(): void
    {
        $result = $this->invoke('parsePageContext', ['/page/123']);
        $this->assertNull($result['item_id']);
        $this->assertSame(123, $result['page_id']);
    }

    /** TC-006: /page/diff/{page_id}/{history_id} → page_id only */
    public function testParsePageContextDiffRoute(): void
    {
        $result = $this->invoke('parsePageContext', ['/page/diff/456/789']);
        $this->assertNull($result['item_id']);
        $this->assertSame(456, $result['page_id']);
    }

    /** TC-006: /{item_id}/{page_id} → item_id + page_id */
    public function testParsePageContextItemPageRoute(): void
    {
        $result = $this->invoke('parsePageContext', ['/10/20']);
        $this->assertSame(10, $result['item_id']);
        $this->assertSame(20, $result['page_id']);
    }

    /** TC-006: /{item_id} → item_id only */
    public function testParsePageContextItemOnlyRoute(): void
    {
        $result = $this->invoke('parsePageContext', ['/42']);
        $this->assertSame(42, $result['item_id']);
        $this->assertNull($result['page_id']);
    }

    /** TC-006: /item/setting/{item_id} → item_id */
    public function testParsePageContextItemSettingRoute(): void
    {
        $result = $this->invoke('parsePageContext', ['/item/setting/55']);
        $this->assertSame(55, $result['item_id']);
        $this->assertNull($result['page_id']);
    }

    /** TC-006: hash routing /#/page/123 */
    public function testParsePageContextHashRoute(): void
    {
        $result = $this->invoke('parsePageContext', ['/#/page/123']);
        $this->assertSame(123, $result['page_id']);
        $this->assertNull($result['item_id']);
    }

    /** TC-006: query params are stripped */
    public function testParsePageContextWithQueryParams(): void
    {
        $result = $this->invoke('parsePageContext', ['/page/edit/100/200?key=abc&foo=bar']);
        $this->assertSame(100, $result['item_id']);
        $this->assertSame(200, $result['page_id']);
    }

    /** TC-006: empty string returns null/null */
    public function testParsePageContextEmptyString(): void
    {
        $result = $this->invoke('parsePageContext', ['']);
        $this->assertNull($result['item_id']);
        $this->assertNull($result['page_id']);
    }

    // ==================================================================
    //  resolveRole（TC-020）
    // ==================================================================

    /** TC-020: isGuest=true → 'guest' */
    public function testResolveRoleGuest(): void
    {
        $result = $this->invoke('resolveRole', [0, 0, true]);
        $this->assertEquals('guest', $result);
    }

    /** TC-020: global session + groupid=1 → 'admin' */
    public function testResolveRoleGlobalAdmin(): void
    {
        \Illuminate\Database\Capsule\Manager::table('user')->insert([
            'uid' => 1, 'username' => 'admin', 'groupid' => 1,
        ]);

        $result = $this->invoke('resolveRole', [1, 0, false]);
        $this->assertEquals('admin', $result);
    }

    /** TC-020: global session + normal user → 'writer' */
    public function testResolveRoleGlobalWriter(): void
    {
        \Illuminate\Database\Capsule\Manager::table('user')->insert([
            'uid' => 2, 'username' => 'normal', 'groupid' => 0,
        ]);

        $result = $this->invoke('resolveRole', [2, 0, false]);
        $this->assertEquals('writer', $result);
    }

    /** TC-020: project session + creator (item.uid) → 'admin' */
    public function testResolveRoleProjectCreatorAdmin(): void
    {
        \Illuminate\Database\Capsule\Manager::table('user')->insert([
            'uid' => 10, 'username' => 'creator', 'groupid' => 0,
        ]);
        \Illuminate\Database\Capsule\Manager::table('item')->insert([
            'item_id' => 100, 'item_name' => 'Test', 'uid' => 10,
        ]);

        $result = $this->invoke('resolveRole', [10, 100, false]);
        $this->assertEquals('admin', $result);
    }

    /** TC-020: project session + item_member(group_id=1) → 'writer' */
    public function testResolveRoleProjectMemberWriter(): void
    {
        \Illuminate\Database\Capsule\Manager::table('user')->insert([
            'uid' => 20, 'username' => 'member', 'groupid' => 0,
        ]);
        \Illuminate\Database\Capsule\Manager::table('item')->insert([
            'item_id' => 200, 'item_name' => 'Test2', 'uid' => 99,
        ]);
        \Illuminate\Database\Capsule\Manager::table('item_member')->insert([
            'item_id' => 200, 'uid' => 20, 'member_group_id' => 1,
        ]);

        $result = $this->invoke('resolveRole', [20, 200, false]);
        $this->assertEquals('writer', $result);
    }

    /** TC-020: project session + read-only → 'reader' */
    public function testResolveRoleProjectReader(): void
    {
        \Illuminate\Database\Capsule\Manager::table('user')->insert([
            'uid' => 30, 'username' => 'reader_user', 'groupid' => 0,
        ]);
        \Illuminate\Database\Capsule\Manager::table('item')->insert([
            'item_id' => 300, 'item_name' => 'Test3', 'uid' => 99,
        ]);

        $result = $this->invoke('resolveRole', [30, 300, false]);
        $this->assertEquals('reader', $result);
    }

    // ==================================================================
    //  getPageContentSnippet（TC-063）
    // ==================================================================

    /** TC-063: page_content 超 2000 字截断 + 截断标记 */
    public function testGetPageContentSnippetTruncated(): void
    {
        $itemId = 1;
        $pageId = 500;
        $longContent = str_repeat('X', 2500);

        // 开源版 Page 模型使用单表 page 存储 page_content
        \Illuminate\Database\Capsule\Manager::table('page')->insert([
            'page_id' => $pageId, 'item_id' => $itemId, 'page_title' => 'Test',
            'page_content' => $longContent,
        ]);

        $result = $this->invoke('getPageContentSnippet', [$pageId, $itemId]);
        $this->assertStringContainsString('...（页面内容已截断）', $result);
        $this->assertLessThanOrEqual(2020, mb_strlen($result));
    }

    /** TC-063: page 不存在时返回空字符串 */
    public function testGetPageContentSnippetPageNotFound(): void
    {
        $result = $this->invoke('getPageContentSnippet', [99999, 0]);
        $this->assertEquals('', $result);
    }

    /** TC-063: item_id>0 且页面归属不匹配时返回空字符串 */
    public function testGetPageContentSnippetItemMismatch(): void
    {
        $itemId = 1;
        $otherItemId = 2;
        $pageId = 501;

        \Illuminate\Database\Capsule\Manager::table('page')->insert([
            'page_id' => $pageId, 'item_id' => $itemId, 'page_title' => 'Test',
            'page_content' => 'hello',
        ]);

        $result = $this->invoke('getPageContentSnippet', [$pageId, $otherItemId]);
        $this->assertEquals('', $result);
    }

    /** TC-063: pageId<=0 返回空字符串 */
    public function testGetPageContentSnippetInvalidPageId(): void
    {
        $result = $this->invoke('getPageContentSnippet', [0, 0]);
        $this->assertEquals('', $result);
    }

    /** TC-063: 正常页面内容（未超长）原样返回 */
    public function testGetPageContentSnippetNormalContent(): void
    {
        $itemId = 3;
        $pageId = 502;
        $content = 'This is normal page content';

        \Illuminate\Database\Capsule\Manager::table('page')->insert([
            'page_id' => $pageId, 'item_id' => $itemId, 'page_title' => 'Normal',
            'page_content' => $content,
        ]);

        $result = $this->invoke('getPageContentSnippet', [$pageId, $itemId]);
        $this->assertEquals($content, $result);
    }

    /** TC-063: Page::findById 抛异常时捕获并返回空字符串 */
    public function testGetPageContentSnippetException(): void
    {
        $schema = \Illuminate\Database\Capsule\Manager::connection()->getSchemaBuilder();
        // 临时删除 page 表，强制 Page::findById 触发异常
        $schema->drop('page');

        $result = $this->invoke('getPageContentSnippet', [600, 0]);
        $this->assertEquals('', $result);

        // 恢复表，避免影响后续测试
        $this->createPageTable();
    }

    // ==================================================================
    //  convertLargeIntegersToString（TC-092）
    //  （在 McpServer 中，通过 Closure 测试）
    // ==================================================================

    /** TC-092: >9007199254740991 的整数转为字符串 */
    public function testConvertLargeIntegersToStringBasic(): void
    {
        $mcp = new \App\Mcp\McpServer();
        $ref = new \ReflectionMethod($mcp, 'convertLargeIntegersToString');
        $ref->setAccessible(true);

        $big = 9007199254740992; // > max safe integer
        $result = $ref->invoke($mcp, $big);
        $this->assertIsString($result);
        $this->assertEquals('9007199254740992', $result);
    }

    /** TC-092: 正常整数不变 */
    public function testConvertLargeIntegersNormalInt(): void
    {
        $mcp = new \App\Mcp\McpServer();
        $ref = new \ReflectionMethod($mcp, 'convertLargeIntegersToString');
        $ref->setAccessible(true);

        $result = $ref->invoke($mcp, 42);
        $this->assertSame(42, $result);
    }

    /** TC-092: 数组中大整数转为字符串 */
    public function testConvertLargeIntegersInArray(): void
    {
        $mcp = new \App\Mcp\McpServer();
        $ref = new \ReflectionMethod($mcp, 'convertLargeIntegersToString');
        $ref->setAccessible(true);

        $data = ['id' => 9007199254740993, 'name' => 'test'];
        $result = $ref->invoke($mcp, $data);
        $this->assertIsString($result['id']);
        $this->assertEquals('9007199254740993', $result['id']);
        $this->assertEquals('test', $result['name']);
    }

    /** TC-092: 对象中大整数转为字符串 */
    public function testConvertLargeIntegersInObject(): void
    {
        $mcp = new \App\Mcp\McpServer();
        $ref = new \ReflectionMethod($mcp, 'convertLargeIntegersToString');
        $ref->setAccessible(true);

        $obj = new \stdClass();
        $obj->id = 9007199254740994;
        $obj->name = 'test';
        $result = $ref->invoke($mcp, $obj);
        $this->assertIsString($result->id);
        $this->assertEquals('9007199254740994', $result->id);
    }

    /** TC-092: 递归深度限制 6 层 — 深层嵌套数组不再递归 */
    public function testConvertLargeIntegersDepthLimit(): void
    {
        $mcp = new \App\Mcp\McpServer();
        $ref = new \ReflectionMethod($mcp, 'convertLargeIntegersToString');
        $ref->setAccessible(true);

        // Build 8 nested layers: L0 -> L1 -> ... -> L7 -> ['big' => $big]
        $big = 9007199254740995;
        $data = ['big' => $big];
        for ($i = 0; $i < 8; $i++) {
            $data = ['nested' => $data];
        }

        $result = $ref->invoke($mcp, $data);

        // Depth 0 processes L0, recurses into L1 (depth 1), ..., L5→L6 (depth 6).
        // At depth 6, $depth >= 6 triggers early return — L6 returned as-is.
        // So big ints inside L7+ are NOT converted.
        // Walk down 6 levels to reach L6 (unprocessed):
        $current = $result;
        for ($i = 0; $i < 6; $i++) {
            $current = $current['nested'];
        }
        // $current = L6 = ['nested' => L7], L7 = ['nested' => L8], L8 = ['big' => $big]
        // L6 was returned as-is at depth limit, so L8['big'] was never processed
        $this->assertIsInt(
            $current['nested']['nested']['big'],
            'Big int inside array beyond depth 6 should NOT be converted'
        );
    }

    /** TC-092: 字符串值不受影响 */
    public function testConvertLargeIntegersStringPassthrough(): void
    {
        $mcp = new \App\Mcp\McpServer();
        $ref = new \ReflectionMethod($mcp, 'convertLargeIntegersToString');
        $ref->setAccessible(true);

        $result = $ref->invoke($mcp, 'hello');
        $this->assertEquals('hello', $result);
    }
}
