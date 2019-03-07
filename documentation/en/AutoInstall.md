### 前言
 自动脚本脚本利用docker来安装运行环境，适用于linux服务器。如果你的服务器没有docker服务，脚本会尝试安装之。安装docker的过程可能有些慢。如果你已经安装过docker，脚本会省略部分步骤，从而加快showdoc安装进度。
 
当脚本安装docker失败时，你可以手动安装好docker后再执行脚本 。若装好了docker后还是再失败，则可根据此教程一步步地安装和调试： https://www.showdoc.cc/help?page_id=65610

如果服务器系统本身不支持docker，则只能通过手动安装PHP环境的方式来运行showdoc：https://www.showdoc.cc/help?page_id=13732


### 使用方法


 
 ```
  #下载脚本并赋予权限
 wget https://www.showdoc.cc/script/showdoc;chmod +x showdoc;
  
  #默认安装中文版。如果想安装英文版，请加上en参数，如 ./showdoc en
  ./showdoc en
 
 ```


### 安装后说明

安装好后，showdoc的数据都会存放在 /showdoc_data/html 目录下。./showdoc 脚本可放置在任何目录，方便以后使用。也可以重新从官方地址下载。

你可以打开 http://xxx.com:4999 来访问showdoc (xxx.com为你的服务器域名或者IP)。账户密码是showdoc/123456，登录后你便可以看到右上方的管理后台入口。建议登录后修改密码。

对showdoc的问题或建议请至https://github.com/star7th/showdoc 处提issue。


### 从手动方式升级到自动脚本方式
如果你之前是手动安装showdoc，可考虑升级到现在这种自动脚本方式。升级到脚本方式后，就可以使用上脚本的自动化功能 ，比如说升级到最新版，重启，卸载等。
升级方法：

1，首先参考前文部分，在服务器全新安装一个showdoc

2，把原来showdoc目录的Sqlite/showdoc.db.php覆盖/showdoc_data/html/Sqlite/showdoc.db.php  ，Public/Uploads覆盖 /showdoc_data/html/Public/Uploads

3，执行命令

 ```
 chmod 777 -R /showdoc_data/html
 ./showdoc update
 ```
 
 
### 其他命令

 ```
 
 #下面附上脚本其他命令，以便管理showdoc时可以用得上。

 #停止
 ./showdoc stop 
 
 #重启
 ./showdoc restart

 #升级showdoc到最新版
 ./showdoc update
  
 #卸载showdoc
 ./showdoc uninstall
 
 ```