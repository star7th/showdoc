<?php

/**
 * 判断语言
 */

function lang(){
  $lang = $_REQUEST['lang'] ? $_REQUEST['lang'] :"zh";
  if ($lang == 'zh-CN') {
    $lang = "zh";
  }
  return include("lang.".$lang.".php");
}


function L($field){
  if (!isset($GLOBALS['lang'])) {
      $GLOBALS['lang'] = lang();
  }
  return $GLOBALS['lang'][$field] ;
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

function clear_runtime($path = "../server/Application/Runtime"){  
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

function ajax_out($message,$error_code = 0){
        echo json_encode(array("error_code"=>$error_code,"message"=>$message));
        exit();
}