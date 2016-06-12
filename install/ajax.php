<?php
// --------
// 	如果你能在浏览器中看到本句话，则证明你没有安装好PHP运行环境。请先安装好PHP运行环境
// --------
ini_set("display_errors", "Off");
error_reporting(E_ALL | E_STRICT);
header("Content-type: text/html; charset=utf-8"); 

if($f = file_get_contents("./install.lock")){
	ajax_out("本程序已经安装过！如果要解除安装锁定，则可删除/install目录下的install.lock文件后再重新访问本页面",10099);
}

if(!new_is_writeable("./")){
	ajax_out("请赋予 /install 目录以可写权限！",10098);
}

if(!new_is_writeable("../Public/Uploads")){
	ajax_out("请赋予 /Public/Uploads/ 目录以可写权限！",10098);
}

if(!new_is_writeable("../Application/Runtime")){
	ajax_out("请赋予 /Application/Runtime 目录以可写权限！",10095);
}

if(!new_is_writeable("../Application/Common/Conf/config.php")){
	ajax_out("请赋予 /Application/Common/Conf/config.php 文件以可写权限！",10094);
}


$db_type = $_POST["db_type"] ?  $_POST["db_type"] :"sqlite";
if ($db_type == "sqlite") {
	if(!new_is_writeable("../Sqlite")){
		ajax_out("请赋予 /Sqlite 目录以可写权限！",10097);
	}

	if(!new_is_writeable("../Sqlite/showdoc.db.php")){
		ajax_out("请赋予 /Sqlite/showdoc.db.php 以可写权限！",10096);
	}
    user_sqlite();
}
elseif ($db_type == "mysql") {
    user_mysql();
}
function user_sqlite(){
        clear_runtime();//清除缓存
        $config = 
<<<EOD
<?php
return array(
    //'配置项'=>'配置值'
    //使用sqlite数据库（ShowDoc默认）
    'DB_TYPE'   => 'Sqlite', 
    'DB_NAME'   => 'Sqlite/showdoc.db.php', 
    //使用mysql数据库
    //'DB_TYPE'   => 'mysql', 
    //'DB_NAME'   => 'showdoc',
    'DB_HOST'   => 'localhost',
    'DB_USER'   => 'showdoc', 
    'DB_PWD'    => 'showdoc123456',
    'DB_PORT'   => 3306, // 端口
    'DB_PREFIX' => '', // 数据库表前缀
    'DB_CHARSET'=> 'utf8', // 字符集
    'DB_DEBUG'  =>  TRUE, // 数据库调试模式 开启后可以记录SQL日志
    'URL_HTML_SUFFIX' => '',//url伪静态后缀
    'URL_MODEL' => 3 ,//URL兼容模式
    'URL_ROUTER_ON'   => true, 
    'URL_ROUTE_RULES'=>array(
        ':id\d'               => 'Home/Item/Show?item_id=:1',
        'uid/:id\d'               => 'Home/Item/showByUid?uid=:1',
    ),
    'URL_CASE_INSENSITIVE'=>true,
    'SHOW_ERROR_MSG'        =>  true,    // 显示错误信息，这样在部署模式下也能显示错误
);
EOD;
        $ret = file_put_contents("../Application/Common/Conf/config.php", $config);
        if ($ret) {
        	file_put_contents("./install.lock","http://doc.star7th.com/");
            ajax_out("安装成功！建议删除/install目录，以免安装脚本被再次执行。");
        }else{
            ajax_out("安装失败，配置文件写入错误！",10001);
        }
}

