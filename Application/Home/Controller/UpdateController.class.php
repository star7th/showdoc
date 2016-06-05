<?php
namespace Home\Controller;
use Think\Controller;
class UpdateController extends BaseController {
    
 	//升级数据库
    public function db(){
    	if (strtolower(C("DB_TYPE")) == 'mysql' ) {
    		$this->mysql();
    	}
        elseif (strtolower(C("DB_TYPE")) == 'sqlite' ) {
            $this->sqlite();
        }
    	
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

        echo "OK!";
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

        echo 'OK!';
    }





}