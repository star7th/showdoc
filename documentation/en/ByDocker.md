
### 基础安装

安装前请确保你的环境已经装好了docker 。docker的安装教程在网上比较多，可以搜索了解下。这里重点介绍showdoc.

```
# 原版官方镜像安装命令(中国大陆用户不建议直接使用原版镜像，可以用后面的加速镜像)
docker pull star7th/showdoc 

# 中国大陆镜像安装命令（安装后记得执行docker tag命令以进行重命名）
docker pull registry.docker-cn.com/star7th/showdoc
docker tag registry.docker-cn.com/star7th/showdoc:latest star7th/showdoc:latest 

##后续命令无论使用官方镜像还是加速镜像都需要执行

#新建存放showdoc数据的目录
mkdir /showdoc_data
mkdir /showdoc_data/html
chmod 777 -R /showdoc_data

#启动showdoc容器。启动完了后别忘记后面还有转移数据的步骤。
docker run -d --name showdoc -p 4999:80 -v /showdoc_data/html:/var/www/html/ star7th/showdoc

#转移数据。执行这里的时候留意命令行界面有没有权限禁止的错误提示。
#如果有，则检查权限，或者安全限制（比如说可能selinux会禁止docker进程写文件）
docker exec showdoc \cp -fr /showdoc_data/html/ /var/www/
# 权限
chmod 777 -R /showdoc_data

```

根据以上命令操作的话，往后showdoc的数据都会存放在 /showdoc_data/html 目录下。
你可以打开 http://localhost:4999 来访问showdoc (localhost可改为你的服务器域名或者IP)。账户密码是showdoc/123456，登录后你便可以看到右上方的管理后台入口。建议登录后修改密码。
对showdoc的问题或建议请至https://github.com/star7th/showdoc 出提issue。若觉得showdoc好用，不妨点个star。

### 如何升级
   这里的升级是针对上面docker安装方式的升级。如果你原来是采用非docker安装方式（如php安装方式）的话，请跳过本部分文字，直接去看下部分。
```
//停止容器
docker stop showdoc 

//下载最新代码包
wget https://github.com/star7th/showdoc/archive/master.tar.gz
//解压
tar -zxvf master.tar.gz -C /showdoc_data/

rm -rf  /showdoc_data/html_bak
//备份。如果可以的话，命令中的html_bak还可以加上日期后缀，以便保留不同日期的多个备份
mv /showdoc_data/html  /showdoc_data/html_bak
mv /showdoc_data/showdoc-master /showdoc_data/html  ##// */

//赋予权限
chmod 777 -R /showdoc_data/html

//启动容器
docker start showdoc

//执行安装。默认安装中文版。如果想安装英文版，将下面参数中的zh改为en
curl http://localhost:4999/install/non_interactive.php?lang=zh

//转移旧数据库
\cp  -f  /showdoc_data/html_bak/Sqlite/showdoc.db.php /showdoc_data/html/Sqlite/showdoc.db.php

//转移旧附件数据
\cp -r -f /showdoc_data/html_bak/Public/Uploads /showdoc_data/html/Public/Uploads

// 执行数据库升级，看到OK字样便证明成功
curl http://localhost:4999?s=/home/update/db

//如果中途出错，请重命名原来的/showdoc_data/html_bak文件为/showdoc_data/html ，然后重启容器便可恢复。

```


### 非docker安装方式如何升级到docker安装方式

先参考前文，用docker方式全新安装一个showdoc，并且做好数据持久化。
接下来，假设你原来安装的旧showdoc已上传到服务器的 /tmp/showdoc 目录，那么
```
//转移旧数据库
\cp -r -f /tmp/showdoc/Sqlite/showdoc.db.php /showdoc_data/html/Sqlite/showdoc.db.php

//转移旧附件数据
\cp -r -f /tmp/showdoc/Public/Uploads /showdoc_data/html/Public/Uploads

// 执行数据库升级，看到OK字样便证明成功
curl http://localhost:4999?s=/home/update/db
```

### 数据备份
备份/showdoc_data/html 目录即可。比如执行下面命令压缩存放
```
zip -r /showdoc_data/showdoc_bak.zip  /showdoc_data/html 
//其中showdoc_bak.zip可以用日期后缀命名，以便多个备份。你也可以用定时任务来实现定时备份。
```
### 其他参考命令
```
 docker stop showdoc //停止容器
 docker restart showdoc //重启showdoc容器
 docker rm showdoc //删除showdoc容器
 docker rmi star7th/showdoc //删除showdoc镜像
 docker stop $(docker ps -a -q) ;docker rm $(docker ps -a -q) ;//停止并删除所有容器。危险命令，不懂勿用。
```
