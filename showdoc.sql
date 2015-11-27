-- phpMyAdmin SQL Dump
-- version 4.0.10
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2015-11-27 22:22:54
-- 服务器版本: 5.1.73
-- PHP 版本: 5.4.41

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `showdoc`
--

-- --------------------------------------------------------

--
-- 表的结构 `catalog`
--

CREATE TABLE IF NOT EXISTS `catalog` (
  `cat_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '目录id',
  `cat_name` varchar(20) NOT NULL DEFAULT '' COMMENT '目录名',
  `item_id` int(10) NOT NULL DEFAULT '0' COMMENT '所在的项目id',
  `order` int(10) NOT NULL DEFAULT '99' COMMENT '顺序号。数字越小越靠前。若此值全部相等时则按id排序',
  `addtime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cat_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='目录表' AUTO_INCREMENT=6 ;

--
-- 转存表中的数据 `catalog`
--

INSERT INTO `catalog` (`cat_id`, `cat_name`, `item_id`, `order`, `addtime`) VALUES
(1, '接口示例', 2, 99, 1448461340),
(3, '数据字典示例', 2, 99, 1448548858);

-- --------------------------------------------------------

--
-- 表的结构 `item`
--

CREATE TABLE IF NOT EXISTS `item` (
  `item_id` int(10) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(50) NOT NULL DEFAULT '',
  `item_description` varchar(225) NOT NULL DEFAULT '' COMMENT '项目描述',
  `uid` int(10) NOT NULL DEFAULT '0',
  `username` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(50) NOT NULL DEFAULT '',
  `addtime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='项目表' AUTO_INCREMENT=4 ;

--
-- 转存表中的数据 `item`
--

INSERT INTO `item` (`item_id`, `item_name`, `item_description`, `uid`, `username`, `password`, `addtime`) VALUES
(1, 'ShowDoc数据字典', 'ShowDoc数据结构字典', 1, 'showdoc', '', 1448457876),
(2, '示例文档', '示例文档', 1, 'showdoc', '', 1448460984),
(3, 'ShowDoc', '介绍ShowDoc的各自功能', 1, 'showdoc', '', 1448541665);

-- --------------------------------------------------------

--
-- 表的结构 `item_member`
--

CREATE TABLE IF NOT EXISTS `item_member` (
  `item_member_id` int(10) NOT NULL AUTO_INCREMENT,
  `item_id` int(10) NOT NULL DEFAULT '0',
  `uid` int(10) NOT NULL DEFAULT '0',
  `username` varchar(50) NOT NULL DEFAULT '',
  `addtime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_member_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='项目成员表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `page`
--

CREATE TABLE IF NOT EXISTS `page` (
  `page_id` int(10) NOT NULL AUTO_INCREMENT,
  `author_uid` int(10) NOT NULL DEFAULT '0' COMMENT '页面作者uid',
  `author_username` varchar(50) NOT NULL DEFAULT '' COMMENT '页面作者名字',
  `item_id` int(10) NOT NULL DEFAULT '0',
  `cat_id` int(10) NOT NULL DEFAULT '0',
  `page_title` varchar(50) NOT NULL DEFAULT '',
  `page_content` text NOT NULL,
  `order` int(10) NOT NULL DEFAULT '99' COMMENT '顺序号。数字越小越靠前。若此值全部相等时则按id排序',
  `addtime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`page_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='文章页面表' AUTO_INCREMENT=23 ;

--
-- 转存表中的数据 `page`
--

INSERT INTO `page` (`page_id`, `author_uid`, `author_username`, `item_id`, `cat_id`, `page_title`, `page_content`, `order`, `addtime`) VALUES
(1, 1, 'showdoc', 1, 0, 'user', '-  用户表，储存用户信息\n\n|字段|类型|空|默认|注释|\n|:----    |:-------    |:--- |-- -|------      |\n|uid	  |int(10)     |否	|	 |	           |\n|username |varchar(20) |否	|    |	 用户名	|\n|groupid  |tinyint(2)   |否	|  2  |	 1为管理员，2为普通用户。此字段保留方便以后扩展	|\n|password |varchar(50) |否   |    |	 密码		 |\n|cookie_token |varchar(50) |否   |    |	 实现cookie自动登录的token凭证 |\n|cookie_token_expire |int(11) |否   |    |	 过期时间		 |\n|avatar |varchar(200) |是   |    |	 头像		 |\n|avatar_small |varchar(200) |是   |    |	 小头像	 |\n|email |varchar(50) |否   |    |	 邮箱		 |\n|name     |varchar(15) |是   |    |    昵称     |\n|reg_time |int(11)     |否   | 0  |   注册时间  |\n|last_login_time |int(11)     |否   | 0  |   最后一次登录时间  |\n\n- 备注：无\n\n', 2, 1448597059),
(2, 1, 'showdoc', 1, 0, 'page_history', '\n-  页面历史表，保存页面历史\n\n|字段|类型|空|默认|注释|\n|:----    |:-------    |:--- |-- -|------      |\n|page_history_id |int(10)     |否	|	 |	自增id           |\n|page_id |int(10) |否	|  0  |	 页面id	|\n|author_uid |int(10) |否   |  0  |	 页面作者uid		 |\n|author_username     |varchar(50) |否   |    |    页面作者用户名     |\n|item_id |int(10)     |否   | 0  |   项目id  |\n|cat_id |int(10)     |否   | 0  |   父目录id  |\n|page_title |varchar(50)	    |否   |   |   页面标题  |\n|page_content  |text     |否   |   |   页面内容  |\n|order |int(10)     |否   | 99  |   顺序号。数字越小越靠前  |\n|addtime |int(11)     |否   | 0  |   该记录添加的时间。可认为是页面的修改时间  |\n\n- 备注：无\n\n', 6, 1448513535),
(3, 1, 'showdoc', 1, 0, 'page', '\n-  页面表，保存编辑的页面内容\n\n|字段|类型|空|默认|注释|\n|:----    |:-------    |:--- |-- -|------      |\n|page_id |int(10) |否	|  0  |	 页面自增id	|\n|author_uid |int(10) |否   |  0  |	 页面作者uid		 |\n|author_username     |varchar(50) |否   |    |    页面作者用户名     |\n|item_id |int(10)     |否   | 0  |   项目id  |\n|cat_id |int(10)     |否   | 0  |   父目录id  |\n|page_title |varchar(50)	    |否   |   |   页面标题  |\n|page_content  |text     |否   |   |   页面内容  |\n|order |int(10)     |否   | 99  |   顺序号。数字越小越靠前  |\n|addtime |int(11)     |否   | 0  |   该记录添加的时间。可认为是页面的修改时间  |\n\n- 备注：无\n\n', 5, 1448513525),
(4, 1, 'showdoc', 1, 0, 'item', '\n-  项目表，储存项目信息\n\n|字段|类型|空|默认|注释|\n|:----    |:-------    |:--- |-- -|------      |\n|item_id	  |int(10)     |否	|	 |	 项目id、自增id          |\n|item_name |varchar(50) |否	|    |	 项目名	|\n|item_description |varchar(225) |否   |    |	 项目描述		 |\n|uid     |int(10) |是   |    |    创建人uid     |\n|username |varchar(50)     |否   |   |   创建人用户名  |\n|username |varchar(50)     |否   |   |   创建人用户名  |\n|password |varchar(50)     |否   |   |   项目密码。可为空。空表示可以公开访问的项目  |\n|addtime |int(11)     |否   |   |   项目添加的时间，时间戳  |\n\n- 备注：无\n\n', 2, 1448513501),
(5, 1, 'showdoc', 1, 0, 'catalog', '\n-  目录表，储存页面目录信息\n\n|字段|类型|空|默认|注释|\n|:----    |:-------    |:--- |-- -|------      |\n|cat_id	  |int(10)     |否	|	 |	目录id，自增           |\n|cat_name |varchar(20) |否	|    |	 目录名	|\n|item_id |int(50) |否   |    |	 目录所在的项目id		 |\n|order     |int(10) |否   | 99   |    顺序。数字越小越靠前     |\n|addtime  |int(10)     |否   | 0  |   添加时间，时间戳  |\n\n- 备注：无\n\n', 3, 1448513509),
(6, 1, 'showdoc', 1, 0, 'item_member', '\n-  项目成员表\n\n|字段|类型|空|默认|注释|\n|:----    |:-------    |:--- |-- -|------      |\n|item_member_id	  |int(10)     |否	|	 |	  自增id         |\n|item_id |int(10) |否	|    |	 项目id	|\n|uid |int(10) |否   |    |	 成员uid		 |\n|username     |varchar(50) |是   |    |    成员用户名     |\n|addtime |int(11)     |否   | 0  |   添加时间  |\n\n- 备注：无\n\n', 4, 1448513517),
(13, 1, 'showdoc', 1, 0, '序言', '这是ShowDoc的数据字典\n\n最后更新：2015-11-27\n\nby star7th\n\n\n', 1, 1448597049),
(7, 1, 'showdoc', 2, 0, '序言', '###这是一个示例文档\n你可以点击左侧菜单以查看接口示例和数据字典示例', 99, 1448549158),
(9, 1, 'showdoc', 2, 1, '用户注册', '**简要描述：** \n\n- 用户注册接口\n\n**请求URL：** \n- ` http://xx.com/api/user/register `\n  \n**请求方式：**\n- POST \n\n**参数：** \n\n|参数名|必选|类型|说明|\n|:----    |:---|:----- |-----   |\n|username |是  |string |用户名   |\n|password |是  |string | 密码    |\n|name     |否  |string | 昵称    |\n\n **返回示例**\n``` \n  {\n    &quot;error_code&quot;: 0,\n    &quot;data&quot;: {\n      &quot;uid&quot;: &quot;1&quot;,\n      &quot;username&quot;: &quot;12154545&quot;,\n      &quot;name&quot;: &quot;吴系挂&quot;,\n      &quot;groupid&quot;: 2 ,\n      &quot;reg_time&quot;: &quot;1436864169&quot;,\n      &quot;last_login_time&quot;: &quot;0&quot;,\n    }\n  }\n```\n **返回参数说明** \n\n|参数名|类型|说明|\n|:-----  |:-----|-----                           |\n|groupid |int   |用户组id，1：超级管理员；2：普通用户  |\n\n **备注** \n\n- 更多返回错误代码请看首页的错误代码描述\n\n', 99, 1448512640),
(10, 1, 'showdoc', 2, 1, '用户登录', '**简要描述：** \n\n- 用户登录接口\n\n**请求URL：** \n- ` http://xx.com/api/user/login `\n  \n**请求方式：**\n- POST \n\n**参数：** \n\n|参数名|必选|类型|说明|\n|:----    |:---|:----- |-----   |\n|username |是  |string |用户名   |\n|password |是  |string | 密码    |\n\n\n **返回示例**\n``` \n  {\n    &quot;error_code&quot;: 0,\n    &quot;data&quot;: {\n      &quot;uid&quot;: &quot;1&quot;,\n      &quot;username&quot;: &quot;12154545&quot;,\n      &quot;name&quot;: &quot;吴系挂&quot;,\n      &quot;groupid&quot;: 2 ,\n      &quot;reg_time&quot;: &quot;1436864169&quot;,\n      &quot;last_login_time&quot;: &quot;0&quot;,\n    }\n  }\n```\n **返回参数说明** \n\n|参数名|类型|说明|\n|:-----  |:-----|-----                           |\n|groupid |int   |用户组id，1：超级管理员；2：普通用户  |\n\n **备注** \n\n- 更多返回错误代码请看首页的错误代码描述\n\n', 99, 1448512651),
(11, 1, 'showdoc', 2, 1, '省份数据', '**简要描述：** \n\n- 获取全国省份数据\n\n**请求URL：** \n- ` http://xx.com/api/geograph/province `\n  \n**请求方式：**\n- GET \n\n**参数：** \n\n无\n\n **返回示例**\n``` \n{\n    &quot;error_code&quot;: 0,\n    &quot;data&quot;: [\n        {\n            &quot;id&quot;: &quot;1&quot;,\n            &quot;code&quot;: &quot;11&quot;,\n            &quot;parentid&quot;: &quot;0&quot;,\n            &quot;name&quot;: &quot;北京市&quot;,\n            &quot;level&quot;: &quot;1&quot;\n        },\n        {\n            &quot;id&quot;: &quot;636&quot;,\n            &quot;code&quot;: &quot;13&quot;,\n            &quot;parentid&quot;: &quot;0&quot;,\n            &quot;name&quot;: &quot;河北省&quot;,\n            &quot;level&quot;: &quot;1&quot;\n        },\n     .....     \n    ]\n}\n\n```\n **返回参数说明** \n\n无\n\n **备注** \n\n- 更多返回错误代码请看首页的错误代码描述\n\n\n', 99, 1448591022),
(12, 1, 'showdoc', 2, 1, '城市数据', '**简要描述：** \n\n- 获取某个省份的城市数据\n\n**请求URL：** \n- ` http://xx.com/api/geograph/citys `\n  \n**请求方式：**\n- GET \n\n**参数：** \n\n|参数名|必选|类型|说明|\n|:----    |:---|:----- |-----   |\n|code |是  |string |省份代码   |\n\n **返回示例**\n``` \n{\n    &quot;error_code&quot;: 0,\n    &quot;data&quot;: [\n        {\n            &quot;id&quot;: &quot;28241&quot;,\n            &quot;code&quot;: &quot;4401&quot;,\n            &quot;parentid&quot;: &quot;44&quot;,\n            &quot;name&quot;: &quot;广州市&quot;,\n            &quot;level&quot;: &quot;2&quot;\n        },\n        {\n            &quot;id&quot;: &quot;28421&quot;,\n            &quot;code&quot;: &quot;4402&quot;,\n            &quot;parentid&quot;: &quot;44&quot;,\n            &quot;name&quot;: &quot;韶关市&quot;,\n            &quot;level&quot;: &quot;2&quot;\n        },\n        {\n            &quot;id&quot;: &quot;28558&quot;,\n            &quot;code&quot;: &quot;4403&quot;,\n            &quot;parentid&quot;: &quot;44&quot;,\n            &quot;name&quot;: &quot;深圳市&quot;,\n            &quot;level&quot;: &quot;2&quot;\n        },\n       ....\n    ]\n}\n```\n **返回参数说明** \n无\n\n **备注** \n\n- 更多返回错误代码请看首页的错误代码描述\n\n', 99, 1448549196),
(14, 1, 'showdoc', 3, 0, '帮助教程', '###ShowDoc是什么\r\n\r\n每当接手一个他人开发好的模块或者项目，看着那些没有写注释的代码，我们都无比抓狂。文档呢？！文档呢？！**Show me the doc  ！！**  \r\n \r\n程序员都很希望别人能写技术文档，而自己却很不希望要写文档。因为写文档需要花大量的时间去处理格式排版，想着新建的word文档放在哪个目录等各种非技术细节。\r\n\r\nword文档零零散散地放在团队不同人那里，需要文档的人基本靠吼，吼一声然后上qq或者邮箱接收对方丢过来的文档。这种沟通方式当然可以，只是效率不高。  \r\n \r\nShowDoc就是一个非常适合IT团队的在线文档分享工具，它可以加快团队之间沟通的效率。  \r\n\r\n###它可以用来做什么\r\n\r\n- #### API文档（ [查看Demo](http://doc.star7th.com/2)）\r\n\r\n	随着移动互联网的发展，BaaS（后端即服务）越来越流行。服务端提供API，APP端或者网页前端便可方便调用数据。用ShowDoc可以非常方便快速地编写出美观的API文档。\r\n\r\n- #### 数据字典（ [查看Demo](http://doc.star7th.com/1)）\r\n\r\n	一份好的数据字典可以很方便地向别人说明你的数据库结构，如各个字段的释义等。\r\n\r\n- #### 说明文档\r\n\r\n	你完全可以使用showdoc来编写一些工具的说明书。例如你正在看的教程说明便是用showdoc编辑的。你也可以编写一些技术规范说明文档以供团队查阅\r\n\r\n###它都有些什么功能\r\n\r\n	\r\n- ####分享与导出\r\n\r\n	- 响应式网页设计，可将项目文档分享到电脑或移动设备查看。同时也可以将项目导出成word文件，以便离线浏览。\r\n\r\n- ####权限管理\r\n	- 公开项目与私密项目\r\n		\r\n		ShowDoc上的项目有公开项目和私密项目两种。公开项目可供任何登录与非登录的用户访问，而私密项目则需要输入密码验证访问。密码由项目创建者设置。\r\n	\r\n	- 项目转让\r\n		\r\n		项目创建者可以自由地把项目转让给网站的其他用户。\r\n		\r\n	- 项目成员\r\n		\r\n		你可以很方便地为ShowDoc的项目添加、删除项目成员。项目成员可以对项目进行编辑，但不可转让或删除项目（只有项目创建者才有权限）\r\n- ####编辑功能\r\n	- markdown编辑\r\n		\r\n		ShowDoc采用markdown编辑器，无论是编辑还是阅读体验都极佳很棒。如果你不了解Markdown，请在搜索引擎搜索&quot;认识与入门 Markdown&quot;\r\n	\r\n	- 模板插入\r\n	\r\n		在ShowDoc的编辑页面，点击编辑器上方的按钮可方便地插入API接口模板和数据字典模板。插入模板后，剩下的就是改动数据了，省去了很多编辑的力气。 \r\n	\r\n	- 历史版本\r\n	\r\n		ShowDoc为页面提供历史版本功能，你可以方便地把页面恢复到之前的版本。\r\n\r\n\r\n###部署到自己的服务器\r\n\r\n- 克隆或者下载代码：\r\n\r\n	[https://github.com/star7th/showdoc](https://github.com/star7th/showdoc;)\r\n\r\n- 导入数据库\r\n\r\n	下载代码后，将跟目录的showdoc.sql文件导入mysql数据库\r\n\r\n- 修改配置文件\r\n\r\n	进入Application/Common/Conf/目录，编辑config.php文件，填写相应的数据库信息\r\n\r\n- 目录权限\r\n\r\n	请确保Application/Runtime 有可写权限\r\n\r\n###使用在线的ShowDoc\r\n\r\n- 如果你没有自己的服务器，但又想使用ShowDoc作为分档分享工具，你可以使用在线的ShowDoc   [http://doc.star7th.com](http://doc.star7th.com/index.php/home/user/login)\r\n\r\n###版权\r\n\r\n- ShowDoc遵循Apache2开源协议发布，并提供免费使用。  \r\n版权所有Copyright © 2015 by star7th  [http://blog.star7th.com](http://blog.star7th.com)  \r\nAll rights reserved.', 99, 1448633665),
(15, 1, 'showdoc', 2, 3, 'user', '-  用户表，储存用户信息\n\n|字段|类型|空|默认|注释|\n|:----    |:-------    |:--- |-- -|------      |\n|uid	  |int(10)     |否	|	 |	           |\n|username |varchar(20) |否	|    |	 用户名	|\n|groupid  |tinyint(2)   |否	|  2  |	 1为管理员，2为普通用户。此字段保留方便以后扩展	|\n|password |varchar(50) |否   |    |	 密码		 |\n|avatar |varchar(200) |是   |    |	 头像		 |\n|avatar_small |varchar(200) |是   |    |	 小头像	 |\n|email |varchar(50) |否   |    |	 邮箱		 |\n|name     |varchar(15) |是   |    |    昵称     |\n|reg_time |int(11)     |否   | 0  |   注册时间  |\n|last_login_time |int(11)     |否   | 0  |   最后一次登录时间  |\n\n- 备注：无\n\n', 99, 1448590754),
(16, 1, 'showdoc', 2, 3, 'page', '-  页面表，保存编辑的页面内容\n\n|字段|类型|空|默认|注释|\n|:----    |:-------    |:--- |-- -|------      |\n|page_id |int(10) |否	|  0  |	 页面自增id	|\n|author_uid |int(10) |否   |  0  |	 页面作者uid		 |\n|author_username     |varchar(50) |否   |    |    页面作者用户名     |\n|item_id |int(10)     |否   | 0  |   项目id  |\n|cat_id |int(10)     |否   | 0  |   父目录id  |\n|page_title |varchar(50)	    |否   |   |   页面标题  |\n|page_content  |text     |否   |   |   页面内容  |\n|order |int(10)     |否   | 99  |   顺序号。数字越小越靠前  |\n|addtime |int(11)     |否   | 0  |   该记录添加的时间。可认为是页面的修改时间  |\n\n- 备注：无\n\n\n', 99, 1448590719),
(17, 1, 'showdoc', 2, 3, 'item', '-  项目表，储存项目信息\n\n|字段|类型|空|默认|注释|\n|:----    |:-------    |:--- |-- -|------      |\n|item_id	  |int(10)     |否	|	 |	 项目id、自增id          |\n|item_name |varchar(50) |否	|    |	 项目名	|\n|item_description |varchar(225) |否   |    |	 项目描述		 |\n|uid     |int(10) |是   |    |    创建人uid     |\n|username |varchar(50)     |否   |   |   创建人用户名  |\n|username |varchar(50)     |否   |   |   创建人用户名  |\n|password |varchar(50)     |否   |   |   项目密码。可为空。空表示可以公开访问的项目  |\n|addtime |int(11)     |否   |   |   项目添加的时间，时间戳  |\n\n- 备注：无\n\n', 99, 1448590742);

-- --------------------------------------------------------

--
-- 表的结构 `page_history`
--

CREATE TABLE IF NOT EXISTS `page_history` (
  `page_history_id` int(10) NOT NULL AUTO_INCREMENT,
  `page_id` int(10) NOT NULL DEFAULT '0',
  `author_uid` int(10) NOT NULL DEFAULT '0' COMMENT '页面作者uid',
  `author_username` varchar(50) NOT NULL DEFAULT '' COMMENT '页面作者名字',
  `item_id` int(10) NOT NULL DEFAULT '0',
  `cat_id` int(10) NOT NULL DEFAULT '0',
  `page_title` varchar(50) NOT NULL DEFAULT '',
  `page_content` text NOT NULL,
  `order` int(10) NOT NULL DEFAULT '99' COMMENT '顺序号。数字越小越靠前。若此值全部为0则按时间排序',
  `addtime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`page_history_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='页面历史表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `uid` int(10) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `groupid` tinyint(2) NOT NULL DEFAULT '2' COMMENT '1为超级管理员，2为普通用户',
  `name` varchar(15) CHARACTER SET utf8 DEFAULT '',
  `avatar` varchar(200) CHARACTER SET utf8 DEFAULT '' COMMENT '头像',
  `avatar_small` varchar(200) DEFAULT '',
  `email` varchar(50) CHARACTER SET utf8 DEFAULT '',
  `password` varchar(50) CHARACTER SET utf8 NOT NULL,
  `cookie_token` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '实现cookie自动登录的token凭证',
  `cookie_token_expire` int(11) NOT NULL DEFAULT '0',
  `reg_time` int(11) NOT NULL DEFAULT '0',
  `last_login_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `username` (`username`) USING BTREE
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='用户表' AUTO_INCREMENT=2 ;

--
-- 转存表中的数据 `user`
--

INSERT INTO `user` (`uid`, `username`, `groupid`, `name`, `avatar`, `avatar_small`, `email`, `password`, `cookie_token`, `cookie_token_expire`, `reg_time`, `last_login_time`) VALUES
(1, 'showdoc', 2, '', '', '', '', 'a89da13684490eb9ec9e613f91d24d00', '4669e2ea0d3613c6107dfa59d87842b8', 1451187510, 1448457804, 1448595510);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
