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
namespace Think;
/**
 * 用于ThinkPHP的自动生成
 */
class Build {

    static protected $controller   =   '<?php
namespace [MODULE]\Controller;
use Think\Controller;
class [CONTROLLER]Controller extends Controller {
    public function index(){
        $this->show(\'<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px } a,a:hover{color:blue;}</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>版本 V{$Think.version}</div><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_55e75dfae343f5a1"></thinkad><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script>\',\'utf-8\');
    }
}';

    static protected $model         =   '<?php
namespace [MODULE]\Model;
use Think\Model;
class [MODEL]Model extends Model {

}';
    // 检测应用目录是否需要自动创建
    static public function checkDir($module){
        if(!is_dir(APP_PATH.$module)) {
            // 创建模块的目录结构
            self::buildAppDir($module);
        }elseif(!is_dir(LOG_PATH)){
            // 检查缓存目录
            self::buildRuntime();
        }
    }

    // 创建应用和模块的目录结构
    static public function buildAppDir($module) {
        // 没有创建的话自动创建
        if(!is_dir(APP_PATH)) mkdir(APP_PATH,0755,true);
        if(is_writeable(APP_PATH)) {
            $dirs  = array(
                COMMON_PATH,
                COMMON_PATH.'Common/',
                CONF_PATH,
                APP_PATH.$module.'/',
                APP_PATH.$module.'/Common/',
                APP_PATH.$module.'/Controller/',
                APP_PATH.$module.'/Model/',
                APP_PATH.$module.'/Conf/',
                APP_PATH.$module.'/View/',
                RUNTIME_PATH,
                CACHE_PATH,
                CACHE_PATH.$module.'/',
                LOG_PATH,
                LOG_PATH.$module.'/',
                TEMP_PATH,
                DATA_PATH,
                );
            foreach ($dirs as $dir){
                if(!is_dir($dir))  mkdir($dir,0755,true);
            }
            // 写入目录安全文件
            self::buildDirSecure($dirs);
            // 写入应用配置文件
            if(!is_file(CONF_PATH.'config'.CONF_EXT))
                file_put_contents(CONF_PATH.'config'.CONF_EXT,'.php' == CONF_EXT ? "<?php\nreturn array(\n\t//'配置项'=>'配置值'\n);":'');
            // 写入模块配置文件
            if(!is_file(APP_PATH.$module.'/Conf/config'.CONF_EXT))
                file_put_contents(APP_PATH.$module.'/Conf/config'.CONF_EXT,'.php' == CONF_EXT ? "<?php\nreturn array(\n\t//'配置项'=>'配置值'\n);":'');
            // 生成模块的测试控制器
            if(defined('BUILD_CONTROLLER_LIST')){
                // 自动生成的控制器列表（注意大小写）
                $list = explode(',',BUILD_CONTROLLER_LIST);
                foreach($list as $controller){
                    self::buildController($module,$controller);
                }
            }else{
                // 生成默认的控制器
                self::buildController($module);
            }
            // 生成模块的模型
            if(defined('BUILD_MODEL_LIST')){
                // 自动生成的控制器列表（注意大小写）
                $list = explode(',',BUILD_MODEL_LIST);
                foreach($list as $model){
                    self::buildModel($module,$model);
                }
            }            
        }else{
            header('Content-Type:text/html; charset=utf-8');
            exit('应用目录['.APP_PATH.']不可写，目录无法自动生成！<BR>请手动生成项目目录~');
        }
    }

    // 检查缓存目录(Runtime) 如果不存在则自动创建
    static public function buildRuntime() {
        if(!is_dir(RUNTIME_PATH)) {
            mkdir(RUNTIME_PATH);
        }elseif(!is_writeable(RUNTIME_PATH)) {
            header('Content-Type:text/html; charset=utf-8');
            exit('目录 [ '.RUNTIME_PATH.' ] 不可写！');
        }
        mkdir(CACHE_PATH);  // 模板缓存目录
        if(!is_dir(LOG_PATH))   mkdir(LOG_PATH);    // 日志目录
        if(!is_dir(TEMP_PATH))  mkdir(TEMP_PATH);   // 数据缓存目录
        if(!is_dir(DATA_PATH))  mkdir(DATA_PATH);   // 数据文件目录
        return true;
    }

    // 创建控制器类
    static public function buildController($module,$controller='Index') {
        $file   =   APP_PATH.$module.'/Controller/'.$controller.'Controller'.EXT;
        if(!is_file($file)){
            $content = str_replace(array('[MODULE]','[CONTROLLER]'),array($module,$controller),self::$controller);
            if(!C('APP_USE_NAMESPACE')){
                $content    =   preg_replace('/namespace\s(.*?);/','',$content,1);
            }
            $dir = dirname($file);
            if(!is_dir($dir)){
                mkdir($dir, 0755, true);
            }
            file_put_contents($file,$content);
        }
    }

    // 创建模型类
    static public function buildModel($module,$model) {
        $file   =   APP_PATH.$module.'/Model/'.$model.'Model'.EXT;
        if(!is_file($file)){
            $content = str_replace(array('[MODULE]','[MODEL]'),array($module,$model),self::$model);
            if(!C('APP_USE_NAMESPACE')){
                $content    =   preg_replace('/namespace\s(.*?);/','',$content,1);
            }
            $dir = dirname($file);
            if(!is_dir($dir)){
                mkdir($dir, 0755, true);
            }
            file_put_contents($file,$content);
        }
    }

    // 生成目录安全文件
    static public function buildDirSecure($dirs=array()) {
        // 目录安全写入（默认开启）
        defined('BUILD_DIR_SECURE')  or define('BUILD_DIR_SECURE',    true);
        if(BUILD_DIR_SECURE) {
            defined('DIR_SECURE_FILENAME')  or define('DIR_SECURE_FILENAME',    'index.html');
            defined('DIR_SECURE_CONTENT')   or define('DIR_SECURE_CONTENT',     ' ');
            // 自动写入目录安全文件
            $content = DIR_SECURE_CONTENT;
            $files = explode(',', DIR_SECURE_FILENAME);
            foreach ($files as $filename){
                foreach ($dirs as $dir)
                    file_put_contents($dir.$filename,$content);
            }
        }
    }
}
