###ShowDoc是什么

每当接手一个他人开发好的模块或者项目，看着那些没有写注释的代码，我们都无比抓狂。文档呢？！文档呢？！**Show me the doc  ！！**  
 
程序员都很希望别人能写技术文档，而自己却很不希望要写文档。因为写文档需要花大量的时间去处理格式排版，想着新建的word文档放在哪个目录等各种非技术细节。

word文档零零散散地放在团队不同人那里，需要文档的人基本靠吼，吼一声然后上qq或者邮箱接收对方丢过来的文档。这种沟通方式当然可以，只是效率不高。  
 
ShowDoc就是一个非常适合IT团队的在线文档分享工具，它可以加快团队之间沟通的效率。  

###它可以用来做什么

- #### API文档（ [查看Demo](http://doc.star7th.com/2)）

	随着移动互联网的发展，BaaS（后端即服务）越来越流行。服务端提供API，APP端或者网页前端便可方便调用数据。用ShowDoc可以非常方便快速地编写出美观的API文档。

- #### 数据字典（ [查看Demo](http://doc.star7th.com/1)）

	一份好的数据字典可以很方便地向别人说明你的数据库结构，如各个字段的释义等。

- #### 说明文档

	你完全可以使用showdoc来编写一些工具的说明书,也可以编写一些技术规范说明文档以供团队查阅

###它都有些什么功能

	
- ####分享与导出

	- 响应式网页设计，可将项目文档分享到电脑或移动设备查看。同时也可以将项目导出成word文件，以便离线浏览。

- ####权限管理
	- 公开项目与私密项目
		
		ShowDoc上的项目有公开项目和私密项目两种。公开项目可供任何登录与非登录的用户访问，而私密项目则需要输入密码验证访问。密码由项目创建者设置。
	
	- 项目转让
		
		项目创建者可以自由地把项目转让给网站的其他用户。
		
	- 项目成员
		
		你可以很方便地为ShowDoc的项目添加、删除项目成员。项目成员可以对项目进行编辑，但不可转让或删除项目（只有项目创建者才有权限）
- ####编辑功能
	- markdown编辑
		
		ShowDoc采用markdown编辑器，无论是编辑还是阅读体验都极佳很棒。如果你不了解Markdown，请在搜索引擎搜索"认识与入门 Markdown"
	
	- 模板插入
	
		在ShowDoc的编辑页面，点击编辑器上方的按钮可方便地插入API接口模板和数据字典模板。插入模板后，剩下的就是改动数据了，省去了很多编辑的力气。 
	
	- 历史版本
	
		ShowDoc为页面提供历史版本功能，你可以方便地把页面恢复到之前的版本。


###部署到自己的服务器

- 环境要求

	PHP5.3以上版本、php-mbstring模块、php-pdo模块、mysql数据库


- 克隆或者下载代码：

	[https://github.com/star7th/showdoc](https://github.com/star7th/showdoc)

- 导入数据库

	下载代码后，将根目录的showdoc.sql文件导入mysql数据库

- 修改配置文件

	进入Application/Common/Conf/目录，编辑config.php文件，填写相应的数据库信息

- 目录权限

	请确保Application/Runtime 和Public/Uploads 有可写权限

- 错误排查
	
	部署时出错，请先确认是否按照上面步骤执行（例如说目录权限有没有）。然后进Application/Runtime/Log看一下日志，或清除下Application/Runtime/目录下所有文件（缓存来的）试试。再遇到无法解决的问题可联系我，联系方式有博客、github、邮件等。

- nginx下的重定向规则
	
	请参考：[http://blog.star7th.com/2016/03/1969.html](http://blog.star7th.com/2016/03/1969.html)

###使用在线的ShowDoc

- 如果你没有自己的服务器，但又想使用ShowDoc作为分档分享工具，你可以使用在线的ShowDoc   [http://doc.star7th.com](http://doc.star7th.com/index.php/home/user/login)

- http://doc.star7th.com 作为在线服务会长期维护，请放心托管你的文档数据，不用担心突然关站的问题。如对数据非常敏感的个人或企业，还是把ShowDoc部署到自己的服务器比较好。

###版权

- ShowDoc遵循Apache2开源协议发布，并提供免费使用。  
版权所有Copyright © 2015 by star7th  
博客：[http://blog.star7th.com](http://blog.star7th.com)  
邮箱：xing7th#gmail.com（把#改为@）   
All rights reserved.