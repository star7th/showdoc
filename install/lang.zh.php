<?php
return array(

  "install_title"=>'安装ShowDoc',
  "use_sqlite"=>'使用Sqlite数据库',
  "use_mysql"=>'使用Mysql数据库',
  "server_address"=>'服务器地址，一般为localhost',
  "server_port"=>'端口，一般为3306',
  "db_name"=>'数据库名，建议数据库名为showdoc',
  "db_user"=>'数据库用户名',
  "db_password"=>'数据库密码',
  "sqlite_tips"=>'PHP内置支持Sqlite数据库，你无须再配置数据库，直接点击开始即可',
  "go"=>'开始',
  "install_success_help"=>'安装成功！默认管理员账户密码是showdoc/123456。登录后，在右上角可以看到管理后台入口。此外，强烈建议修改管理员初始密码。若再遇到问题，可参考ShowDoc帮助文档：<a href="https://www.showdoc.cc/help" target="_blank">https://www.showdoc.cc/help</a>',
  "home"=>'进入网站首页',
  "FAQ"=>'常见问题',


  "lock"=>'本程序已经安装过！如果要解除安装锁定，则可删除install目录下的install.lock文件后再重新访问本页面',
  "not_writable_install"=>'请赋予 install 目录以可写权限！',
  "not_writable_upload"=>'请赋予 Public/Uploads/ 目录以可写权限！',
  "not_writable_runtime"=>'请赋予 server/Application/Runtime 目录以可写权限！',
  "not_writable_server_runtime"=>'请赋予 server/Application/Runtime 目录以可写权限！',
  "not_writable_config"=>'请赋予 server/Application/Common/Conf/config.php 文件以可写权限！',
  "not_writable_home_config"=>'请赋予 server/Application/Home/Conf/config.php 文件以可写权限！<br>(如果你确定赋予了文件权限但却一直看到此信息，则可考虑关闭selinux试试)',
  "not_writable_sqlite"=>'请赋予 Sqlite 目录以可写权限！',
  "not_writable_sqlite_db"=>'请赋予 Sqlite/showdoc.db.php 以可写权限！',
  "not_writable_web_docconfig"=>'请赋予 web/index.html 以可写权限！',
  "not_writable_web_src_docconfig"=>'请赋予 web_src/index.html 以可写权限！',
  "install_success"=>'安装成功！建议删除install目录，以免安装脚本被再次执行。',
  "install_config_not_writable"=>'安装失败，配置文件写入错误！',
  "db_wrong"=>'数据库链接错误，请检查配置信息是否填写正确',
  "db_has_data"=>'检测到该数据库已经存在数据。请清理后再重试',
  "create_table_fail"=>'创建数据库表失败！',
  "install_config_not_writable"=>'安装失败，配置文件写入错误！',

  "require_php_version" => "需要PHP5.3.0以上版本",
  );