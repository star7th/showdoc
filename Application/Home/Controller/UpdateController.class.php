<?php
namespace Home\Controller;
use Think\Controller;
class UpdateController extends BaseController {
    
 	//升级数据库
    public function db(){
        $this->_clear_runtime();
    	if (strtolower(C("DB_TYPE")) == 'mysql' ) {
    		//$this->mysql();
            echo 'ShowDoc does not support mysql any more . https://www.showdoc.cc/help?page_id=31990 ';
    	}
        elseif (strtolower(C("DB_TYPE")) == 'sqlite' ) {
            $this->sqlite();
        }
    	$this->_clear_runtime();
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

        //item_member表增加member_group_id字段
        $columns = M("ItemMember")->getDbFields();
        if ($columns) {
            $has_it = 0 ;//是否存在该字段
            foreach ($columns as $key => $value) {
                if ($value == 'member_group_id') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0) {
                $sql = "ALTER TABLE ".C('DB_PREFIX')."item_member ADD member_group_id INT( 1 ) NOT NULL DEFAULT '1'  ;";
                D("ItemMember")->execute($sql);
            }
        }

        //item表增加item_type字段
        $columns = M("Item")->getDbFields();
        if ($columns) {
            $has_it = 0 ;//是否存在该字段
            foreach ($columns as $key => $value) {
                if ($value == 'item_type') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0) {
                $sql = "ALTER TABLE ".C('DB_PREFIX')."item ADD item_type INT( 1 ) NOT NULL DEFAULT '1'  ;";
                D("Item")->execute($sql);
            }
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
        $columns = M("Item")->getDbFields();
        if ($columns) {
            $has_it = 0 ;//是否存在该字段
            foreach ($columns as $key => $value) {
                if ($value == 'is_archived') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0) {
                $sql = "ALTER TABLE ".C('DB_PREFIX')."item ADD is_archived INT( 1 ) NOT NULL DEFAULT '0'  ;";
                D("Item")->execute($sql);
            }
        }


        echo 'OK!';
    }

    private function _clear_runtime($path = RUNTIME_PATH){  
        //给定的目录不是一个文件夹  
        if(!is_dir($path)){  
            return null;  
        }  
      
        $fh = opendir($path);  
        while(($row = readdir($fh)) !== false){  
            //过滤掉虚拟目录  
            if($row == '.' || $row == '..'|| $row == 'index.html'){  
                continue;  
            }  
      
            if(!is_dir($path.'/'.$row)){
                unlink($path.'/'.$row);  
            }  
            $this->_clear_runtime($path.'/'.$row);  
              
        }  
        //关闭目录句柄，否则出Permission denied  
        closedir($fh);    
        return true;  
    }

    //转移mysql的数据到sqlite
    public function toSqlite(){
        $this->_clear_runtime();
        if (strtolower(C("DB_TYPE")) == 'mysql' ) {
            $this->mysql();
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
            $array = M("item")->db(2,$db_config)->select();
            if ($array) {
                echo "ok";
            }else{
                echo 'fail';
            }
            
        }
        else{
            echo "mysql not found";
        }
        $this->_clear_runtime();
    }

    //升级mysql数据库  
    public function mysql(){

        //user表的username字段增大了长度，防止长邮箱的用户名注册不了
        $sql = "alter table ".C('DB_PREFIX')."user modify column username varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '' ";
        M("Catalog")->execute($sql);

        //item表增加last_update_time字段
        $columns = M("item")->getDbFields();
        if ($columns) {
            $has_it = 0 ;//是否存在该字段
            foreach ($columns as $key => $value) {
                if ($value == 'last_update_time') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0) {
                $sql = "ALTER TABLE ".C('DB_PREFIX')."item ADD last_update_time INT( 11 ) NOT NULL DEFAULT '0' COMMENT '最后更新时间';";
                D("Item")->execute($sql);
            }
        }
        

        //更改catalog表的order字段名为s_number
        $columns = M("Catalog")->getDbFields();
        if ($columns) {
            foreach ($columns as $key => $value) {
                if ($value == 'order') {
                    $sql = "ALTER TABLE  `".C('DB_PREFIX')."catalog` CHANGE  `order`  `s_number` INT( 10 ) NOT NULL DEFAULT  '99' COMMENT  '顺序号。数字越小越靠前。若此值全部相等时则按id排序';";
                    M("Catalog")->execute($sql);
                }
            }
        }

        //更改page表的order字段名为s_number
        $columns = M("Page")->getDbFields();
        if ($columns) {
            foreach ($columns as $key => $value) {
                if ($value == 'order') {
                    $sql = "ALTER TABLE  `".C('DB_PREFIX')."page` CHANGE  `order`  `s_number` INT( 10 ) NOT NULL DEFAULT  '99' COMMENT  '顺序号。数字越小越靠前。若此值全部相等时则按id排序';";
                    M("Page")->execute($sql);
                }
            }
        }

        //更改page_history表的order字段名为s_number
        $columns = M("PageHistory")->getDbFields();
        if ($columns) {
            foreach ($columns as $key => $value) {
                if ($value == 'order') {
                    $sql = "ALTER TABLE  `".C('DB_PREFIX')."page_history` CHANGE  `order`  `s_number` INT( 10 ) NOT NULL DEFAULT  '99' COMMENT  '顺序号。数字越小越靠前。若此值全部相等时则按id排序';";
                    M("PageHistory")->execute($sql);
                }
            }
        }

        //为catalog表增加addtime索引
        $indexs = M("Catalog")->query(" show index from ".C('DB_PREFIX')."catalog");
        if ($indexs) {
            $has_it = 0 ;//是否存在该索引
            foreach ($indexs as $key => $value) {
                if ($value['column_name'] =='addtime') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0 ) {
                M("Catalog")->execute("ALTER TABLE ".C('DB_PREFIX')."catalog ADD INDEX ( `addtime` ) ;");
            }
        }

        //为item表增加addtime索引
        $indexs = M("Item")->query(" show index from ".C('DB_PREFIX')."item");
        if ($indexs) {
            $has_it = 0 ;//是否存在该索引
            foreach ($indexs as $key => $value) {
                if ($value['column_name'] =='addtime') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0 ) {
                M("Item")->execute("ALTER TABLE ".C('DB_PREFIX')."item ADD INDEX ( `addtime` ) ;");
            }
        }

        //为page表增加addtime索引
        $indexs = M("Page")->query(" show index from ".C('DB_PREFIX')."page");
        if ($indexs) {
            $has_it = 0 ;//是否存在该索引
            foreach ($indexs as $key => $value) {
                if ($value['column_name'] =='addtime') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0 ) {
                M("page")->execute("ALTER TABLE ".C('DB_PREFIX')."page ADD INDEX ( `addtime` ) ;");
            }
        }

        //为page_history表增加addtime索引
        $indexs = M("PageHistory")->query(" show index from ".C('DB_PREFIX')."page_history");
        if ($indexs) {
            $has_it = 0 ;//是否存在该索引
            foreach ($indexs as $key => $value) {
                if ($value['column_name'] =='addtime') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0 ) {
                M("PageHistory")->execute("ALTER TABLE ".C('DB_PREFIX')."page_history ADD INDEX ( `addtime` ) ;");
            }
        }

        //为page_history表增加page_id索引
        $indexs = M("PageHistory")->query(" show index from ".C('DB_PREFIX')."page_history");
        if ($indexs) {
            $has_it = 0 ;//是否存在该索引
            foreach ($indexs as $key => $value) {
                if ($value['column_name'] =='page_id') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0 ) {
                M("PageHistory")->execute("ALTER TABLE ".C('DB_PREFIX')."page_history ADD INDEX ( `page_id` ) ;");
            }
        }


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
                $sql = "ALTER TABLE ".C('DB_PREFIX')."catalog ADD parent_cat_id INT( 10 ) NOT NULL DEFAULT '0' COMMENT '上一级目录的id';";
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
                $sql = "ALTER TABLE ".C('DB_PREFIX')."catalog ADD level INT( 10 ) NOT NULL DEFAULT '2' COMMENT '2为二级目录，3为三级目录';";
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
                $sql = "ALTER TABLE ".C('DB_PREFIX')."item ADD item_domain varchar( 50 ) NOT NULL DEFAULT '' COMMENT 'item的个性域名';";
                D("item")->execute($sql);
            }
        }

