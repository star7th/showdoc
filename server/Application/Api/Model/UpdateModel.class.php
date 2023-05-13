<?php

namespace Api\Model;

use Api\Model\BaseModel;

/**
 * 
 * @author star7th      
 */
class UpdateModel
{

    protected $autoCheckFields = false;  //一定要关闭字段缓存，不然会报找不到表的错误

    //检测数据库并更新
    public function checkDb()
    {
        $version_num = 17;
        $db_version_num = D("Options")->get("db_version_num");
        if (!$db_version_num || $db_version_num < $version_num) {
            $r = $this->updateSqlite();
            if ($r) {
                D("Options")->set("db_version_num", $version_num);
            }
            //echo '执行数据库升级';
        }
    }

    public function updateSqlite()
    {
        //catalog表增加parent_cat_id字段
        if (!$this->_is_column_exist("catalog", "parent_cat_id")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "catalog ADD parent_cat_id INT( 10 ) NOT NULL DEFAULT '0' ;";
            D("catalog")->execute($sql);
        }

        //catalog表增加level字段
        if (!$this->_is_column_exist("catalog", "level")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "catalog ADD level INT( 10 ) NOT NULL DEFAULT '2'  ;";
            D("catalog")->execute($sql);
        }


        //item表增加item_domain字段
        if (!$this->_is_column_exist("item", "item_domain")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "item ADD item_domain text NOT NULL DEFAULT '';";
            D("catalog")->execute($sql);
        }

        //创建user_token表
        $sql = "CREATE TABLE IF NOT EXISTS `user_token` (
        `id`  INTEGER PRIMARY KEY ,
        `uid` int(10) NOT NULL DEFAULT '0',
        `token` CHAR(200) NOT NULL DEFAULT '',
        `token_expire` int(11) NOT NULL DEFAULT '0' ,
        `ip` CHAR(200) NOT NULL DEFAULT '',
        `addtime` int(11) NOT NULL DEFAULT '0'
        )";
        D("UserToken")->execute($sql);

