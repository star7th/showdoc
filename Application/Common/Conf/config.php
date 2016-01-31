<?php
return array(
	//'配置项'=>'配置值'
	'DB_TYPE'   => 'mysql', // 数据库类型
	'DB_HOST'   => 'localhost',
	'DB_NAME'   => 'showdoc', 
	'DB_USER'   => 'showdoc', 
	'DB_PWD'    => 'showdoc123456',
	'DB_PORT'   => 3306, // 端口
	'DB_PREFIX' => '', // 数据库表前缀
	'DB_CHARSET'=> 'utf8', // 字符集
	'DB_DEBUG'  =>  TRUE, // 数据库调试模式 开启后可以记录SQL日志
	"URL_HTML_SUFFIX" => '',//url伪静态后缀
	'URL_ROUTER_ON'   => true, 
	'URL_ROUTE_RULES'=>array(
	    ':id\d'               => 'Home/Item/Show?item_id=:1',
	),
	'URL_CASE_INSENSITIVE'=>true
);