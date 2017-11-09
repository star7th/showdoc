<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Template\Driver;
/**
 * Smarty模板引擎驱动 
 */
class Smarty {

    /**
     * 渲染模板输出
     * @access public
     * @param string $templateFile 模板文件名
     * @param array $var 模板变量
     * @return void
     */
    public function fetch($templateFile,$var) {
        $templateFile   =   substr($templateFile,strlen(THEME_PATH));
        vendor('Smarty.Smarty#class');
        $tpl            =   new \Smarty();
        $tpl->caching       = C('TMPL_CACHE_ON');
        $tpl->template_dir  = THEME_PATH;
        $tpl->compile_dir   = CACHE_PATH ;
        $tpl->cache_dir     = TEMP_PATH ;        
        if(C('TMPL_ENGINE_CONFIG')) {
            $config  =  C('TMPL_ENGINE_CONFIG');
            foreach ($config as $key=>$val){
                $tpl->{$key}   =  $val;
            }
        }
        $tpl->assign($var);
        $tpl->display($templateFile);
    }
}