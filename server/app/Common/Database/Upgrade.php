<?php

namespace App\Common\Database;

use Illuminate\Database\Capsule\Manager as DB;
use App\Model\Options;

/**
 * 数据库升级类
 * 
 * 负责检查数据库版本并执行必要的升级操作
 */
class Upgrade
{
    /**
     * 当前数据库版本号
     * 注意：如果更新数据库结构，务必更改此版本号
     */
    private const CURRENT_VERSION = 28;

    /**
     * 检查并执行数据库升级
     * 
     * @return bool 是否成功
     */
    public static function checkAndUpgrade(): bool
    {
        try {
            $dbVersion = (int) Options::get('db_version_num', 0);
            
            if ($dbVersion < self::CURRENT_VERSION) {
                $result = self::updateSqlite();
                if ($result) {
                    Options::set('db_version_num', self::CURRENT_VERSION);
                }
                return $result;
            }
            
            return true;
        } catch (\Throwable $e) {
            // 升级失败时记录日志，但不阻止应用启动
            error_log('Database upgrade failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 执行 SQLite 数据库升级
     * 
     * @return bool 是否成功
     */
    private static function updateSqlite(): bool
    {
        try {
            // catalog表增加parent_cat_id字段
            if (!self::isColumnExist('catalog', 'parent_cat_id')) {
                DB::statement("ALTER TABLE catalog ADD parent_cat_id INT(10) NOT NULL DEFAULT '0'");
            }

            // catalog表增加level字段
            if (!self::isColumnExist('catalog', 'level')) {
                DB::statement("ALTER TABLE catalog ADD level INT(10) NOT NULL DEFAULT '2'");
            }

            // item表增加item_domain字段
            if (!self::isColumnExist('item', 'item_domain')) {
                DB::statement("ALTER TABLE item ADD item_domain text NOT NULL DEFAULT ''");
            }

            // 创建user_token表
            DB::statement("CREATE TABLE IF NOT EXISTS `user_token` (
                `id` INTEGER PRIMARY KEY,
                `uid` int(10) NOT NULL DEFAULT '0',
                `token` CHAR(200) NOT NULL DEFAULT '',
                `token_expire` int(11) NOT NULL DEFAULT '0',
                `ip` CHAR(200) NOT NULL DEFAULT '',
                `addtime` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建template表
            DB::statement("CREATE TABLE IF NOT EXISTS `template` (
                `id` INTEGER PRIMARY KEY,
                `uid` int(10) NOT NULL DEFAULT '0',
                `username` CHAR(200) NOT NULL DEFAULT '',
                `template_title` CHAR(200) NOT NULL DEFAULT '',
                `template_content` text NOT NULL DEFAULT '',
                `addtime` int(11) NOT NULL DEFAULT '0'
            )");

            // page表增加page_comments字段
            if (!self::isColumnExist('page', 'page_comments')) {
                DB::statement("ALTER TABLE page ADD page_comments text NOT NULL DEFAULT ''");
            }

            // page_history表增加page_comments字段
            if (!self::isColumnExist('page_history', 'page_comments')) {
                DB::statement("ALTER TABLE page_history ADD page_comments text NOT NULL DEFAULT ''");
            }

            // item_member表增加member_group_id字段
            if (!self::isColumnExist('item_member', 'member_group_id')) {
                DB::statement("ALTER TABLE item_member ADD member_group_id INT(1) NOT NULL DEFAULT '1'");
            }

            // item表增加item_type字段
            if (!self::isColumnExist('item', 'item_type')) {
                DB::statement("ALTER TABLE item ADD item_type INT(1) NOT NULL DEFAULT '1'");
            }

            // 创建options表
            DB::statement("CREATE TABLE IF NOT EXISTS `options` (
                `option_id` INTEGER PRIMARY KEY,
                `option_name` CHAR(200) NOT NULL UNIQUE,
                `option_value` CHAR(200) NOT NULL
            )");

            // 创建item_token表
            DB::statement("CREATE TABLE IF NOT EXISTS `item_token` (
                `id` INTEGER PRIMARY KEY,
                `item_id` int(11) NOT NULL DEFAULT '0',
                `api_key` CHAR(200) NOT NULL UNIQUE,
                `api_token` CHAR(200) NOT NULL,
                `addtime` int(11) NOT NULL DEFAULT '0',
                `last_check_time` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建item_top表
            DB::statement("CREATE TABLE IF NOT EXISTS `item_top` (
                `id` INTEGER PRIMARY KEY,
                `item_id` int(11) NOT NULL DEFAULT '0',
                `uid` int(11) NOT NULL DEFAULT '0',
                `addtime` int(11) NOT NULL DEFAULT '0'
            )");

            // item表增加is_archived字段
            if (!self::isColumnExist('item', 'is_archived')) {
                DB::statement("ALTER TABLE item ADD is_archived INT(1) NOT NULL DEFAULT '0'");
            }

            // 管理员账户和权限
            $user = DB::table('user')->where('username', 'showdoc')->first();
            if ($user) {
                DB::table('user')->where('username', 'showdoc')->update(['groupid' => 1]);
            } else {
                DB::table('user')->insert([
                    'username' => 'showdoc',
                    'groupid' => 1,
                    'password' => 'a89da13684490eb9ec9e613f91d24d00',
                    'reg_time' => time()
                ]);
            }

            // item表增加is_del字段
            if (!self::isColumnExist('item', 'is_del')) {
                DB::statement("ALTER TABLE item ADD is_del INT(1) NOT NULL DEFAULT '0'");
            }

            // page表增加is_del字段
            if (!self::isColumnExist('page', 'is_del')) {
                DB::statement("ALTER TABLE page ADD is_del INT(1) NOT NULL DEFAULT '0'");
            }

            // page表增加page_addtime字段
            if (!self::isColumnExist('page', 'page_addtime')) {
                DB::statement("ALTER TABLE page ADD page_addtime INT(11) NOT NULL DEFAULT '0'");
            }

            // 创建team表
            DB::statement("CREATE TABLE IF NOT EXISTS `team` (
                `id` INTEGER PRIMARY KEY,
                `team_name` CHAR(200) NOT NULL DEFAULT '',
                `uid` int(11) NOT NULL DEFAULT '0',
                `username` CHAR(200) NOT NULL DEFAULT '',
                `addtime` int(11) NOT NULL DEFAULT '0',
                `last_update_time` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建team_item表
            DB::statement("CREATE TABLE IF NOT EXISTS `team_item` (
                `id` INTEGER PRIMARY KEY,
                `team_id` int(11) NOT NULL DEFAULT '0',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `addtime` int(11) NOT NULL DEFAULT '0',
                `last_update_time` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建team_item_member表
            DB::statement("CREATE TABLE IF NOT EXISTS `team_item_member` (
                `id` INTEGER PRIMARY KEY,
                `team_id` int(11) NOT NULL DEFAULT '0',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `member_group_id` int(11) NOT NULL DEFAULT '0',
                `member_uid` int(11) NOT NULL DEFAULT '0',
                `member_username` CHAR(200) NOT NULL DEFAULT '',
                `addtime` int(11) NOT NULL DEFAULT '0',
                `last_update_time` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建team_member表
            DB::statement("CREATE TABLE IF NOT EXISTS `team_member` (
                `id` INTEGER PRIMARY KEY,
                `team_id` int(11) NOT NULL DEFAULT '0',
                `member_uid` int(11) NOT NULL DEFAULT '0',
                `member_username` CHAR(200) NOT NULL DEFAULT '',
                `addtime` int(11) NOT NULL DEFAULT '0',
                `last_update_time` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建upload_file表
            DB::statement("CREATE TABLE IF NOT EXISTS `upload_file` (
                `file_id` INTEGER PRIMARY KEY,
                `sign` CHAR(200) NOT NULL DEFAULT '',
                `display_name` CHAR(200) NOT NULL DEFAULT '',
                `file_type` CHAR(200) NOT NULL DEFAULT '',
                `file_size` CHAR(200) NOT NULL DEFAULT '',
                `uid` int(11) NOT NULL DEFAULT '0',
                `page_id` int(11) NOT NULL DEFAULT '0',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `visit_times` int(11) NOT NULL DEFAULT '0',
                `addtime` int(11) NOT NULL DEFAULT '0',
                `real_url` CHAR(200) NOT NULL DEFAULT '',
                `last_update_time` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建item_sort表
            DB::statement("CREATE TABLE IF NOT EXISTS `item_sort` (
                `id` INTEGER PRIMARY KEY,
                `uid` int(10) NOT NULL DEFAULT '0',
                `item_sort_data` text NOT NULL DEFAULT '',
                `addtime` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建single_page表
            DB::statement("CREATE TABLE IF NOT EXISTS `single_page` (
                `id` INTEGER PRIMARY KEY,
                `unique_key` CHAR(200) NOT NULL DEFAULT '',
                `page_id` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建captcha表
            DB::statement("CREATE TABLE IF NOT EXISTS `captcha` (
                `captcha_id` INTEGER PRIMARY KEY,
                `mobile` CHAR(200) NOT NULL DEFAULT '',
                `captcha` CHAR(200) NOT NULL DEFAULT '',
                `expire_time` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建recycle表
            DB::statement("CREATE TABLE IF NOT EXISTS `recycle` (
                `id` INTEGER PRIMARY KEY,
                `item_id` int(11) NOT NULL DEFAULT '0',
                `page_id` int(11) NOT NULL DEFAULT '0',
                `page_title` CHAR(200) NOT NULL DEFAULT '',
                `del_by_uid` int(11) NOT NULL DEFAULT '0',
                `del_by_username` CHAR(200) NOT NULL DEFAULT '',
                `del_time` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建page_lock表
            DB::statement("CREATE TABLE IF NOT EXISTS `page_lock` (
                `id` INTEGER PRIMARY KEY,
                `page_id` int(11) NOT NULL DEFAULT '0',
                `lock_uid` int(11) NOT NULL DEFAULT '0',
                `lock_username` CHAR(200) NOT NULL DEFAULT '',
                `lock_to` int(11) NOT NULL DEFAULT '0',
                `addtime` int(11) NOT NULL DEFAULT '0'
            )");

            // item_member表增加cat_id字段
            if (!self::isColumnExist('item_member', 'cat_id')) {
                DB::statement("ALTER TABLE item_member ADD cat_id INT(10) NOT NULL DEFAULT '0'");
            }

            // team_item_member表增加cat_id字段
            if (!self::isColumnExist('team_item_member', 'cat_id')) {
                DB::statement("ALTER TABLE team_item_member ADD cat_id INT(10) NOT NULL DEFAULT '0'");
            }

            // item_member表增加cat_ids字段
            if (!self::isColumnExist('item_member', 'cat_ids')) {
                DB::statement("ALTER TABLE item_member ADD cat_ids text NOT NULL DEFAULT ''");
            }

            // team_item_member表增加cat_ids字段
            if (!self::isColumnExist('team_item_member', 'cat_ids')) {
                DB::statement("ALTER TABLE team_item_member ADD cat_ids text NOT NULL DEFAULT ''");
            }

            // 创建item_variable表
            DB::statement("CREATE TABLE IF NOT EXISTS `item_variable` (
                `id` INTEGER PRIMARY KEY,
                `var_name` CHAR(2000) NOT NULL DEFAULT '',
                `var_value` CHAR(2000) NOT NULL DEFAULT '',
                `uid` int(11) NOT NULL DEFAULT '0',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `addtime` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建file_flow表
            DB::statement("CREATE TABLE IF NOT EXISTS `file_flow` (
                `id` INTEGER PRIMARY KEY,
                `uid` int(11) NOT NULL DEFAULT '0',
                `used` int(11) NOT NULL DEFAULT '0',
                `date_month` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // item_variable表增加env_id字段
            if (!self::isColumnExist('item_variable', 'env_id')) {
                DB::statement("ALTER TABLE item_variable ADD env_id INT(10) NOT NULL DEFAULT '0'");
            }

            // 创建runapi_env表
            DB::statement("CREATE TABLE IF NOT EXISTS `runapi_env` (
                `id` INTEGER PRIMARY KEY,
                `env_name` CHAR(2000) NOT NULL DEFAULT '',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `uid` int(11) NOT NULL DEFAULT '0',
                `addtime` CHAR(2000) NOT NULL DEFAULT '',
                `last_update_time` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // 创建runapi_env_selectd表
            DB::statement("CREATE TABLE IF NOT EXISTS `runapi_env_selectd` (
                `id` INTEGER PRIMARY KEY,
                `item_id` int(11) NOT NULL DEFAULT '0',
                `uid` int(11) NOT NULL DEFAULT '0',
                `env_id` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建runapi_global_param表
            DB::statement("CREATE TABLE IF NOT EXISTS `runapi_global_param` (
                `id` INTEGER PRIMARY KEY,
                `item_id` int(11) NOT NULL DEFAULT '0',
                `param_type` CHAR(2000) NOT NULL DEFAULT '',
                `content_json_str` CHAR(2000) NOT NULL DEFAULT '',
                `addtime` CHAR(2000) NOT NULL DEFAULT '',
                `last_update_time` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // 创建mock表
            DB::statement("CREATE TABLE IF NOT EXISTS `mock` (
                `id` INTEGER PRIMARY KEY,
                `unique_key` CHAR(2000) NOT NULL DEFAULT '',
                `uid` int(11) NOT NULL DEFAULT '0',
                `page_id` int(11) NOT NULL DEFAULT '0',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `view_times` int(11) NOT NULL DEFAULT '0',
                `template` CHAR(2000) NOT NULL DEFAULT '',
                `addtime` CHAR(2000) NOT NULL DEFAULT '',
                `last_update_time` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // 创建file_page表
            DB::statement("CREATE TABLE IF NOT EXISTS `file_page` (
                `id` INTEGER PRIMARY KEY,
                `file_id` int(11) NOT NULL DEFAULT '0',
                `page_id` int(11) NOT NULL DEFAULT '0',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `addtime` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // 如果file_page尚未有数据，则把upload_file表的数据转换过去
            $filePageCount = DB::table('file_page')->count();
            if ($filePageCount == 0) {
                $files = DB::table('upload_file')->get();
                foreach ($files as $file) {
                    DB::table('file_page')->insert([
                        'file_id' => $file->file_id,
                        'page_id' => $file->page_id,
                        'item_id' => $file->item_id,
                        'addtime' => $file->addtime,
                    ]);
                }
            }

            // mock表增加path字段
            if (!self::isColumnExist('mock', 'path')) {
                DB::statement("ALTER TABLE mock ADD path text NOT NULL DEFAULT ''");
            }

            // 创建runapi_flow表
            DB::statement("CREATE TABLE IF NOT EXISTS `runapi_flow` (
                `id` INTEGER PRIMARY KEY,
                `flow_name` CHAR(2000) NOT NULL DEFAULT '',
                `uid` int(11) NOT NULL DEFAULT '0',
                `username` CHAR(2000) NOT NULL DEFAULT '',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `env_id` int(11) NOT NULL DEFAULT '0',
                `times` int(11) NOT NULL DEFAULT '0',
                `time_interval` int(11) NOT NULL DEFAULT '0',
                `error_continue` int(11) NOT NULL DEFAULT '0',
                `save_change` int(11) NOT NULL DEFAULT '0',
                `addtime` CHAR(2000) NOT NULL DEFAULT '',
                `last_update_time` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // 创建runapi_flow_page表
            DB::statement("CREATE TABLE IF NOT EXISTS `runapi_flow_page` (
                `id` INTEGER PRIMARY KEY,
                `flow_id` int(11) NOT NULL DEFAULT '0',
                `page_id` int(11) NOT NULL DEFAULT '0',
                `s_number` int(11) NOT NULL DEFAULT '0',
                `addtime` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // runapi_flow_page表增加enabled字段
            if (!self::isColumnExist('runapi_flow_page', 'enabled')) {
                DB::statement("ALTER TABLE runapi_flow_page ADD enabled int(1) NOT NULL DEFAULT '1'");
            }

            // item_sort表增加item_group_id字段
            if (!self::isColumnExist('item_sort', 'item_group_id')) {
                DB::statement("ALTER TABLE item_sort ADD item_group_id int(10) NOT NULL DEFAULT '0'");
            }

            // 创建item_group表
            DB::statement("CREATE TABLE IF NOT EXISTS `item_group` (
                `id` INTEGER PRIMARY KEY,
                `uid` int(11) NOT NULL DEFAULT '0',
                `group_name` CHAR(2000) NOT NULL DEFAULT '',
                `item_ids` text NOT NULL DEFAULT '',
                `s_number` int(11) NOT NULL DEFAULT '0',
                `created_at` CHAR(2000) NOT NULL DEFAULT '',
                `updated_at` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // 创建item_change_log表
            DB::statement("CREATE TABLE IF NOT EXISTS `item_change_log` (
                `id` INTEGER PRIMARY KEY,
                `uid` int(11) NOT NULL DEFAULT '0',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `op_action_type` CHAR(2000) NOT NULL DEFAULT '',
                `op_object_type` CHAR(2000) NOT NULL DEFAULT '',
                `op_object_id` int(11) NOT NULL DEFAULT '0',
                `op_object_name` CHAR(2000) NOT NULL DEFAULT '',
                `remark` CHAR(2000) NOT NULL DEFAULT '',
                `optime` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // 创建message_content表
            DB::statement("CREATE TABLE IF NOT EXISTS `message_content` (
                `id` INTEGER PRIMARY KEY,
                `from_uid` int(11) NOT NULL DEFAULT '0',
                `from_name` CHAR(2000) NOT NULL DEFAULT '',
                `message_type` CHAR(2000) NOT NULL DEFAULT '',
                `message_content` CHAR(2000) NOT NULL DEFAULT '',
                `action_type` CHAR(2000) NOT NULL DEFAULT '',
                `object_type` CHAR(2000) NOT NULL DEFAULT '',
                `object_id` int(11) NOT NULL DEFAULT '0',
                `addtime` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // 创建message表
            DB::statement("CREATE TABLE IF NOT EXISTS `message` (
                `id` INTEGER PRIMARY KEY,
                `from_uid` int(11) NOT NULL DEFAULT '0',
                `to_uid` int(11) NOT NULL DEFAULT '0',
                `message_type` CHAR(2000) NOT NULL DEFAULT '',
                `message_content_id` int(11) NOT NULL DEFAULT '0',
                `status` int(11) NOT NULL DEFAULT '0',
                `addtime` CHAR(2000) NOT NULL DEFAULT '',
                `readtime` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // 创建subscription表
            DB::statement("CREATE TABLE IF NOT EXISTS `subscription` (
                `id` INTEGER PRIMARY KEY,
                `uid` int(11) NOT NULL DEFAULT '0',
                `object_id` int(11) NOT NULL DEFAULT '0',
                `object_type` CHAR(2000) NOT NULL DEFAULT '',
                `action_type` CHAR(2000) NOT NULL DEFAULT '',
                `sub_time` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // 创建template_item表
            DB::statement("CREATE TABLE IF NOT EXISTS `template_item` (
                `id` INTEGER PRIMARY KEY,
                `template_id` int(11) NOT NULL DEFAULT '0',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `uid` int(11) NOT NULL DEFAULT '0',
                `username` CHAR(2000) NOT NULL DEFAULT '',
                `created_at` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // team_member表增加team_member_group_id字段
            if (!self::isColumnExist('team_member', 'team_member_group_id')) {
                DB::statement("ALTER TABLE team_member ADD team_member_group_id int(10) NOT NULL DEFAULT '1'");
            }

            // user表增加salt字段
            if (!self::isColumnExist('user', 'salt')) {
                DB::statement("ALTER TABLE user ADD salt CHAR(2000) NOT NULL DEFAULT ''");
            }

            // 创建item_star表
            DB::statement("CREATE TABLE IF NOT EXISTS `item_star` (
                `id` INTEGER PRIMARY KEY,
                `uid` int(11) NOT NULL DEFAULT '0',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `s_number` int(11) NOT NULL DEFAULT '0',
                `created_at` CHAR(2000) NOT NULL DEFAULT '',
                `updated_at` CHAR(2000) NOT NULL DEFAULT ''
            )");

            // page表增加ext_info字段
            if (!self::isColumnExist('page', 'ext_info')) {
                DB::statement("ALTER TABLE page ADD ext_info CHAR(2000) NOT NULL DEFAULT ''");
            }

            // page_history表增加ext_info字段
            if (!self::isColumnExist('page_history', 'ext_info')) {
                DB::statement("ALTER TABLE page_history ADD ext_info CHAR(2000) NOT NULL DEFAULT ''");
            }

            // 创建export_log表
            DB::statement("CREATE TABLE IF NOT EXISTS `export_log` (
                `id` INTEGER PRIMARY KEY,
                `uid` int(11) NOT NULL DEFAULT '0',
                `export_type` CHAR(200) NOT NULL DEFAULT '',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `addtime` CHAR(200) NOT NULL DEFAULT ''
            )");

            // upload_file表增加last_visit_time字段
            if (!self::isColumnExist('upload_file', 'last_visit_time')) {
                DB::statement("ALTER TABLE upload_file ADD last_visit_time INT(11) NOT NULL DEFAULT '0'");
            }

            // 设置自增id从随机数开始（仅当表为空时）
            $pageCount = DB::table('page')->count();
            $catalogCount = DB::table('catalog')->count();
            $itemCount = DB::table('item')->count();
            
            if ($pageCount == 0) {
                $randomNumber1 = mt_rand(100000000, 299999999);
                DB::statement("INSERT OR IGNORE INTO sqlite_sequence (name, seq) VALUES ('page', {$randomNumber1})");
            }
            if ($catalogCount == 0) {
                $randomNumber2 = mt_rand(400000000, 499999999);
                DB::statement("INSERT OR IGNORE INTO sqlite_sequence (name, seq) VALUES ('catalog', {$randomNumber2})");
            }
            if ($itemCount == 0) {
                $randomNumber3 = mt_rand(600000000, 699999999);
                DB::statement("INSERT OR IGNORE INTO sqlite_sequence (name, seq) VALUES ('item', {$randomNumber3})");
            }

            // 创建user_setting表
            DB::statement("CREATE TABLE IF NOT EXISTS `user_setting` (
                `id` INTEGER PRIMARY KEY,
                `uid` int(10) NOT NULL DEFAULT '0',
                `key_name` CHAR(200) NOT NULL DEFAULT '',
                `key_value` text NOT NULL DEFAULT '',
                `addtime` text NOT NULL DEFAULT ''
            )");

            // single_page表增加expire_time字段
            if (!self::isColumnExist('single_page', 'expire_time')) {
                DB::statement("ALTER TABLE single_page ADD expire_time INT(11) NOT NULL DEFAULT '0'");
            }

            // 创建parameter_description_entry表
            DB::statement("CREATE TABLE IF NOT EXISTS `parameter_description_entry` (
                `id` TEXT PRIMARY KEY,
                `item_id` TEXT NOT NULL,
                `name` TEXT NOT NULL,
                `type` TEXT NOT NULL,
                `description` TEXT NOT NULL,
                `example` TEXT,
                `default_value` TEXT,
                `aliases` TEXT,
                `tags` TEXT,
                `path` TEXT,
                `source` TEXT NOT NULL DEFAULT 'manual' CHECK (source IN ('manual','auto-extracted','builtin')),
                `status` TEXT NOT NULL DEFAULT 'permanent' CHECK (status IN ('temp','permanent')),
                `usage_count` INTEGER NOT NULL DEFAULT 0,
                `quality_score` NUMERIC NOT NULL DEFAULT 0.00,
                `created_by` TEXT NOT NULL,
                `created_at` TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
                `updated_at` TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP)
            )");

            // 创建索引
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uk_item_name_type ON parameter_description_entry (item_id, name, type)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_item_id ON parameter_description_entry (item_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_name ON parameter_description_entry (name)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_type ON parameter_description_entry (type)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_status ON parameter_description_entry (status)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_created_at ON parameter_description_entry (created_at)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_updated_at ON parameter_description_entry (updated_at)");

            // 创建触发器
            DB::statement("DROP TRIGGER IF EXISTS trg_parameter_description_entry_updated_at");
            DB::statement("CREATE TRIGGER trg_parameter_description_entry_updated_at AFTER UPDATE ON parameter_description_entry
                BEGIN
                    UPDATE parameter_description_entry SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
                END");

            // 创建page_comment表
            DB::statement("CREATE TABLE IF NOT EXISTS `page_comment` (
                `comment_id` INTEGER PRIMARY KEY,
                `page_id` int(11) NOT NULL DEFAULT '0',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `parent_id` int(11) NOT NULL DEFAULT '0',
                `uid` int(11) NOT NULL DEFAULT '0',
                `username` CHAR(200) NOT NULL DEFAULT '',
                `content` text NOT NULL DEFAULT '',
                `is_deleted` int(1) NOT NULL DEFAULT '0',
                `addtime` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建page_feedback表
            DB::statement("CREATE TABLE IF NOT EXISTS `page_feedback` (
                `feedback_id` INTEGER PRIMARY KEY,
                `page_id` int(11) NOT NULL DEFAULT '0',
                `item_id` int(11) NOT NULL DEFAULT '0',
                `uid` int(11) NOT NULL DEFAULT '0',
                `client_id` CHAR(200) DEFAULT NULL,
                `feedback_type` int(1) NOT NULL DEFAULT '0',
                `addtime` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建唯一索引
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS unique_user_feedback ON page_feedback (page_id, uid)");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS unique_client_feedback ON page_feedback (page_id, client_id)");

            // item表增加allow_comment字段
            if (!self::isColumnExist('item', 'allow_comment')) {
                DB::statement("ALTER TABLE item ADD allow_comment INT(1) NOT NULL DEFAULT '0'");
            }

            // item表增加allow_feedback字段
            if (!self::isColumnExist('item', 'allow_feedback')) {
                DB::statement("ALTER TABLE item ADD allow_feedback INT(1) NOT NULL DEFAULT '0'");
            }

            // 清理可能存在的冲突数据
            DB::statement("UPDATE page_feedback SET client_id = NULL WHERE uid > 0 AND (client_id = '' OR client_id IS NULL)");

            // 创建item_ai_config表
            DB::statement("CREATE TABLE IF NOT EXISTS `item_ai_config` (
                `id` INTEGER PRIMARY KEY,
                `item_id` int(11) NOT NULL DEFAULT '0',
                `enabled` int(1) NOT NULL DEFAULT '0',
                `dialog_collapsed` int(1) NOT NULL DEFAULT '1',
                `welcome_message` text NOT NULL DEFAULT '',
                `addtime` int(11) NOT NULL DEFAULT '0',
                `updatetime` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建索引
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uk_item_id ON item_ai_config (item_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_enabled ON item_ai_config (enabled)");

            // 创建runapi_db_config表
            DB::statement("CREATE TABLE IF NOT EXISTS `runapi_db_config` (
                `id` INTEGER PRIMARY KEY,
                `item_id` int(11) NOT NULL DEFAULT '0',
                `env_id` int(11) NOT NULL DEFAULT '0',
                `config_name` CHAR(100) NOT NULL DEFAULT '默认',
                `db_type` CHAR(20) NOT NULL DEFAULT 'mysql',
                `host` CHAR(255) NOT NULL DEFAULT '',
                `port` int(11) NOT NULL DEFAULT '0',
                `username` CHAR(255) NOT NULL DEFAULT '',
                `password` CHAR(255) NOT NULL DEFAULT '',
                `database` CHAR(255) NOT NULL DEFAULT '',
                `options` text NOT NULL DEFAULT '',
                `is_default` int(1) NOT NULL DEFAULT '0',
                `addtime` CHAR(2000) NOT NULL DEFAULT '',
                `last_update_time` CHAR(2000) NOT NULL DEFAULT '',
                `uid` int(11) NOT NULL DEFAULT '0'
            )");

            // 创建唯一索引
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uniq_item_env_name ON runapi_db_config (item_id, env_id, config_name)");

            return true;
        } catch (\Throwable $e) {
            error_log('Database upgrade SQL execution failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 检查表中是否存在指定字段
     * 
     * @param string $table 表名
     * @param string $column 字段名
     * @return bool 是否存在
     */
    private static function isColumnExist(string $table, string $column): bool
    {
        try {
            // SQLite 查询表结构
            $result = DB::select("PRAGMA table_info({$table})");
            foreach ($result as $row) {
                if (isset($row->name) && $row->name === $column) {
                    return true;
                }
            }
            return false;
        } catch (\Throwable $e) {
            // 如果表不存在，返回 false
            return false;
        }
    }
}