function user_mysql(){
        $db_host = $_POST["db_host"] ;
        $db_user = $_POST["db_user"] ;
        $db_port = $_POST["db_port"] ? $_POST["db_port"] :3306 ;
        $db_name = $_POST["db_name"] ;
        $db_password = $_POST["db_password"] ;

        clear_runtime();//清除缓存

        //检测数据库配置是否能链接得上

        $con = mysqli_connect($db_host,$db_user,$db_password,$db_name,$db_port);
        if (!$con ) {
           ajax_out("数据库链接错误，请检查配置信息是否填写正确",10002);
           exit();
        }
        mysqli_query($con, "SET NAMES UTF8");
        $row = mysqli_fetch_array(mysqli_query($con, " SELECT COUNT(*) FROM user "));
        
        if ($row) {
           ajax_out("检测到该数据库已经存在数据。请清理后再重试",10003);
           exit();
        }
        
        //开始导入mysql数据库 
        $ret = import_mysql($con);
        if (!$ret) {
           ajax_out("创建数据库表失败！",10004);
           exit();
        }       

        $config = "<?php ";
        $config .= "
return array(
    //'配置项'=>'配置值'
    //使用sqlite数据库（ShowDoc默认）
    //'DB_TYPE'   => 'Sqlite', 
    //'DB_NAME'   => 'Sqlite/showdoc.db.php', 
    //使用mysql数据库
    'DB_TYPE'   => 'mysql', 
    'DB_NAME'   => '{$db_name}',
    'DB_HOST'   => '{$db_host}',
    'DB_USER'   => '{$db_user}', 
    'DB_PWD'    => '{$db_password}',
    'DB_PORT'   => {$db_port}, // 端口
    'DB_PREFIX' => '', // 数据库表前缀
    'DB_CHARSET'=> 'utf8', // 字符集
    'DB_DEBUG'  =>  TRUE, // 数据库调试模式 开启后可以记录SQL日志
    'URL_HTML_SUFFIX' => '',//url伪静态后缀
    'URL_MODEL' => 3 ,//URL兼容模式
    'URL_ROUTER_ON'   => true, 
    'URL_ROUTE_RULES'=>array(
        ':id\d'               => 'Home/Item/Show?item_id=:1',
        'uid/:id\d'               => 'Home/Item/showByUid?uid=:1',
    ),
    'URL_CASE_INSENSITIVE'=>true,
    'SHOW_ERROR_MSG'        =>  true,    // 显示错误信息，这样在部署模式下也能显示错误
);";
        $ret = file_put_contents("../Application/Common/Conf/config.php", $config);


        if ($ret) {
        	file_put_contents("./install.lock","http://doc.star7th.com/");
            ajax_out("安装成功！建议删除/install目录，以免安装脚本被再次执行。");
        }else{
            ajax_out("安装失败，配置文件写入错误！",10001);
        }
}


function ajax_out($message,$error_code = 0){
        echo json_encode(array("error_code"=>$error_code,"message"=>$message));
        exit();
}

function clear_runtime($path = "../Application/Runtime"){  
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
        clear_runtime($path.'/'.$row);  
          
    }  
    //关闭目录句柄，否则出Permission denied  
    closedir($fh);    
    return true;  
} 


