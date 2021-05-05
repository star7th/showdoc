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
  if (!isset($GLOBALS['lang_array'])) {
      $GLOBALS['lang_array'] = lang();
  }
  return $GLOBALS['lang_array'][$field] ;
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
        echo json_encode(array("error_code"=>$error_code,"error_message"=>$message));
        exit();
}


function replace_file_content($file , $from ,$to ){
  $content = file_get_contents($file);
  $content2 = str_replace($from,$to,$content);
  if ($content2) {
      file_put_contents($file,$content2);
  }
}