<?php
// ShowDoc安装脚本
// install Showdoc
// 
// --------
// 	如果你能在浏览器中看到本句话，则证明你没有安装好PHP运行环境。请先安装好PHP运行环境
// --------

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
        <h3 class="form-signin-heading">安装ShowDoc</h3>
        <br>
        <div>
	        <select id="db_type">
	        	<option value="sqlite">使用Sqlite数据库</option>
	        	<option value="mysql">使用Mysql数据库</option>
	        </select>
        </div>
        <br>
        <div class="mysql-info" style="display:none">
	        <input type="text" class="input-block-level"  name="db_host" id = "db_host" placeholder="服务器地址，一般为localhost">
	        <input type="text" class="input-block-level"  name="db_port" id = "db_port"  placeholder="端口，一般为3306">
	        <input type="text" class="input-block-level"  name="db_name" id = "db_name"  placeholder="数据库名，建议数据库名为showdoc">
	        <input type="text" class="input-block-level"  name="db_user" id = "db_user"  placeholder="数据库用户名">
	        <input type="text" class="input-block-level"  name="db_password" id = "db_password"  placeholder="数据库密码">
        </div>

        <div class="sqlite_tips" >PHP内置支持Sqlite数据库，你无须再配置数据库，直接点击开始即可</div>

        <br>
        <div>
        	 <button class="btn btn-large btn-primary " id="start" type="submit">开始&nbsp;&nbsp;<i class="icon-circle-arrow-right"></i></button>
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
 		var db_password = $("#db_password").val();
 		$.post(
 			'ajax.php',
 			{"db_type":db_type,"db_host":db_host,"db_port":db_port,"db_name":db_name,"db_user":db_user,"db_password":db_password},
 			function(data){
 				if (data.error_code === 0) {
 					//安装成功
			 		//alert(data.message);
          var text = '<div>安装成功！建议删除/install目录，以免安装脚本被再次执行。若再遇到问题，可参考ShowDoc帮助文档：<a href="http://doc.star7th.com/3" target="_blank">http://doc.star7th.com/3</a></div><br>';
			 		 text += '<div><a href="../" >进入网站首页</a></div>';
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