        $sql = "CREATE TABLE IF NOT EXISTS `".C('DB_PREFIX')."user_token` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `uid` int(10) NOT NULL DEFAULT '0',
        `token` varchar(200) NOT NULL DEFAULT '',
        `token_expire` int(11) NOT NULL DEFAULT '0' ,
        `ip` varchar(200) NOT NULL DEFAULT '',
        `addtime` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `token` (`token`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='' AUTO_INCREMENT=1 ";
        D("User")->execute($sql);

        //创建template表
        $sql = "CREATE TABLE IF NOT EXISTS `".C('DB_PREFIX')."template` (
        `id` int(10) NOT NULL AUTO_INCREMENT,
        `uid` int(10) NOT NULL DEFAULT '0',
        `username` varchar(200) NOT NULL DEFAULT '',
        `template_title` varchar(200) NOT NULL DEFAULT '' ,
        `template_content` text NOT NULL ,
        `addtime` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `uid` (`uid`)
        )ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='' AUTO_INCREMENT=1";
        D("UserToken")->execute($sql);

        //page表增加page_comments字段
        $columns = M("Page")->getDbFields();
        if ($columns) {
            $has_it = 0 ;//是否存在该字段
            foreach ($columns as $key => $value) {
                if ($value == 'page_comments') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0) {
                $sql = "ALTER TABLE ".C('DB_PREFIX')."page ADD page_comments varchar( 255 ) NOT NULL DEFAULT '' COMMENT '页面注释';";
                D("Page")->execute($sql);
            }
        }
        //page_history表增加page_comments字段
        $columns = M("PageHistory")->getDbFields();
        if ($columns) {
            $has_it = 0 ;//是否存在该字段
            foreach ($columns as $key => $value) {
                if ($value == 'page_comments') {
                    $has_it = 1 ;
                }
            }
            if ($has_it === 0) {
                $sql = "ALTER TABLE ".C('DB_PREFIX')."page_history ADD page_comments varchar( 255 ) NOT NULL DEFAULT '' COMMENT '页面注释';";
                D("PageHistory")->execute($sql);
            }
        }

        if(D("User")->where("uid = 1 ")->find()){
        $db_config = array(
            'DB_TYPE'   => 'Sqlite', 
            'DB_NAME'   => 'Sqlite/showdoc.db.php', 
            );
            M("User")->db(2,$db_config)->where("uid = 1 ")->delete();    
        }
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