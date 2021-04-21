<?php
// ShowDoc安装脚本
// install Showdoc
// 
// --------
// 	如果你能在浏览器中看到本句话，则证明你没有安装好PHP运行环境。请先安装好PHP运行环境
// --------
include("common.php");
// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die(L('require_php_version'));

if(file_exists('./install.lock') && $f = file_get_contents("./install.lock")){
  echo L("lock").'<br>';
  exit();
}

$go = 1 ;

//检测文件权限
if(!new_is_writeable("./")){
  //本安装目录需要写入lock文件
  echo L("not_writable_install").'<br>';
  $go = 0;
}
if(!new_is_writeable("../Public/Uploads")){
  echo L("not_writable_upload").'<br>';
  $go = 0;
}

if(!new_is_writeable("../server/Application/Runtime")){
  echo L("not_writable_server_runtime").'<br>';
  $go = 0;
}


//检查扩展
if(!extension_loaded("gd")){
  echo '请安装php-gd<br>';
  $go = 0;
}
/*
if(!extension_loaded("mcrypt")){
  echo '请安装php-mcrypt<br>';
  $go = 0;
}
*/
if(!extension_loaded("mbstring")){
  echo '请安装php-mbstring<br>';
  $go = 0;
}

if(!extension_loaded("zlib")){
  echo '请安装php-zlib<br>';
  $go = 0;
}

if(!extension_loaded("PDO") && !extension_loaded("pdo") ){
  echo '请安装php-pdo<br>';
  $go = 0;
}

/*if(extension_loaded("sqlite") || extension_loaded("sqlite3")){
  echo '请安装php-sqlite<br>';
  $go = 0;
}
*/

if (!$go) {
  exit();
}


?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title> ShowDoc</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <style type="text/css">
        html,body{
          margin:0 0 ;
          padding:0 0 ;
        }
        .container{
            display: -webkit-flex; /* Safari */
            display: flex;
            height:100%;
            width:100%;
            position: absolute;
          }

        .flex-item{
          display: -webkit-inline-flex;
          display: inline-flex;
          justify-content:center; /*水平居中*/
          align-items: center; /*垂直居中*/
        }
          
        .left{
          width: 50%;
          background-color:#F59064;
          /* background-image: linear-gradient(to right, red, white); */
          height:100%;
          
        }
        .right{
          width: 50%;
          background-color:#87CEEB;
          /* background-image: linear-gradient(to  left,  black , white); */
          height:100% ;
        }
        .text{
          font-size:30px;
          cursor:pointer ;
        }
    </style>

  </head>
  <body>

    <div class="container">

      <div class="flex-item left">
        <div class="text" id="en">
          Language : English &nbsp;   →
        </div>
      </div>
      <div class="flex-item right">
      <div class="text" id="zh">
          语言 ：中文  &nbsp;  →
        </div>
      </div>
    </div> <!-- /container -->

  </body>
</html> 
<script>
 　document.getElementById("en").onclick = function(){ 
      toInstall("en"); 
   }
   document.getElementById("zh").onclick = function(){ 
      toInstall("zh"); 
   }

   function toInstall (lang){
      //创建异步对象  
      var xhr = new XMLHttpRequest();
      xhr.open("GET","ajax.php?lang="+lang,true);
      xhr.send();
      xhr.onreadystatechange = function () {
        // 这步为判断服务器是否正确响应
        if (xhr.readyState == 4 && xhr.status == 200) {
          var json = JSON.parse(xhr.responseText) ;
          // console.log(json);
          if(json.error_code === 0){
            window.location.href = "../";
          }else{
            alert(json.error_message)
          }
        }
      };
   }
</script>