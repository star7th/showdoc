

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

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
  `s_number` int(10) NOT NULL DEFAULT '99' COMMENT '顺序号。数字越小越靠前。若此值全部相等时则按id排序',
  `addtime` int(11) NOT NULL DEFAULT '0',
  `parent_cat_id` int(10) NOT NULL DEFAULT '0' COMMENT '上一级目录的id',
  `level` int(10) NOT NULL DEFAULT '2' COMMENT '2为二级目录，3为三级目录',
  PRIMARY KEY (`cat_id`),
  KEY `order` (`s_number`),
  KEY `addtime` (`addtime`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='目录表' AUTO_INCREMENT=1 ;

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
  `last_update_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后更新时间',
  PRIMARY KEY (`item_id`),
  KEY `addtime` (`addtime`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='项目表' AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='项目成员表' AUTO_INCREMENT=1 ;

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
  `s_number` int(10) NOT NULL DEFAULT '99' COMMENT '顺序号。数字越小越靠前。若此值全部相等时则按id排序',
  `addtime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`page_id`),
  KEY `order` (`s_number`),
  KEY `addtime` (`addtime`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='文章页面表' AUTO_INCREMENT=1 ;

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
  `s_number` int(10) NOT NULL DEFAULT '99' COMMENT '顺序号。数字越小越靠前。若此值全部相等时则按id排序',
  `addtime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`page_history_id`),
  KEY `addtime` (`addtime`),
  KEY `page_id` (`page_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='页面历史表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `uid` int(10) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT '',
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='用户表' AUTO_INCREMENT=1 ;
