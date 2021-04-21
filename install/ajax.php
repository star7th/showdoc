<?php
// --------
// 	如果你能在浏览器中看到本句话，则证明你没有安装好PHP运行环境。请先安装好PHP运行环境
// --------
ini_set("display_errors", "Off");
error_reporting(E_ALL | E_STRICT);
header("Content-type: text/html; charset=utf-8"); 
include("common.php");
$lang = $_REQUEST['lang'] ? $_REQUEST['lang'] :"zh";

if(file_exists('./install.lock') && $f = file_get_contents("./install.lock")){
	ajax_out(L("lock"),10099);
}

if(!new_is_writeable("./")){
	ajax_out(L("not_writable_install"),10098);
}

if(!new_is_writeable("../Public/Uploads")){
	ajax_out(L("not_writable_upload"),10098);
}


if(!new_is_writeable("../server/Application/Runtime")){
    ajax_out(L("not_writable_server_runtime"),10095);
}


if(!new_is_writeable("../Sqlite")){
    ajax_out(L("not_writable_sqlite"),10097);
}

if(!new_is_writeable("../Sqlite/showdoc.db.php")){
    ajax_out(L("not_writable_sqlite_db"),10096);
}


if($lang == 'en'){

    if(!new_is_writeable("../server/Application/Home/Conf/config.php")){
        ajax_out(L("not_writable_home_config"),10096);
    }
    if(!new_is_writeable("../web/index.html")){
        ajax_out(L("not_writable_web_docconfig"),10096);
    }
    if(!new_is_writeable("../web_src/index.html")){
        ajax_out(L("not_writable_web_src_docconfig"),10096);
    }    

    replace_file_content("../web/index.html","zh-cn","en") ;
    replace_file_content("../web_src/index.html","zh-cn","en") ;

    clear_runtime();//清除缓存
    
    $config = "<?php ";
    $config .= "
    return array(
    //'配置项'=>'配置值'
    'DB_TYPE'   => 'Sqlite', 
    'DB_NAME'   => './Sqlite/showdoc.db.php', 
    'LANG_SWITCH_ON' => true,   // 开启语言包功能
    'LANG_AUTO_DETECT' => false, // 自动侦测语言 开启多语言功能后有效
    'DEFAULT_LANG' => 'en-us', // 默认语言
    'LANG_LIST'        => 'zh-cn,en-us', // 允许切换的语言列表 用逗号分隔
    'VAR_LANGUAGE'     => 'l', // 默认语言切换变量
    );";

    $ret = file_put_contents("../server/Application/Home/Conf/config.php", $config);

    if (!$ret) {
        ajax_out(L("not_writable_home_config"),10001);
    }
}

clear_runtime();//清除缓存

file_put_contents("./install.lock","https://www.showdoc.com.cn/");
ajax_out(L("install_success"));


