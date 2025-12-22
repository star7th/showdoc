<?php
/**
 * ShowDoc安装脚本 - 非交互式安装
 */
ini_set("display_errors", "Off");
error_reporting(E_ALL | E_STRICT);
header("Content-type: text/html; charset=utf-8"); 
include("common.php");

// 获取语言
$lang = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : "zh";

// 检查是否已安装
if (is_install_locked()) {
  echo L("lock")."\n";
  exit();
}

// 检查环境
$check_result = check_environment();
if (!$check_result['status']) {
  echo implode("\n", $check_result['messages'])."\n";
  exit();
}

// 设置安装配置
if (set_install_config($lang)) {
  // 设置安装锁
  if (set_install_lock()) {
    echo 'install success !!!'."\n";
  } else {
    echo L("install_config_not_writable")."\n";
  }
} else {
  echo L("install_config_not_writable")."\n";
}


function user_sqlite(){
        clear_runtime();//清除缓存
        write_js_lang();
        
        $ret = write_home_config();
        if ($ret) {
          file_put_contents("./install.lock","https://www.showdoc.cc/");
            echo 'install success !!!'."\n";
        }else{
            echo "\n".L("not_writable_home_config")."\n";
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
    'DB_TYPE'   => 'Sqlite', 
    'DB_NAME'   => './Sqlite/showdoc.db.php', 
    'LANG_SWITCH_ON' => true,   // 开启语言包功能
    'LANG_AUTO_DETECT' => false, // 自动侦测语言 开启多语言功能后有效
    'DEFAULT_LANG' => '{$DEFAULT_LANG}', // 默认语言
    'LANG_LIST'        => 'zh-cn,en-us', // 允许切换的语言列表 用逗号分隔
    'VAR_LANGUAGE'     => 'l', // 默认语言切换变量
);";

  // 新架构不再需要 Home/Conf/config.php，直接返回成功
  $ret = true;
    return $ret ;
}

function write_js_lang(){
    $lang = $_REQUEST['lang'] ? $_REQUEST['lang'] :"zh";
    if ($lang == 'en') {
       replace_file_content("../web/index.html","zh-cn","en") ;
       replace_file_content("../web_src/index.html","zh-cn","en") ;
    }
    
}