        //创建template表
        $sql = "CREATE TABLE IF NOT EXISTS `template` (
        `id`  INTEGER PRIMARY KEY ,
        `uid` int(10) NOT NULL DEFAULT '0',
        `username` CHAR(200) NOT NULL DEFAULT '',
        `template_title` CHAR(200) NOT NULL DEFAULT '' ,
        `template_content` text NOT NULL DEFAULT '',
        `addtime` int(11) NOT NULL DEFAULT '0'
        )";
        D("UserToken")->execute($sql);

        //page表增加page_comments字段
        if (!$this->_is_column_exist("page", "page_comments")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "page ADD page_comments text NOT NULL DEFAULT ''  ;";
            D("catalog")->execute($sql);
        }


        //page_history 表增加page_comments字段
        if (!$this->_is_column_exist("PageHistory", "page_comments")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "page_history ADD page_comments text NOT NULL DEFAULT '';";
            D("catalog")->execute($sql);
        }


        //item_member表增加member_group_id字段
        if (!$this->_is_column_exist("ItemMember", "member_group_id")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "item_member ADD member_group_id INT( 1 ) NOT NULL DEFAULT '1'  ;";
            D("ItemMember")->execute($sql);
        }


        //item表增加item_type字段
        if (!$this->_is_column_exist("Item", "item_type")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "item ADD item_type INT( 1 ) NOT NULL DEFAULT '1'  ;";
            D("ItemMember")->execute($sql);
        }

        //创建options表
        $sql = "CREATE TABLE IF NOT EXISTS `options` (
        `option_id`  INTEGER PRIMARY KEY ,
        `option_name` CHAR(200) NOT NULL UNIQUE ,
        `option_value` CHAR(200) NOT NULL 
        )";
        D("UserToken")->execute($sql);

        //创建item_token表
        $sql = "CREATE TABLE IF NOT EXISTS `item_token` (
        `id`  INTEGER PRIMARY KEY ,
        `item_id` int(11) NOT NULL DEFAULT '0' ,
        `api_key` CHAR(200) NOT NULL UNIQUE ,
        `api_token` CHAR(200) NOT NULL ,
        `addtime` int(11) NOT NULL DEFAULT '0' ,
        `last_check_time` int(11) NOT NULL DEFAULT '0' 
        )";
        D("UserToken")->execute($sql);

        //创建item_top表
        $sql = "CREATE TABLE IF NOT EXISTS `item_top` (
        `id`  INTEGER PRIMARY KEY ,
        `item_id` int(11) NOT NULL DEFAULT '0' ,
        `uid` int(11) NOT NULL DEFAULT '0' ,
        `addtime` int(11) NOT NULL DEFAULT '0' 
        )";
        D("UserToken")->execute($sql);

        //item表增加is_archived字段
        if (!$this->_is_column_exist("Item", "is_archived")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "item ADD is_archived INT( 1 ) NOT NULL DEFAULT '0'  ;";
            D("ItemMember")->execute($sql);
        }


        //管理员账户和权限
        if (D("User")->where("username = 'showdoc' ")->find()) {
            D("User")->where("username = 'showdoc' ")->save(array("groupid" => 1));
        } else {
            D("User")->add(array('username' => "showdoc", "groupid" => 1, 'password' => "a89da13684490eb9ec9e613f91d24d00", 'reg_time' => time()));
        }

        //item表增加is_del字段
        if (!$this->_is_column_exist("Item", "is_del")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "item ADD is_del INT( 1 ) NOT NULL DEFAULT '0'  ;";
            D("ItemMember")->execute($sql);
        }

        //page表增加is_del字段
        if (!$this->_is_column_exist("Page", "is_del")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "page ADD is_del INT( 1 ) NOT NULL DEFAULT '0'  ;";
            D("ItemMember")->execute($sql);
        }

        //page表增加page_addtime字段
        if (!$this->_is_column_exist("Page", "page_addtime")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "page ADD page_addtime INT( 11 ) NOT NULL DEFAULT '0'  ;";
            D("Page")->execute($sql);
        }

        //创建team表
        $sql = "CREATE TABLE IF NOT EXISTS `team` (
        `id`  INTEGER PRIMARY KEY ,
        `team_name` CHAR(200) NOT NULL DEFAULT '',
        `uid` int(11) NOT NULL DEFAULT '0' ,
        `username` CHAR(200) NOT NULL DEFAULT '',
        `addtime` int(11) NOT NULL DEFAULT '0' ,
        `last_update_time` int(11) NOT NULL DEFAULT '0' 
        )";
        D("User")->execute($sql);

        //创建team_item表
        $sql = "CREATE TABLE IF NOT EXISTS `team_item` (
        `id`  INTEGER PRIMARY KEY ,
        `team_id` int(11) NOT NULL DEFAULT '0' ,
        `item_id` int(11) NOT NULL DEFAULT '0' ,
        `addtime` int(11) NOT NULL DEFAULT '0' ,
        `last_update_time` int(11) NOT NULL DEFAULT '0' 
        )";
        D("User")->execute($sql);

        //创建team_item_member表
        $sql = "CREATE TABLE IF NOT EXISTS `team_item_member` (
        `id`  INTEGER PRIMARY KEY ,
        `team_id` int(11) NOT NULL DEFAULT '0' ,
        `item_id` int(11) NOT NULL DEFAULT '0' ,
        `member_group_id` int(11) NOT NULL DEFAULT '0' ,
        `member_uid` int(11) NOT NULL DEFAULT '0' ,
        `member_username` CHAR(200) NOT NULL DEFAULT '',
        `addtime` int(11) NOT NULL DEFAULT '0' ,
        `last_update_time` int(11) NOT NULL DEFAULT '0' 
        )";
        D("User")->execute($sql);

        //创建team_member表
        $sql = "CREATE TABLE IF NOT EXISTS `team_member` (
        `id`  INTEGER PRIMARY KEY ,
        `team_id` int(11) NOT NULL DEFAULT '0' ,
        `member_uid` int(11) NOT NULL DEFAULT '0' ,
        `member_username` CHAR(200) NOT NULL DEFAULT '',
        `addtime` int(11) NOT NULL DEFAULT '0' ,
        `last_update_time` int(11) NOT NULL DEFAULT '0' 
        )";
        D("User")->execute($sql);

        //创建upload_file表
        $sql = "CREATE TABLE IF NOT EXISTS `upload_file` (
        `file_id`  INTEGER PRIMARY KEY ,
        `sign` CHAR(200) NOT NULL DEFAULT '',
        `display_name` CHAR(200) NOT NULL DEFAULT '',
        `file_type` CHAR(200) NOT NULL DEFAULT '',
        `file_size` CHAR(200) NOT NULL DEFAULT '',
        `uid` int(11) NOT NULL DEFAULT '0' ,
        `page_id` int(11) NOT NULL DEFAULT '0' ,
        `item_id` int(11) NOT NULL DEFAULT '0' ,
        `visit_times` int(11) NOT NULL DEFAULT '0' ,
        `addtime` int(11) NOT NULL DEFAULT '0' ,
        `real_url` CHAR(200) NOT NULL DEFAULT '',
        `last_update_time` int(11) NOT NULL DEFAULT '0' 
        )";
        D("User")->execute($sql);

        //创建item_sort表
        $sql = "CREATE TABLE IF NOT EXISTS `item_sort` (
        `id`  INTEGER PRIMARY KEY ,
        `uid` int(10) NOT NULL DEFAULT '0',
        `item_sort_data` text NOT NULL DEFAULT '',
        `addtime` int(11) NOT NULL DEFAULT '0'
        )";
        D("UserToken")->execute($sql);

        //创建single_page表
        $sql = "CREATE TABLE IF NOT EXISTS `single_page` (
        `id`  INTEGER PRIMARY KEY ,
        `unique_key` CHAR(200) NOT NULL DEFAULT '',
        `page_id` int(11) NOT NULL DEFAULT '0'
        )";
        D("User")->execute($sql);

        //创建captcha表
        $sql = "CREATE TABLE IF NOT EXISTS `captcha` (
        `captcha_id`  INTEGER PRIMARY KEY ,
        `mobile` CHAR(200) NOT NULL DEFAULT '',
        `captcha` CHAR(200) NOT NULL DEFAULT '',
        `expire_time` int(11) NOT NULL DEFAULT '0'
        )";
        D("User")->execute($sql);

        //创建recycle表
        $sql = "CREATE TABLE IF NOT EXISTS `recycle` (
        `id`  INTEGER PRIMARY KEY ,
        `item_id` int(11) NOT NULL DEFAULT '0',
        `page_id` int(11) NOT NULL DEFAULT '0',
        `page_title` CHAR(200) NOT NULL DEFAULT '',
        `del_by_uid` int(11) NOT NULL DEFAULT '0',
        `del_by_username` CHAR(200) NOT NULL DEFAULT '',
        `del_time` int(11) NOT NULL DEFAULT '0'
        )";
        D("User")->execute($sql);

        //创建page_lock表
        $sql = "CREATE TABLE IF NOT EXISTS `page_lock` (
            `id`  INTEGER PRIMARY KEY ,
            `page_id` int(11) NOT NULL DEFAULT '0',
            `lock_uid` int(11) NOT NULL DEFAULT '0',
            `lock_username` CHAR(200) NOT NULL DEFAULT '',
            `lock_to` int(11) NOT NULL DEFAULT '0',
            `addtime` int(11) NOT NULL DEFAULT '0'
            )";
        D("User")->execute($sql);

        //item_member表增加cat_id字段
        if (!$this->_is_column_exist("item_member", "cat_id")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "item_member ADD cat_id INT( 10 ) NOT NULL DEFAULT '0'  ;";
            D("User")->execute($sql);
        }

        //team_item_member表增加cat_id字段
        if (!$this->_is_column_exist("team_item_member", "cat_id")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "team_item_member ADD cat_id INT( 10 ) NOT NULL DEFAULT '0'  ;";
            D("User")->execute($sql);
        }

        //创建item_variable表
        $sql = "CREATE TABLE IF NOT EXISTS `item_variable` (
            `id`  INTEGER PRIMARY KEY ,
            `var_name` CHAR(2000) NOT NULL DEFAULT '',
            `var_value` CHAR(2000) NOT NULL DEFAULT '',
            `uid` int(11) NOT NULL DEFAULT '0',
            `item_id` int(11) NOT NULL DEFAULT '0',
            `addtime` int(11) NOT NULL DEFAULT '0'
            )";
        D("User")->execute($sql);

        //创建file_flow表
        $sql = "CREATE TABLE IF NOT EXISTS `file_flow` (
            `id`  INTEGER PRIMARY KEY ,
            `uid` int(11) NOT NULL DEFAULT '0',
            `used` int(11) NOT NULL DEFAULT '0',
            `date_month` CHAR(2000) NOT NULL DEFAULT ''
            )";
        D("User")->execute($sql);

        //item_variable表增加env_id字段
        if (!$this->_is_column_exist("item_variable", "env_id")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "item_variable ADD env_id INT( 10 ) NOT NULL DEFAULT '0'  ;";
            D("User")->execute($sql);
        }
        //创建runapi_env表
        $sql = "CREATE TABLE IF NOT EXISTS `runapi_env` (
            `id`  INTEGER PRIMARY KEY ,
            `env_name` CHAR(2000) NOT NULL DEFAULT '',
            `item_id` int(11) NOT NULL DEFAULT '0',
            `uid` int(11) NOT NULL DEFAULT '0',
            `addtime` CHAR(2000) NOT NULL DEFAULT '',
            `last_update_time` CHAR(2000) NOT NULL DEFAULT ''
            )";
        D("User")->execute($sql);
        //创建runapi_env_selectd表
        $sql = "CREATE TABLE IF NOT EXISTS `runapi_env_selectd` (
            `id`  INTEGER PRIMARY KEY ,
            `item_id` int(11) NOT NULL DEFAULT '0',
            `uid` int(11) NOT NULL DEFAULT '0',
            `env_id` int(11) NOT NULL DEFAULT '0'
            )";
        D("User")->execute($sql);
        //创建runapi_global_param表
        $sql = "CREATE TABLE IF NOT EXISTS `runapi_global_param` (
            `id`  INTEGER PRIMARY KEY ,
            `item_id` int(11) NOT NULL DEFAULT '0',
            `param_type` CHAR(2000) NOT NULL DEFAULT '',
            `content_json_str` CHAR(2000) NOT NULL DEFAULT '',
            `addtime` CHAR(2000) NOT NULL DEFAULT '',
            `last_update_time` CHAR(2000) NOT NULL DEFAULT ''
            )";
        D("User")->execute($sql);
        //创建mock表
        $sql = "CREATE TABLE IF NOT EXISTS `mock` (
            `id`  INTEGER PRIMARY KEY ,
            `unique_key` CHAR(2000) NOT NULL DEFAULT '',
            `uid` int(11) NOT NULL DEFAULT '0',
            `page_id` int(11) NOT NULL DEFAULT '0',
            `item_id` int(11) NOT NULL DEFAULT '0',
            `view_times` int(11) NOT NULL DEFAULT '0',
            `template` CHAR(2000) NOT NULL DEFAULT '',
            `addtime` CHAR(2000) NOT NULL DEFAULT '',
            `last_update_time` CHAR(2000) NOT NULL DEFAULT ''
            )";
        D("User")->execute($sql);

        //创建file_page表
        $sql = "CREATE TABLE IF NOT EXISTS `file_page` (
            `id`  INTEGER PRIMARY KEY ,
            `file_id` int(11) NOT NULL DEFAULT '0',
            `page_id` int(11) NOT NULL DEFAULT '0',
            `item_id` int(11) NOT NULL DEFAULT '0',
            `addtime` CHAR(2000) NOT NULL DEFAULT ''
            )";
        D("User")->execute($sql);

        // 如果file_page尚未有数据，则把upload_file表的数据转换过去
        if (!D("FilePage")->find()) {
            $files = D("UploadFile")->select();
            if ($files) {
                foreach ($files as $key => $value) {
                    D("FilePage")->add(array(
                        "file_id" => $value['file_id'],
                        "page_id" => $value['page_id'],
                        "item_id" => $value['item_id'],
                        "addtime" => $value['addtime'],
                    ));
                }
            }
        }

        //给mock表增加path字段
        if (!$this->_is_column_exist("mock", "path")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "mock ADD path text NOT NULL DEFAULT '';";
            D("mock")->execute($sql);
        }

        //创建runapi_flow表
        $sql = "CREATE TABLE IF NOT EXISTS `runapi_flow` (
            `id`  INTEGER PRIMARY KEY ,
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
            )";
        D("User")->execute($sql);

        //创建runapi_flow_page表
        $sql = "CREATE TABLE IF NOT EXISTS `runapi_flow_page` (
            `id`  INTEGER PRIMARY KEY ,
            `flow_id` int(11) NOT NULL DEFAULT '0',
            `page_id` int(11) NOT NULL DEFAULT '0',
            `s_number` int(11) NOT NULL DEFAULT '0',
            `addtime` CHAR(2000) NOT NULL DEFAULT ''
            )";
        D("User")->execute($sql);

        //给runapi_flow_page表增加enabled字段
        if (!$this->_is_column_exist("runapi_flow_page", "enabled")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "runapi_flow_page ADD enabled int(1) NOT NULL DEFAULT '1' ;";
            D("mock")->execute($sql);
        }

        //给item_sort表增加item_group_id字段
        if (!$this->_is_column_exist("item_sort", "item_group_id")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "item_sort ADD item_group_id int(10) NOT NULL DEFAULT '0' ;";
            D("mock")->execute($sql);
        }

        //创建item_group表
        $sql = "CREATE TABLE IF NOT EXISTS `item_group` (
            `id`  INTEGER PRIMARY KEY ,
            `uid` int(11) NOT NULL DEFAULT '0',
            `group_name` CHAR(2000) NOT NULL DEFAULT '',
            `item_ids` text NOT NULL DEFAULT '',
            `s_number` int(11) NOT NULL DEFAULT '0',
            `created_at` CHAR(2000) NOT NULL DEFAULT '',
            `updated_at` CHAR(2000) NOT NULL DEFAULT ''
            )";
        D("User")->execute($sql);

        //创建item_change_log表
        $sql = "CREATE TABLE IF NOT EXISTS `item_change_log` (
            `id`  INTEGER PRIMARY KEY ,
            `uid` int(11) NOT NULL DEFAULT '0',
            `item_id` int(11) NOT NULL DEFAULT '0',
            `op_action_type` CHAR(2000) NOT NULL DEFAULT '',
            `op_object_type` CHAR(2000) NOT NULL DEFAULT '',
            `op_object_id` int(11) NOT NULL DEFAULT '0',
            `op_object_name` CHAR(2000) NOT NULL DEFAULT '',
            `remark` CHAR(2000) NOT NULL DEFAULT '',
            `optime` CHAR(2000) NOT NULL DEFAULT ''
            )";
        D("User")->execute($sql);

        //创建message_content表
        $sql = "CREATE TABLE IF NOT EXISTS `message_content` (
            `id`  INTEGER PRIMARY KEY ,
            `from_uid` int(11) NOT NULL DEFAULT '0',
            `from_name` CHAR(2000) NOT NULL DEFAULT '',
            `message_type` CHAR(2000) NOT NULL DEFAULT '',
            `message_content` CHAR(2000) NOT NULL DEFAULT '',
            `action_type` CHAR(2000) NOT NULL DEFAULT '',
            `object_type` CHAR(2000) NOT NULL DEFAULT '',
            `object_id` int(11) NOT NULL DEFAULT '0',
            `addtime` CHAR(2000) NOT NULL DEFAULT ''

            )";
        D("User")->execute($sql);

        //创建message表
        $sql = "CREATE TABLE IF NOT EXISTS `message` (
            `id`  INTEGER PRIMARY KEY ,
            `from_uid` int(11) NOT NULL DEFAULT '0',
            `to_uid` int(11) NOT NULL DEFAULT '0',
            `message_type` CHAR(2000) NOT NULL DEFAULT '',
            `message_content_id` int(11) NOT NULL DEFAULT '0',
            `status` int(11) NOT NULL DEFAULT '0',
            `addtime` CHAR(2000) NOT NULL DEFAULT '',
            `readtime` CHAR(2000) NOT NULL DEFAULT ''
            )";
        D("User")->execute($sql);

        //创建subscription表
        $sql = "CREATE TABLE IF NOT EXISTS `subscription` (
            `id`  INTEGER PRIMARY KEY ,
            `uid` int(11) NOT NULL DEFAULT '0',
            `object_id` int(11) NOT NULL DEFAULT '0',
            `object_type` CHAR(2000) NOT NULL DEFAULT '',
            `action_type` CHAR(2000) NOT NULL DEFAULT '',
            `sub_time` CHAR(2000) NOT NULL DEFAULT ''
            )";
        D("User")->execute($sql);

        //创建template_item表
        $sql = "CREATE TABLE IF NOT EXISTS `template_item` (
            `id`  INTEGER PRIMARY KEY ,
            `template_id` int(11) NOT NULL DEFAULT '0',
            `item_id` int(11) NOT NULL DEFAULT '0',
            `uid` int(11) NOT NULL DEFAULT '0',
            `username` CHAR(2000) NOT NULL DEFAULT '',
            `created_at` CHAR(2000) NOT NULL DEFAULT ''
            )";
        D("User")->execute($sql);

        //给team_member表增加team_member_group_id字段
        if (!$this->_is_column_exist("team_member", "team_member_group_id")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "team_member ADD team_member_group_id int(10) NOT NULL DEFAULT '1' ;";
            D("mock")->execute($sql);
        }

        //给user表增加salt字段
        if (!$this->_is_column_exist("user", "salt")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "user ADD salt CHAR(2000) NOT NULL DEFAULT '' ;";
            D("mock")->execute($sql);
        }

        //创建item_star表
        $sql = "CREATE TABLE IF NOT EXISTS `item_star` (
            `id`  INTEGER PRIMARY KEY ,
            `uid` int(11) NOT NULL DEFAULT '0',
            `item_id` int(11) NOT NULL DEFAULT '0',
            `s_number` int(11) NOT NULL DEFAULT '0',
            `created_at` CHAR(2000) NOT NULL DEFAULT '',
            `updated_at` CHAR(2000) NOT NULL DEFAULT ''
            )";
        D("User")->execute($sql);

        //给page表增加ext_info字段
        if (!$this->_is_column_exist("page", "ext_info")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "page ADD ext_info CHAR(2000) NOT NULL DEFAULT '' ;";
            D("page")->execute($sql);
        }

        //给page_history表增加ext_info字段
        if (!$this->_is_column_exist("page_history", "ext_info")) {
            $sql = "ALTER TABLE " . C('DB_PREFIX') . "page_history ADD ext_info CHAR(2000) NOT NULL DEFAULT '' ;";
            D("page")->execute($sql);
        }

        //留个注释提醒自己，如果更新数据库结构，务必更改checkDb()里面的$version_num
        //留个注释提醒自己，如果更新数据库结构，务必更改checkDb()里面的$version_num
        //留个注释提醒自己，如果更新数据库结构，务必更改checkDb()里面的$version_num

        return true;
    }

    private function _is_column_exist($table, $column)
    {
        $has_it = false; //是否存在该字段
        $columns = M($table)->getDbFields();
        if ($columns) {
            foreach ($columns as $key => $value) {
                if ($value == $column) {
                    $has_it = true;
                }
            }
        }
        return $has_it;
    }
}
