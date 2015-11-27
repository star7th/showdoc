<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Behavior;
/**
 * 语言检测 并自动加载语言包
 */
class CheckLangBehavior {

    // 行为扩展的执行入口必须是run
    public function run(&$params){
        // 检测语言
        $this->checkLanguage();
    }

    /**
     * 语言检查
     * 检查浏览器支持语言，并自动加载语言包
     * @access private
     * @return void
     */
    private function checkLanguage() {
        // 不开启语言包功能，仅仅加载框架语言文件直接返回
        if (!C('LANG_SWITCH_ON',null,false)){
            return;
        }
        $langSet = C('DEFAULT_LANG');
        $varLang =  C('VAR_LANGUAGE',null,'l');
        $langList = C('LANG_LIST',null,'zh-cn');
        // 启用了语言包功能
        // 根据是否启用自动侦测设置获取语言选择
        if (C('LANG_AUTO_DETECT',null,true)){
            if(isset($_GET[$varLang])){
                $langSet = $_GET[$varLang];// url中设置了语言变量
                cookie('think_language',$langSet,3600);
            }elseif(cookie('think_language')){// 获取上次用户的选择
                $langSet = cookie('think_language');
            }elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){// 自动侦测浏览器语言
                preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
                $langSet = $matches[1];
                cookie('think_language',$langSet,3600);
            }
            if(false === stripos($langList,$langSet)) { // 非法语言参数
                $langSet = C('DEFAULT_LANG');
            }
        }
        // 定义当前语言
        define('LANG_SET',strtolower($langSet));

        // 读取框架语言包
        $file   =   THINK_PATH.'Lang/'.LANG_SET.'.php';
        if(LANG_SET != C('DEFAULT_LANG') && is_file($file))
            L(include $file);

        // 读取应用公共语言包
        $file   =  LANG_PATH.LANG_SET.'.php';
        if(is_file($file))
            L(include $file);
        
        // 读取模块语言包
        $file   =   MODULE_PATH.'Lang/'.LANG_SET.'.php';
        if(is_file($file))
            L(include $file);

        // 读取当前控制器语言包
        $file   =   MODULE_PATH.'Lang/'.LANG_SET.'/'.strtolower(CONTROLLER_NAME).'.php';
        if (is_file($file))
            L(include $file);
    }
}
