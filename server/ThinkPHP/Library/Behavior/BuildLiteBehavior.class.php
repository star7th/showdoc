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
// 创建Lite运行文件
// 可以替换框架入口文件运行
// 建议绑定位置app_init
class BuildLiteBehavior {
    public function run(&$params) {
        if(!defined('BUILD_LITE_FILE')) return ;
        $litefile   =   C('RUNTIME_LITE_FILE',null,RUNTIME_PATH.'lite.php');
        if(is_file($litefile)) return;
        
        $defs       =   get_defined_constants(TRUE);
        $content    =   'namespace {$GLOBALS[\'_beginTime\'] = microtime(TRUE);';
        if(MEMORY_LIMIT_ON) {
            $content .= '$GLOBALS[\'_startUseMems\'] = memory_get_usage();';
        }

        // 生成数组定义
        unset($defs['user']['BUILD_LITE_FILE']);
        $content   .=   $this->buildArrayDefine($defs['user']).'}';

        // 读取编译列表文件
        $filelist   =   is_file(CONF_PATH.'lite.php')?
            include CONF_PATH.'lite.php':
            array(
                THINK_PATH.'Common/functions.php',
                COMMON_PATH.'Common/function.php',
                CORE_PATH . 'Think'.EXT,
                CORE_PATH . 'Hook'.EXT,
                CORE_PATH . 'App'.EXT,
                CORE_PATH . 'Dispatcher'.EXT,
                CORE_PATH . 'Log'.EXT,
                CORE_PATH . 'Log/Driver/File'.EXT,
                CORE_PATH . 'Route'.EXT,
                CORE_PATH . 'Controller'.EXT,
                CORE_PATH . 'View'.EXT,
                CORE_PATH . 'Storage'.EXT,
                CORE_PATH . 'Storage/Driver/File'.EXT,
                CORE_PATH . 'Exception'.EXT,
                BEHAVIOR_PATH . 'ParseTemplateBehavior'.EXT,
                BEHAVIOR_PATH . 'ContentReplaceBehavior'.EXT,
            );

        // 编译文件
        foreach ($filelist as $file){
          if(is_file($file)) {
            $content   .= compile($file);
          }
        }

        // 处理Think类的start方法
        $content  =  preg_replace('/\$runtimefile = RUNTIME_PATH(.+?)(if\(APP_STATUS)/','\2',$content,1);
        $content  .=  "\nnamespace { Think\Think::addMap(".var_export(\Think\Think::getMap(),true).");";
        $content  .=  "\nL(".var_export(L(),true).");\nC(".var_export(C(),true).');Think\Hook::import('.var_export(\Think\Hook::get(),true).');Think\Think::start();}';

        // 生成运行Lite文件
        file_put_contents($litefile,strip_whitespace('<?php '.$content));
    }

    // 根据数组生成常量定义
    private function buildArrayDefine($array) {
        $content = "\n";
        foreach ($array as $key => $val) {
            $key = strtoupper($key);
            $content .= 'defined(\'' . $key . '\') or ';
            if (is_int($val) || is_float($val)) {
                $content .= "define('" . $key . "'," . $val . ');';
            } elseif (is_bool($val)) {
                $val = ($val) ? 'true' : 'false';
                $content .= "define('" . $key . "'," . $val . ');';
            } elseif (is_string($val)) {
                $content .= "define('" . $key . "','" . addslashes($val) . "');";
            }
            $content    .= "\n";
        }
        return $content;
    }
}