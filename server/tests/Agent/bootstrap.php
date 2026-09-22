<?php
/**
 * Agent 测试引导文件（开源版）
 *
 * 使用 SQLite 内存数据库隔离测试，不依赖真实数据库。
 * 预创建所有 Agent 测试需要的表，避免每个测试文件重复初始化。
 *
 * 与主版的差异：
 *   - 移除 vip / user_credits / credit_usage_log 表（开源版无积分体系）
 *   - 移除 bad_keywords / bad_keyword_hits / item_whitelist 表（开源版无敏感词）
 *   - ai_chat_messages 表移除积分专属列（tokens_input/credits_used 等）
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

// 标记测试环境
define('PHPUNIT_TESTSUITE', true);
define('RUNTIME_PATH', dirname(__DIR__, 2) . '/app/Runtime/');

use Illuminate\Database\Capsule\Manager as DB;

// 初始化 Eloquent Capsule（SQLite 内存数据库）
$capsule = new DB();
$capsule->addConnection([
    'driver'   => 'sqlite',
    'database' => ':memory:',
    'prefix'   => '',
    'options'  => [PDO::ATTR_STRINGIFY_FETCHES => true],
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// 注册 SQLite 自定义函数（模拟 MySQL GET_LOCK/RELEASE_LOCK）
$pdo = $capsule->getConnection()->getPdo();
$pdo->sqliteCreateFunction('GET_LOCK', function ($name, $timeout) {
    return 1;
}, 2);
$pdo->sqliteCreateFunction('RELEASE_LOCK', function ($name) {
    return 1;
}, 1);

// 创建所有表
$schema = $capsule->getConnection()->getSchemaBuilder();

// ==================================================================
//  核心表
// ==================================================================

$schema->create('options', function ($table) {
    $table->increments('id');
    $table->string('option_name', 255)->default('');
    $table->text('option_value')->nullable();
});

$schema->create('user', function ($table) {
    $table->increments('uid');
    $table->string('username', 255)->default('');
    $table->string('password', 255)->default('');
    $table->integer('groupid')->default(0);
    $table->string('email', 255)->default('');
    $table->integer('email_verify')->default(0);
});

$schema->create('user_token', function ($table) {
    $table->increments('id');
    $table->integer('uid')->default(0);
    $table->string('token', 255)->default('');
    $table->integer('token_expire')->default(0);
    $table->string('ip', 50)->nullable();
    $table->integer('addtime')->default(0);
    $table->string('user_agent', 500)->default('');
    $table->integer('last_check_time')->default(0);
});

$schema->create('item', function ($table) {
    $table->increments('item_id');
    $table->string('item_name', 255)->default('');
    $table->integer('uid')->default(0);
    $table->integer('item_type')->default(1);
    $table->integer('is_del')->default(0);
    $table->string('password', 255)->nullable();
    $table->string('item_domain', 255)->nullable();
    $table->integer('last_update_time')->default(0);
    $table->integer('addtime')->default(0);
});

$schema->create('item_member', function ($table) {
    $table->increments('id');
    $table->integer('item_id')->default(0);
    $table->integer('uid')->default(0);
    $table->integer('member_group_id')->default(0);
});

$schema->create('team_item_member', function ($table) {
    $table->increments('id');
    $table->integer('item_id')->default(0);
    $table->integer('member_uid')->default(0);
    $table->integer('member_group_id')->default(0);
});

$schema->create('item_ai_config', function ($table) {
    $table->increments('id');
    $table->integer('item_id')->default(0);
    $table->integer('enabled')->default(0);
    $table->integer('guest_enabled')->default(0);
    $table->integer('dialog_collapsed')->default(1);
    $table->text('welcome_message')->nullable();
    $table->text('system_prompt')->nullable();
    $table->integer('addtime')->default(0);
    $table->integer('updatetime')->default(0);
});

$schema->create('catalog', function ($table) {
    $table->increments('cat_id');
    $table->integer('item_id')->default(0);
    $table->string('cat_name', 255)->default('');
    $table->integer('parent_cat_id')->default(0);
    $table->integer('s_number')->default(99);
    $table->integer('addtime')->default(0);
    $table->integer('level')->default(1);
});

// 开源版 item_change_log：单表（与主版分表架构不同）。
// MCP get_recent_changes / ItemChangeLog 模型读写此表
$schema->create('item_change_log', function ($table) {
    $table->increments('id');
    $table->integer('uid')->default(0);
    $table->integer('item_id')->default(0);
    $table->string('op_action_type', 50)->default('');
    $table->string('op_object_type', 50)->default('');
    $table->integer('op_object_id')->default(0);
    $table->string('op_object_name', 255)->default('');
    $table->string('remark', 255)->default('');
    $table->string('optime', 50)->default('');
});

// 开源版 page 表：单表存储（含 page_content），与主版分表架构不同。
// 开源版 Page/PageHandler 全部读写此表，需包含 PageHandler 用到的全部列
// （s_number / addtime / author_uid / author_username / is_draft / page_comments / ext_info）。
$schema->create('page', function ($table) {
    $table->increments('page_id');
    $table->integer('item_id')->default(0);
    $table->integer('cat_id')->default(0);
    $table->string('page_title', 255)->default('');
    $table->text('page_content')->nullable();
    $table->integer('is_del')->default(0);
    $table->integer('is_draft')->default(0);
    $table->integer('s_number')->default(99);
    $table->integer('addtime')->default(0);
    $table->integer('author_uid')->default(0);
    $table->string('author_username', 255)->default('');
    $table->text('page_comments')->nullable();
    $table->text('ext_info')->nullable();
});

// 开源版 ai_chat_sessions（与主版一致，无积分字段）
$schema->create('ai_chat_sessions', function ($table) {
    $table->increments('id');
    $table->integer('uid')->default(0);
    $table->integer('item_id')->default(0);
    $table->string('guest_token', 64)->nullable();
    $table->integer('last_message_at')->default(0);
    $table->integer('created_at')->default(0);
    $table->integer('is_del')->default(0);
});

// 开源版 ai_chat_messages（精简版：仅 6 核心字段，无积分列）
$schema->create('ai_chat_messages', function ($table) {
    $table->increments('id');
    $table->integer('session_id')->default(0);
    $table->string('role', 20)->default('');
    $table->text('content')->nullable();
    $table->integer('feedback')->default(0);
    $table->integer('created_at')->default(0);
});

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

$schema->create('file_page', function ($table) {
    $table->increments('id');
    $table->integer('file_id')->default(0);
    $table->integer('item_id')->default(0);
    $table->integer('page_id')->default(0);
});

$schema->create('single_page', function ($table) {
    $table->increments('id');
    $table->string('unique_key', 255)->default('');
    $table->integer('page_id')->default(0);
    $table->integer('expire_time')->default(0);
});

$schema->create('upload_file', function ($table) {
    $table->increments('file_id');
    $table->integer('uid')->default(0);
    $table->integer('item_id')->default(0);
    $table->string('display_name', 255)->default('');
    $table->string('file_type', 100)->default('');
    $table->integer('file_size')->default(0);
    $table->string('sign', 255)->default('');
    $table->text('real_url')->nullable();
    $table->integer('addtime')->default(0);
});

$schema->create('user_ai_token', function ($table) {
    $table->increments('id');
    $table->integer('uid')->default(0);
    $table->string('name', 255)->nullable();
    $table->string('token', 255)->default('');
    $table->string('permission', 20)->default('write');
    $table->string('scope', 20)->default('all');
    $table->text('allowed_items')->nullable();
    $table->integer('can_create_item')->default(1);
    $table->integer('can_delete_item')->default(0);
    $table->integer('auto_add_created_item')->default(1);
    $table->string('expires_at', 50)->nullable();
    $table->integer('is_active')->default(1);
    $table->string('last_used_at', 50)->nullable();
    $table->integer('addtime')->default(0);
});

$schema->create('kanban_activity_log', function ($table) {
    $table->increments('id');
    $table->integer('item_id')->default(0);
    $table->integer('page_id')->default(0);
    $table->string('event_type', 64)->default('');
    $table->text('event_data')->nullable();
    $table->integer('operator_uid')->default(0);
    $table->integer('addtime')->default(0);
});

$schema->create('mock', function ($table) {
    $table->increments('id');
    $table->string('unique_key', 255)->default('');
    $table->integer('uid')->default(0);
    $table->integer('page_id')->default(0);
    $table->integer('item_id')->default(0);
    $table->text('template')->nullable();
    $table->string('path', 2048)->default('/');
    $table->string('addtime', 50)->nullable();
    $table->string('last_update_time', 50)->nullable();
    $table->integer('view_times')->default(0);
});

// ==================================================================
//  分表 page_1 ~ page_100
// ==================================================================

for ($i = 1; $i <= 100; $i++) {
    $schema->create('page_' . $i, function ($table) {
        $table->increments('page_id');
        $table->integer('item_id')->default(0);
        $table->integer('cat_id')->default(0);
        $table->string('page_title', 255)->default('');
        $table->text('page_content')->nullable();
        $table->integer('is_del')->default(0);
        $table->integer('is_draft')->default(0);
        $table->integer('s_number')->default(99);
        $table->integer('addtime')->default(0);
        $table->integer('author_uid')->default(0);
        $table->string('author_username', 255)->default('');
        $table->text('ext_info')->nullable();
    });
}

// ==================================================================
//  page_history + 分表 page_history_1 ~ page_history_100
// ==================================================================

$schema->create('page_history', function ($table) {
    $table->increments('page_history_id');
    $table->integer('page_id')->default(0);
    $table->integer('item_id')->default(0);
    $table->integer('cat_id')->default(0);
    $table->string('page_title', 255)->default('');
    $table->text('page_content')->nullable();
    $table->text('page_comments')->nullable();
    $table->integer('s_number')->default(0);
    $table->integer('addtime')->default(0);
    $table->integer('author_uid')->default(0);
    $table->string('author_username', 255)->default('');
    $table->text('ext_info')->nullable();
});

for ($i = 1; $i <= 100; $i++) {
    $schema->create('page_history_' . $i, function ($table) {
        $table->increments('page_history_id');
        $table->integer('page_id')->default(0);
        $table->integer('item_id')->default(0);
        $table->integer('cat_id')->default(0);
        $table->string('page_title', 255)->default('');
        $table->text('page_content')->nullable();
        $table->text('page_comments')->nullable();
        $table->integer('s_number')->default(0);
        $table->integer('addtime')->default(0);
        $table->integer('author_uid')->default(0);
        $table->string('author_username', 255)->default('');
        $table->text('ext_info')->nullable();
    });
}
