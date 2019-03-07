#### 技术栈说明

前端：Vue + ElementUI
后端：为了兼容低版本的php运行环境（兼容至5.3），使用的是保守的ThinkPHP3.2.3框架
数据库：showdoc自带文件型数据库（/Sqlite/showdoc.db.php)，不需要用户手动安装数据库

#### 开发前准备

开发机器需要先安装好PHP环境和NodeJS环境
下载代码并放置到PHP环境下的www目录
先在浏览器通过地址访问，以便完成showdoc的初始化安装（如已安装过则忽略）
在命令行里进入showdoc的web_src目录，执行npm install 以安装依赖。（若无npm，你则先要安装NodeJS环境）


#### 前端开发

执行npm run dev 以启用调式模式，通过访问 localhost:8080 便可以实时看到改动的效果。请使用代理以便请求后端API的时候代理到PHP服务端。
需要执行npm run build 才会最终打包生效。打包后的静态文件会在/web目录下

主要涉及到的目录和文件：
```
web_src/src/components   //页面组件基本都放在这里
web_src/src/router       //页面路由。可以根据url定位到组件
web_src/static           //静态资源目录
web_src/static/lang      //前端语言包
```

#### 后端开发

主要涉及到的目录和文件

```
server/Application/Api/              //应用目录，基本所有后台api都放在这里
server/Application/Runtime/Logs      //如果有错误日志，会直接打印出浏览器或者打印到这里
Public/Uploads                       //上传的图片放置在此处
server/Application/Api/Lang          //后端语言包
```


#### 其它说明

二次开发后请尊重开源协议，保留版权标识和链接
如开发了好用的功能，不妨贡献到官方github代码仓库以分享给大家用
showdoc往后升级可能会覆盖你原有的二次开发。如果想兼容，最好提交到官方仓库成为官方功能。