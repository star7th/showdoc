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
namespace Behavior;
use Think\Storage;
use Think\Think;
/**
 * 系统行为扩展：模板解析
 */
class ParseTemplateBehavior {

    // 行为扩展的执行入口必须是run
    public function run(&$_data){
        $engine             =   strtolower(C('TMPL_ENGINE_TYPE'));
        $_content           =   empty($_data['content'])?$_data['file']:$_data['content'];
        $_data['prefix']    =   !empty($_data['prefix'])?$_data['prefix']:C('TMPL_CACHE_PREFIX');
        if('think'==$engine){ // 采用Think模板引擎
            if((!empty($_data['content']) && $this->checkContentCache($_data['content'],$_data['prefix'])) 
                ||  $this->checkCache($_data['file'],$_data['prefix'])) { // 缓存有效
                //载入模版缓存文件
                Storage::load(C('CACHE_PATH').$_data['prefix'].md5($_content).C('TMPL_CACHFILE_SUFFIX'),$_data['var']);
            }else{
                $tpl = Think::instance('Think\\Template');
                // 编译并加载模板文件
                $tpl->fetch($_content,$_data['var'],$_data['prefix']);
            }
        }else{
            // 调用第三方模板引擎解析和输出
            if(strpos($engine,'\\')){
                $class  =   $engine;
            }else{
                $class   =  'Think\\Template\\Driver\\'.ucwords($engine);                
            }            
            if(class_exists($class)) {
                $tpl   =  new $class;
                $tpl->fetch($_content,$_data['var']);
            }else {  // 类没有定义
                E(L('_NOT_SUPPORT_').': ' . $class);
            }
        }
    }

    /**
     * 检查缓存文件是否有效
     * 如果无效则需要重新编译
     * @access public
     * @param string $tmplTemplateFile  模板文件名
     * @return boolean
     */
    protected function checkCache($tmplTemplateFile,$prefix='') {
        if (!C('TMPL_CACHE_ON')) // 优先对配置设定检测
            return false;
        $tmplCacheFile = C('CACHE_PATH').$prefix.md5($tmplTemplateFile).C('TMPL_CACHFILE_SUFFIX');
        if(!Storage::has($tmplCacheFile)){
            return false;
        }elseif (filemtime($tmplTemplateFile) > Storage::get($tmplCacheFile,'mtime')) {
            // 模板文件如果有更新则缓存需要更新
            return false;
        }elseif (C('TMPL_CACHE_TIME') != 0 && time() > Storage::get($tmplCacheFile,'mtime')+C('TMPL_CACHE_TIME')) {
            // 缓存是否在有效期
            return false;
        }
        // 开启布局模板
        if(C('LAYOUT_ON')) {
            $layoutFile  =  THEME_PATH.C('LAYOUT_NAME').C('TMPL_TEMPLATE_SUFFIX');
            if(filemtime($layoutFile) > Storage::get($tmplCacheFile,'mtime')) {
                return false;
            }
        }
        // 缓存有效
        return true;
    }

    /**
     * 检查缓存内容是否有效
     * 如果无效则需要重新编译
     * @access public
     * @param string $tmplContent  模板内容
     * @return boolean
     */
    protected function checkContentCache($tmplContent,$prefix='') {
        if(Storage::has(C('CACHE_PATH').$prefix.md5($tmplContent).C('TMPL_CACHFILE_SUFFIX'))){
            return true;
        }else{
            return false;
        }
    }    
}
