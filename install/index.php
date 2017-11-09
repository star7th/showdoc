<?php
// ShowDoc安装脚本
// install Showdoc
// 
// --------
// 	如果你能在浏览器中看到本句话，则证明你没有安装好PHP运行环境。请先安装好PHP运行环境
// --------
include("common.php");

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

      <form class="form-signin" method="get" action="database.php">
        <h3 class="form-signin-heading">选择语言<br>(Choose language)</h3>
        <br>
        <div>
	        <select id="db_type" name="lang">
	        	<option value="zh">中文</option>
	        	<option value="en">English</option>
	        </select>
        </div>
        <br>

        <br>
        <div>
        	 <button class="btn btn-large btn-primary " id="start" type="submit">ok&nbsp;&nbsp;<i class="icon-circle-arrow-right"></i></button>
        </div>
      </form>

    </div> <!-- /container -->

    
	<script src="../Public/js/common/jquery.min.js"></script>
    <script src="../Public/bootstrap/js/bootstrap.min.js"></script>
  </body>
</html> 
