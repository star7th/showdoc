<?php
namespace Home\Controller;
use Think\Controller;
class UpdateController extends BaseController {
    
 	//升级数据库
    public function db(){
        clear_runtime();
    	if (strtolower(C("DB_TYPE")) == 'mysql' ) {
    		//$this->mysql();
            echo 'ShowDoc does not support mysql any more';
    	}
        elseif (strtolower(C("DB_TYPE")) == 'sqlite' ) {
            $this->sqlite();
        }
    	clear_runtime();
    }
    public function sqlite(){
        //catalog表增加parent_cat_id字段
        $columns = M("catalog")->getDbFields();
        if ($columns) {
            $has_it = 0 ;//是否存在该字段
            foreach ($columns as $key => $value) {
                if ($value == 'parent_cat_id') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0) {
                $sql = "ALTER TABLE ".C('DB_PREFIX')."catalog ADD parent_cat_id INT( 10 ) NOT NULL DEFAULT '0' ;";
                D("catalog")->execute($sql);
            }
        }

        //catalog表增加level字段
        $columns = M("catalog")->getDbFields();
        if ($columns) {
            $has_it = 0 ;//是否存在该字段
            foreach ($columns as $key => $value) {
                if ($value == 'level') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0) {
                $sql = "ALTER TABLE ".C('DB_PREFIX')."catalog ADD level INT( 10 ) NOT NULL DEFAULT '2'  ;";
                D("catalog")->execute($sql);
            }
        }

        //item表增加item_domain字段
        $columns = M("item")->getDbFields();
        if ($columns) {
            $has_it = 0 ;//是否存在该字段
            foreach ($columns as $key => $value) {
                if ($value == 'item_domain') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0) {
                $sql = "ALTER TABLE ".C('DB_PREFIX')."item ADD item_domain text NOT NULL DEFAULT '';";
                D("item")->execute($sql);
            }
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
        $columns = D("Page")->getDbFields();
        if ($columns) {
            $has_it = 0 ;//是否存在该字段
            foreach ($columns as $key => $value) {
                if ($value == 'page_comments') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0) {
                $sql = "ALTER TABLE ".C('DB_PREFIX')."page ADD page_comments text NOT NULL DEFAULT ''  ;";
                D("Page")->execute($sql);
            }
        }

        //page_history 表增加page_comments字段
        $columns = D("PageHistory")->getDbFields();
        if ($columns) {
            $has_it = 0 ;//是否存在该字段
            foreach ($columns as $key => $value) {
                if ($value == 'page_comments') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0) {
                $sql = "ALTER TABLE ".C('DB_PREFIX')."page_history ADD page_comments text NOT NULL DEFAULT '';";
                D("PageHistory")->execute($sql);
            }
        }

        echo 'OK!';
    }

    //转移mysql的数据到sqlite
    public function toSqlite(){
        clear_runtime();
        if (strtolower(C("DB_TYPE")) == 'mysql' ) {
            $this->_moveTable("catalog");
            $this->_moveTable("item");
            $this->_moveTable("item_member");
            $this->_moveTable("page");
            $this->_moveTable("page_history");
            $this->_moveTable("template");
            $this->_moveTable("user");
            $this->_moveTable("user_token");
            $db_config = array(
                'DB_TYPE'   => 'Sqlite', 
                'DB_NAME'   => 'Sqlite/showdoc.db.php', 
                );
            $array = M("user")->select();
            if ($array) {
                echo "ok";
            }else{
                echo 'fail';
            }
            
        }
        else{
            echo "mysql not found";
        }
        clear_runtime();
    }

    private function _moveTable($table){
        $db_config = array(
            'DB_TYPE'   => 'Sqlite', 
            'DB_NAME'   => 'Sqlite/showdoc.db.php', 
            );
        $array = M($table)->select();
        if ($array) {
            foreach ($array as $key => $value) {
               M($table)->db(2,$db_config)->add($value);
            }
        }
    }





}