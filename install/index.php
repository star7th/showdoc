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
          background-color:#fff;
          /* background-image: linear-gradient(to right, red, white); */
          height:100%;
          
        }
        .right{
          width: 50%;
          background-color:#24292e;
          /* background-image: linear-gradient(to  left,  black , white); */
          height:100% ;
          color:#fff;
        }
        .lang-text{
          font-size:30px;
          cursor:pointer ;
        }
        .left a{
          color:#000;
        }

        .right a{
          color:#fff;
        }
        .en-tips , .zh-tips{
          display:none ;
          font-size:20px;
        }
    </style>

  </head>
  <body>

    <div class="container">

      <div class="flex-item left">
        <div class="lang-text" id="en">
        Choose language: English &nbsp;   →
        </div>

        <div class="en-tips">
        Initialization successful. The default administrator account password is showdoc / 123456.<br>After logging in, you can see the management background entrance in the upper right corner.<br><a href="../web/">Click to enter the home page</a>
        </div>

      </div>
      <div class="flex-item right">

      <div class="lang-text" id="zh">
          选择语言 ：中文  &nbsp;  →
        </div>
        
        <div class="zh-tips">
         初始化成功。默认管理员账户密码是showdoc/123456。<br>登录后，在右上角可以看到管理后台入口。<a href="../web/">点击进入首页</a>
        </div>
      </div>

        
    </div> <!-- /container -->

  </body>
</html>
<script src=../web/static/jquery.min.js></script>
<script>

  $("#en").click(function(){
    toInstall("en");
  });
  $("#zh").click(function(){
    toInstall("zh"); 
  });


   function toInstall (lang){

      $.get("ajax.php?lang="+lang,function(data){
          if(data.error_code === 0){
            if(lang == 'en'){
              showEnTips()
            }else{
              showZhTips()
            }
          }else{
            alert(data.error_message)
          }
      },'json');

   }



   function showEnTips(){
     $(".right").hide();
     $(".left").css("width",'100%');
     $(".left .lang-text").hide();
     $(".en-tips").show();
   }
   function showZhTips(){
     $(".left").hide();
     $(".right").css("width",'100%');
     $(".right .lang-text").hide();
     $(".zh-tips").show();
   }

</script>