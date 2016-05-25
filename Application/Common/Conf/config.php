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
	"URL_HTML_SUFFIX" => '',//url伪静态后缀
	"URL_MODEL" => 3 ,//URL兼容模式
	'URL_ROUTER_ON'   => true, 
	'URL_ROUTE_RULES'=>array(
	    ':id\d'               => 'Home/Item/Show?item_id=:1',
	    'uid/:id\d'               => 'Home/Item/showByUid?uid=:1',
	),
	'URL_CASE_INSENSITIVE'=>true,
	'SHOW_ERROR_MSG'        =>  true,    // 显示错误信息，这样在部署模式下也能显示错误
);