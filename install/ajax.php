<?php
// --------
// 	如果你能在浏览器中看到本句话，则证明你没有安装好PHP运行环境。请先安装好PHP运行环境
// --------
ini_set("display_errors", "Off");
error_reporting(E_ALL | E_STRICT);
header("Content-type: text/html; charset=utf-8"); 
include("common.php");
if(file_exists('./install.lock') && $f = file_get_contents("./install.lock")){
	ajax_out(L("lock"),10099);
}

if(!new_is_writeable("./")){
	ajax_out(L("not_writable_install"),10098);
}

if(!new_is_writeable("../Public/Uploads")){
	ajax_out(L("not_writable_upload"),10098);
}

if(!new_is_writeable("../Application/Runtime")){
	ajax_out(L("not_writable_runtime"),10095);
}

if(!new_is_writeable("../server/Application/Runtime")){
    ajax_out(L("not_writable_server_runtime"),10095);
}

if(!new_is_writeable("../Application/Common/Conf/config.php")){
	ajax_out(L("not_writable_config"),10094);
}

if(!new_is_writeable("../Application/Home/Conf/config.php")){
	ajax_out(L("not_writable_home_config"),10098);
}


$db_type = $_POST["db_type"] ?  $_POST["db_type"] :"sqlite";
if ($db_type == "sqlite") {
	if(!new_is_writeable("../Sqlite")){
		ajax_out(L("not_writable_sqlite"),10097);
	}

	if(!new_is_writeable("../Sqlite/showdoc.db.php")){
		ajax_out(L("not_writable_sqlite_db"),10096);
	}
    user_sqlite();
}
elseif ($db_type == "mysql") {
    //showdoc不再支持mysql http://www.showdoc.cc/help?page_id=31990
}
function user_sqlite(){
        clear_runtime();//清除缓存
        write_home_config();
        write_js_lang();
        $config = 
<<<EOD
<?php
return array(
    //'配置项'=>'配置值'
    //使用sqlite数据库
    'DB_TYPE'   => 'Sqlite', 
    'DB_NAME'   => 'Sqlite/showdoc.db.php', 
    //showdoc不再支持mysql http://www.showdoc.cc/help?page_id=31990
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
        ':id\d'               => 'Home/Item/show?item_id=:1',
		':domain\s$'               => 'Home/Item/show?item_domain=:1',//item的个性域名
        'uid/:id\d'               => 'Home/Item/showByUid?uid=:1',
        'page/:id\d'               => 'Home/Page/single?page_id=:1',
    ),
    'URL_CASE_INSENSITIVE'=>true,
    'SHOW_ERROR_MSG'        =>  true,    // 显示错误信息，这样在部署模式下也能显示错误
    'STATS_CODE' =>'',  //可选，统计代码
    'TMPL_CACHE_ON' => false,//禁止模板编译缓存
    'HTML_CACHE_ON' => false,//禁止静态缓存
    //上传文件到七牛的配置
    'UPLOAD_SITEIMG_QINIU' => array(
                    'maxSize' => 5 * 1024 * 1024,//文件大小
                    'rootPath' => './',
                    'saveName' => array ('uniqid', ''),
                    'driver' => 'Qiniu',
                    'driverConfig' => array (
                            'secrectKey' => '', 
                            'accessKey' => '',
                            'domain' => '',
                            'bucket' => '', 
                        )
                    ),
);
EOD;
        $ret = file_put_contents("../Application/Common/Conf/config.php", $config);
        if ($ret) {
        	file_put_contents("./install.lock","https://www.showdoc.cc/");
            ajax_out(L("install_success"));
        }else{
            ajax_out(L("install_config_not_writable"),10001);
        }
}


function write_home_config(){
	$lang = $_REQUEST['lang'] ? $_REQUEST['lang'] :"zh";
	if ($lang == 'en') {
		$DEFAULT_LANG = 'en-us';
	}else{
		$DEFAULT_LANG = 'zh-cn';
	}
        $config = "<?php ";
        $config .= "
return array(
	//'配置项'=>'配置值'
    'LANG_SWITCH_ON' => true,   // 开启语言包功能
    'LANG_AUTO_DETECT' => false, // 自动侦测语言 开启多语言功能后有效
    'DEFAULT_LANG' => '{$DEFAULT_LANG}', // 默认语言
    'LANG_LIST'        => 'zh-cn,en-us', // 允许切换的语言列表 用逗号分隔
    'VAR_LANGUAGE'     => 'l', // 默认语言切换变量
);";

	$ret = file_put_contents("../Application/Home/Conf/config.php", $config);

}

function write_js_lang(){
    $lang = $_REQUEST['lang'] ? $_REQUEST['lang'] :"zh";
    if ($lang == 'en') {
       replace_file_content("../web/index.html","zh-cn","en") ;
       replace_file_content("../web_src/index.html","zh-cn","en") ;
    }
    
}

function replace_file_content($file , $from ,$to ){
    $content = file_get_contents($file);
    $content2 = str_replace($from,$to,$content);
    if ($content2) {
        file_put_contents($file,$content2);
    }
}
