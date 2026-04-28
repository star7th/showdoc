<?php
/**
 * Mock 正则路径功能测试引导文件
 *
 * 使用 SQLite 内存数据库隔离测试，不依赖真实数据库。
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

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

// 创建 mock 表（与 Mock Model 使用的一致）
$capsule->getConnection()->getSchemaBuilder()->create('mock', function ($table) {
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
