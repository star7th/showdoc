<?php
// --------
//  如果你能在浏览器中看到本句话，则证明你没有安装好PHP运行环境。请先安装好PHP运行环境
// --------
ini_set("display_errors", "Off");
error_reporting(E_ALL | E_STRICT);
header("Content-type: text/html; charset=utf-8"); 
include("common.php");
if(file_exists('./install.lock') && $f = file_get_contents("./install.lock")){
  echo L("lock")."\n";
}else{
  user_sqlite();
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

  $ret = file_put_contents("../server/Application/Home/Conf/config.php", $config);
    return $ret ;
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
