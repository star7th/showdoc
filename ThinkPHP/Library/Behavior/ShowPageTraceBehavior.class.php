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
use Think\Log;
/**
 * 系统行为扩展：页面Trace显示输出
 */
class ShowPageTraceBehavior {
    protected $tracePageTabs =  array('BASE'=>'基本','FILE'=>'文件','INFO'=>'流程','ERR|NOTIC'=>'错误','SQL'=>'SQL','DEBUG'=>'调试');

    // 行为扩展的执行入口必须是run
    public function run(&$params){
        if(!IS_AJAX && !IS_CLI && C('SHOW_PAGE_TRACE')) {
            echo $this->showTrace();
        }
    }

    /**
     * 显示页面Trace信息
     * @access private
     */
    private function showTrace() {
         // 系统默认显示信息
        $files  =  get_included_files();
        $info   =   array();
        foreach ($files as $key=>$file){
            $info[] = $file.' ( '.number_format(filesize($file)/1024,2).' KB )';
        }
        $trace  =   array();
        $base   =   array(
            '请求信息'  =>  date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']).' '.$_SERVER['SERVER_PROTOCOL'].' '.$_SERVER['REQUEST_METHOD'].' : '.__SELF__,
            '运行时间'  =>  $this->showTime(),
            '吞吐率'    =>  number_format(1/G('beginTime','viewEndTime'),2).'req/s',
            '内存开销'  =>  MEMORY_LIMIT_ON?number_format((memory_get_usage() - $GLOBALS['_startUseMems'])/1024,2).' kb':'不支持',
            '查询信息'  =>  N('db_query').' queries '.N('db_write').' writes ',
            '文件加载'  =>  count(get_included_files()),
            '缓存信息'  =>  N('cache_read').' gets '.N('cache_write').' writes ',
            '配置加载'  =>  count(C()),
            '会话信息'  =>  'SESSION_ID='.session_id(),
            );
        // 读取应用定义的Trace文件
        $traceFile  =   COMMON_PATH.'Conf/trace.php';
        if(is_file($traceFile)) {
            $base   =   array_merge($base,include $traceFile);
        }
        $debug  =   trace();
        $tabs   =   C('TRACE_PAGE_TABS',null,$this->tracePageTabs);
        foreach ($tabs as $name=>$title){
            switch(strtoupper($name)) {
                case 'BASE':// 基本信息
                    $trace[$title]  =   $base;
                    break;
                case 'FILE': // 文件信息
                    $trace[$title]  =   $info;
                    break;
                default:// 调试信息
                    $name       =   strtoupper($name);
                    if(strpos($name,'|')) {// 多组信息
                        $names  =   explode('|',$name);
                        $result =   array();
                        foreach($names as $name){
                            $result   +=   isset($debug[$name])?$debug[$name]:array();
                        }
                        $trace[$title]  =   $result;
                    }else{
                        $trace[$title]  =   isset($debug[$name])?$debug[$name]:'';
                    }
            }
        }
        if($save = C('PAGE_TRACE_SAVE')) { // 保存页面Trace日志
            if(is_array($save)) {// 选择选项卡保存
                $tabs   =   C('TRACE_PAGE_TABS',null,$this->tracePageTabs);
                $array  =   array();
                foreach ($save as $tab){
                    $array[] =   $tabs[$tab];
                }
            }
            $content    =   date('[ c ]').' '.get_client_ip().' '.$_SERVER['REQUEST_URI']."\r\n";
            foreach ($trace as $key=>$val){
                if(!isset($array) || in_array_case($key,$array)) {
                    $content    .=  '[ '.$key." ]\r\n";
                    if(is_array($val)) {
                        foreach ($val as $k=>$v){
                            $content .= (!is_numeric($k)?$k.':':'').print_r($v,true)."\r\n";
                        }
                    }else{
                        $content .= print_r($val,true)."\r\n";
                    }
                    $content .= "\r\n";
                }
            }
            error_log(str_replace('<br/>',"\r\n",$content), 3,C('LOG_PATH').date('y_m_d').'_trace.log');
        }
        unset($files,$info,$base);
        // 调用Trace页面模板
        ob_start();
        include C('TMPL_TRACE_FILE')?C('TMPL_TRACE_FILE'):THINK_PATH.'Tpl/page_trace.tpl';
        return ob_get_clean();
    }

    /**
     * 获取运行时间
     */
    private function showTime() {
        // 显示运行时间
        G('beginTime',$GLOBALS['_beginTime']);
        G('viewEndTime');
        // 显示详细运行时间
        return G('beginTime','viewEndTime').'s ( Load:'.G('beginTime','loadTime').'s Init:'.G('loadTime','initTime').'s Exec:'.G('initTime','viewStartTime').'s Template:'.G('viewStartTime','viewEndTime').'s )';
    }
}
