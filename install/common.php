<?php

/**
 * ShowDoc安装脚本 - 通用函数
 */

/**
 * 获取语言设置
 * @return array 语言数组
 */
function lang(){
  $lang = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : "zh";
  if (in_array($lang, ['zh', 'en'])) {
    return include("lang.".$lang.".php");
  }
  return include("lang.zh.php"); // 默认使用中文
}

/**
 * 获取语言字符串
 * @param string $field 语言键值
 * @return string 对应的语言文本
 */
function L($field){
  if (!isset($GLOBALS['lang_array'])) {
    $GLOBALS['lang_array'] = lang();
  }
  return isset($GLOBALS['lang_array'][$field]) ? $GLOBALS['lang_array'][$field] : $field;
}

/**
 * 判断文件/目录是否可写
 * @param string $file 文件/目录路径
 * @return boolean 是否可写
 */
function new_is_writeable($file) {
  if (is_dir($file)){
    $dir = $file;
    if ($fp = @fopen("$dir/test.txt", 'w')) {
      @fclose($fp);
      @unlink("$dir/test.txt");
      return true;
    }
    return false;
  } else {
    if ($fp = @fopen($file, 'a+')) {
      @fclose($fp);
      return true;
    }
    return false;
  }
}

/**
 * 检查文件/目录权限并返回错误信息
 * @param string $path 路径
 * @param string $errorMessage 错误信息
 * @return array [bool $status, string $message]
 */
function check_writable($path, $errorMessage) {
  if (!new_is_writeable($path)) {
    return [false, $errorMessage];
  }
  return [true, ''];
}

/**
 * 递归清除运行时缓存
 * @param string $path 目录路径
 * @return boolean 是否成功
 */
function clear_runtime($path = "../server/Application/Runtime"){  
  if (!is_dir($path)) {  
    return false;  
  }  

  $fh = opendir($path);  
  while(($row = readdir($fh)) !== false){  
    if ($row == '.' || $row == '..' || $row == 'index.html') {  
      continue;  
    }  

    $fullPath = $path.'/'.$row;
    if (!is_dir($fullPath)) {
      unlink($fullPath);  
    } else {
      clear_runtime($fullPath);
    }
  }  
  closedir($fh);
  return true;  
}

/**
 * 输出JSON格式的响应并退出
 * @param string $message 消息
 * @param int $error_code 错误代码
 */
function ajax_out($message, $error_code = 0) {
  echo json_encode([
    "error_code" => $error_code,
    "error_message" => $message
  ]);
  exit();
}

/**
 * 替换文件内容
 * @param string $file 文件路径
 * @param string $from 要替换的内容
 * @param string $to 替换后的内容
 * @return boolean 是否成功
 */
function replace_file_content($file, $from, $to) {
  if (!file_exists($file)) {
    return false;
  }
  
  $content = file_get_contents($file);
  $content = str_replace($from, $to, $content);
  return file_put_contents($file, $content) !== false;
}

/**
 * 检查环境要求
 * @return array ['status' => bool, 'messages' => string[]]
 */
function check_environment() {
  $result = [
    'status' => true,
    'messages' => []
  ];

  // 检测PHP版本
  if (version_compare(PHP_VERSION, '5.3.0', '<')) {
    $result['status'] = false;
    $result['messages'][] = L('require_php_version');
  }

  // 检测目录权限
  $directories = [
    './' => L("not_writable_install"),
    '../Public/Uploads' => L("not_writable_upload"),
    '../server/Application/Runtime' => L("not_writable_server_runtime"),
    '../Sqlite' => L("not_writable_sqlite"),
    '../Sqlite/showdoc.db.php' => L("not_writable_sqlite_db")
  ];

  foreach ($directories as $dir => $message) {
    if (!new_is_writeable($dir)) {
      $result['status'] = false;
      $result['messages'][] = $message;
    }
  }

  // 检查扩展
  $extensions = [
    'gd' => '请安装php-gd',
    'mbstring' => '请安装php-mbstring',
    'zlib' => '请安装php-zlib',
    'PDO' => '请安装php-pdo'
  ];

  foreach ($extensions as $ext => $message) {
    if ($ext === 'PDO') {
      if (!extension_loaded("PDO") && !extension_loaded("pdo")) {
        $result['status'] = false;
        $result['messages'][] = $message;
      }
    } else if (!extension_loaded($ext)) {
      $result['status'] = false;
      $result['messages'][] = $message;
    }
  }

  return $result;
}

/**
 * 检查安装锁定状态
 * @return boolean 是否已锁定
 */
function is_install_locked() {
  return file_exists('./install.lock');
}

/**
 * 设置安装配置
 * @param string $lang 语言
 * @return boolean 是否成功
 */
function set_install_config($lang = 'zh') {
  $default_lang = ($lang == 'en') ? 'en-us' : 'zh-cn';
  
  // 1. 修改HTML文件中的语言设置
  if ($lang == 'en') {
    replace_file_content("../web/index.html", "zh-cn", "en");
    replace_file_content("../web_src/index.html", "zh-cn", "en");
    // 清除缓存
    clear_runtime();
    
  }

  return true;
  

}

/**
 * 设置安装锁
 * @return boolean 是否成功
 */
function set_install_lock() {
  return file_put_contents("./install.lock", "https://www.showdoc.com.cn/") !== false;
}
