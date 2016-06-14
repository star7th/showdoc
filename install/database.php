<?php
// ShowDoc安装脚本
// install Showdoc
// 
// --------
// 	如果你能在浏览器中看到本句话，则证明你没有安装好PHP运行环境。请先安装好PHP运行环境
// --------
include("common.php");
$cur_lang = $_REQUEST['lang'] ? $_REQUEST['lang'] :"zh";

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title> ShowDoc</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="../Public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style type="text/css">
    @charset "utf-8";
	body {
		font:14px/1.5 "Microsoft Yahei","微软雅黑",Tahoma,Arial,Helvetica,STHeiti;
	}
    </style>

  </head>
  <body>
<link rel="stylesheet" href="../Public/css/login.css" />

    <div class="container">

      <form class="form-signin" method="post">
        <h3 class="form-signin-heading"><?php echo L("install_title");?></h3>
        <br>
        <div>
	        <select id="db_type">
	        	<option value="sqlite"><?php echo L("use_sqlite");?></option>
	        	<option value="mysql"><?php echo L("use_mysql");?></option>
	        </select>
        </div>
        <br>
        <div class="mysql-info" style="display:none">
	        <input type="text" class="input-block-level"  name="db_host" id = "db_host" placeholder="<?php echo L("server_address");?>">
	        <input type="text" class="input-block-level"  name="db_port" id = "db_port"  placeholder="<?php echo L("server_port");?>">
	        <input type="text" class="input-block-level"  name="db_name" id = "db_name"  placeholder="<?php echo L("db_name");?>">
	        <input type="text" class="input-block-level"  name="db_user" id = "db_user"  placeholder="<?php echo L("db_user");?>">
	        <input type="text" class="input-block-level"  name="db_password" id = "db_password"  placeholder="<?php echo L("db_password");?>">
        </div>
        <div class="sqlite_tips" ><?php echo L("sqlite_tips");?></div>
        <input type="hidden" value="<?php echo $cur_lang;?>" id="lang">
        <br>
        <div>
        	 <button class="btn btn-large btn-primary " id="start" type="submit"><?php echo L("go");?>&nbsp;&nbsp;<i class="icon-circle-arrow-right"></i></button>
          &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="http://www.showdoc.cc/help?page_id=16118" target="_blank"><?php echo L("FAQ");?></a>
        </div>
      </form>

    </div> <!-- /container -->

    
	<script src="../Public/js/common/jquery.min.js"></script>
    <script src="../Public/bootstrap/js/bootstrap.min.js"></script>
  </body>
</html> 

 <script type="text/javascript">
 $(function(){
 	$("#db_type").change(function(){
 		if ($("#db_type").val() == 'mysql') {
      $(".mysql-info").show();
 			$(".sqlite_tips").hide();
 		};
 		if ($("#db_type").val() == 'sqlite') {
      $(".mysql-info").hide();
      $(".sqlite_tips").show();
 		};
 	});

 	$("#start").click(function(){
 		var db_type = $("#db_type").val();
 		var db_host = $("#db_host").val();
 		var db_port = $("#db_port").val();
 		var db_name = $("#db_name").val();
    var db_user = $("#db_user").val();
 		var lang = $("#lang").val();
 		var db_password = $("#db_password").val();
 		$.post(
 			'ajax.php',
 			{"lang":lang,"db_type":db_type,"db_host":db_host,"db_port":db_port,"db_name":db_name,"db_user":db_user,"db_password":db_password},
 			function(data){
 				if (data.error_code === 0) {
 					//安装成功
			 		//alert(data.message);
          var text = '<div><?php echo L("install_success_help");?></div><br>';
			 		 text += '<div><a href="../" ><?php echo L("home");?></a></div>';
           $(".form-signin").html(text);
 				}else{
 					alert(data.message);
 				}
 			},
 			"json"

 			);

 		return false;
 	});
 });
</script>