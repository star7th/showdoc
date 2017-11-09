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
namespace Think\Cache\Driver;
use Think\Cache;

defined('THINK_PATH') or exit();
/**
 * Memcache缓存驱动
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Cache
 * @author    liu21st <liu21st@gmail.com>
 */
class Memcachesae extends Cache {

    /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     */
    function __construct($options=array()) {
        $options = array_merge(array (
            'host'        =>  C('MEMCACHE_HOST') ? : '127.0.0.1',
            'port'        =>  C('MEMCACHE_PORT') ? : 11211,
            'timeout'     =>  C('DATA_CACHE_TIMEOUT') ? : false,
            'persistent'  =>  false,
        ),$options);

        $this->options      =   $options;
        $this->options['expire'] =  isset($options['expire'])?  $options['expire']  :   C('DATA_CACHE_TIME');
        $this->options['prefix'] =  isset($options['prefix'])?  $options['prefix']  :   C('DATA_CACHE_PREFIX');
        $this->options['length'] =  isset($options['length'])?  $options['length']  :   0;
         $this->handler      =  memcache_init();//[sae] 下实例化
        //[sae] 下不用链接
        $this->connected=true;
    }

    /**
     * 是否连接
     * @access private
     * @return boolean
     */
    private function isConnected() {
        return $this->connected;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name) {
        N('cache_read',1);
        return $this->handler->get($_SERVER['HTTP_APPVERSION'].'/'.$this->options['prefix'].$name);
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null) {
        N('cache_write',1);
        if(is_null($expire)) {
            $expire  =  $this->options['expire'];
        }
        $name   =   $this->options['prefix'].$name;
        if($this->handler->set($_SERVER['HTTP_APPVERSION'].'/'.$name, $value, 0, $expire)) {
            if($this->options['length']>0) {
                // 记录缓存队列
                $this->queue($name);
            }
            return true;
        }
        return false;
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm($name, $ttl = false) {
        $name   =   $_SERVER['HTTP_APPVERSION'].'/'.$this->options['prefix'].$name;
        return $ttl === false ?
            $this->handler->delete($name) :
            $this->handler->delete($name, $ttl);
    }

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clear() {
        return $this->handler->flush();
    }

    /**
     * 队列缓存
     * @access protected
     * @param string $key 队列名
     * @return mixed
     */
    //[sae] 下重写queque队列缓存方法
    protected function queue($key) {
        $queue_name=isset($this->options['queue_name'])?$this->options['queue_name']:'think_queue';
        $value  =  F($queue_name);
        if(!$value) {
            $value   =  array();
        }
        // 进列
        if(false===array_search($key, $value)) array_push($value,$key);
        if(count($value) > $this->options['length']) {
            // 出列
            $key =  array_shift($value);
            // 删除缓存
            $this->rm($key);
            if (APP_DEBUG) {
                    //调试模式下记录出队次数
                        $counter = Think::instance('SaeCounter');
                        if ($counter->exists($queue_name.'_out_times'))
                            $counter->incr($queue_name.'_out_times');
                        else
                            $counter->create($queue_name.'_out_times', 1);
           }
        }
        return F($queue_name,$value);
    }

}