function import_mysql($con){

	//创建目录表
	$sql = "CREATE TABLE IF NOT EXISTS `catalog` (
	`cat_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '目录id',
	`cat_name` varchar(20) NOT NULL DEFAULT '' COMMENT '目录名',
	`item_id` int(10) NOT NULL DEFAULT '0' COMMENT '所在的项目id',
	`s_number` int(10) NOT NULL DEFAULT '99' COMMENT '顺序号。数字越小越靠前。若此值全部相等时则按id排序',
	`addtime` int(11) NOT NULL DEFAULT '0',
	`parent_cat_id` int(10) NOT NULL DEFAULT '0' COMMENT '上一级目录的id',
	`level` int(10) NOT NULL DEFAULT '2' COMMENT '2为二级目录，3为三级目录',
	PRIMARY KEY (`cat_id`),
	KEY `addtime` (`addtime`),
	KEY `s_number` (`s_number`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='目录表' AUTO_INCREMENT=1 ";
	mysqli_query($con, $sql);

	//创建item表
	$sql = "CREATE TABLE IF NOT EXISTS `item` (
	`item_id` int(10) NOT NULL AUTO_INCREMENT,
	`item_name` varchar(50) NOT NULL DEFAULT '',
	`item_description` varchar(225) NOT NULL DEFAULT '' COMMENT '项目描述',
	`uid` int(10) NOT NULL DEFAULT '0',
	`username` varchar(50) NOT NULL DEFAULT '',
	`password` varchar(50) NOT NULL DEFAULT '',
	`addtime` int(11) NOT NULL DEFAULT '0',
	`last_update_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后更新时间',
	PRIMARY KEY (`item_id`),
	KEY `addtime` (`addtime`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='项目表' AUTO_INCREMENT=1 ";
	mysqli_query($con, $sql);	

	//创建项目成员表
	$sql = "CREATE TABLE IF NOT EXISTS `item_member` (
	`item_member_id` int(10) NOT NULL AUTO_INCREMENT,
	`item_id` int(10) NOT NULL DEFAULT '0',
	`uid` int(10) NOT NULL DEFAULT '0',
	`username` varchar(50) NOT NULL DEFAULT '',
	`addtime` int(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`item_member_id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='项目成员表' AUTO_INCREMENT=1 ";
	mysqli_query($con, $sql);

	//创建项目page表
	$sql = "CREATE TABLE IF NOT EXISTS `page` (
	`page_id` int(10) NOT NULL AUTO_INCREMENT,
	`author_uid` int(10) NOT NULL DEFAULT '0' COMMENT '页面作者uid',
	`author_username` varchar(50) NOT NULL DEFAULT '' COMMENT '页面作者名字',
	`item_id` int(10) NOT NULL DEFAULT '0',
	`cat_id` int(10) NOT NULL DEFAULT '0',
	`page_title` varchar(50) NOT NULL DEFAULT '',
	`page_content` text NOT NULL,
	`s_number` int(10) NOT NULL DEFAULT '99' COMMENT '顺序号。数字越小越靠前。若此值全部相等时则按id排序',
	`addtime` int(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`page_id`),
	KEY `addtime` (`addtime`),
	KEY `s_number` (`s_number`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='文章页面表' AUTO_INCREMENT=1 ";
	mysqli_query($con, $sql);

	//创建项目page_history表
	$sql = "CREATE TABLE IF NOT EXISTS `page_history` (
	`page_history_id` int(10) NOT NULL AUTO_INCREMENT,
	`page_id` int(10) NOT NULL DEFAULT '0',
	`author_uid` int(10) NOT NULL DEFAULT '0' COMMENT '页面作者uid',
	`author_username` varchar(50) NOT NULL DEFAULT '' COMMENT '页面作者名字',
	`item_id` int(10) NOT NULL DEFAULT '0',
	`cat_id` int(10) NOT NULL DEFAULT '0',
	`page_title` varchar(50) NOT NULL DEFAULT '',
	`page_content` text NOT NULL,
	`s_number` int(10) NOT NULL DEFAULT '99' COMMENT '顺序号。数字越小越靠前。若此值全部相等时则按id排序',
	`addtime` int(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`page_history_id`),
	KEY `addtime` (`addtime`),
	KEY `page_id` (`page_id`)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='页面历史表' AUTO_INCREMENT=1 ";
	mysqli_query($con, $sql);

	//创建项目user表
	$sql = "CREATE TABLE IF NOT EXISTS `user` (
	`uid` int(10) NOT NULL AUTO_INCREMENT,
	`username` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '',
	`groupid` tinyint(2) NOT NULL DEFAULT '2' COMMENT '1为超级管理员，2为普通用户',
	`name` varchar(15) CHARACTER SET utf8 DEFAULT '',
	`avatar` varchar(200) CHARACTER SET utf8 DEFAULT '' COMMENT '头像',
	`avatar_small` varchar(200) DEFAULT '',
	`email` varchar(50) CHARACTER SET utf8 DEFAULT '',
	`password` varchar(50) CHARACTER SET utf8 NOT NULL,
	`cookie_token` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '实现cookie自动登录的token凭证',
	`cookie_token_expire` int(11) NOT NULL DEFAULT '0',
	`reg_time` int(11) NOT NULL DEFAULT '0',
	`last_login_time` int(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`uid`),
	UNIQUE KEY `username` (`username`) USING BTREE
	) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='用户表' AUTO_INCREMENT=1 ";

	$ret = mysqli_query($con, $sql);

	if ($ret) {
		return true;
	}else{
		return false;
	}

}

/**
 * 判断 文件/目录 是否可写（取代系统自带的 is_writeable 函数）
 *
 * @param string $file 文件/目录
 * @return boolean
 */
function new_is_writeable($file) {
	if (is_dir($file)){
		$dir = $file;
		if ($fp = @fopen("$dir/test.txt", 'w')) {
			@fclose($fp);
			@unlink("$dir/test.txt");
			$writeable = 1;
		} else {
			$writeable = 0;
		}
	} else {
		if ($fp = @fopen($file, 'a+')) {
			@fclose($fp);
			$writeable = 1;
		} else {
			$writeable = 0;
		}
	}

	return $writeable;
}
