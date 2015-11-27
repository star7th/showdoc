<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Cache\Driver;
use Think\Cache;
defined('THINK_PATH') or exit();
/**
 * Sqlite缓存驱动
 */
class Sqlite extends Cache {

    /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options=array()) {
        if ( !extension_loaded('sqlite') ) {
            E(L('_NOT_SUPPORT_').':sqlite');
        }
        if(empty($options)) {
            $options = array (
                'db'        =>  ':memory:',
                'table'     =>  'sharedmemory',
            );
        }
        $this->options  =   $options;      
        $this->options['prefix']    =   isset($options['prefix'])?  $options['prefix']  :   C('DATA_CACHE_PREFIX');
        $this->options['length']    =   isset($options['length'])?  $options['length']  :   0;        
        $this->options['expire']    =   isset($options['expire'])?  $options['expire']  :   C('DATA_CACHE_TIME');
        
        $func = $this->options['persistent'] ? 'sqlite_popen' : 'sqlite_open';
        $this->handler      = $func($this->options['db']);
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name) {
        N('cache_read',1);
		$name   = $this->options['prefix'].sqlite_escape_string($name);
        $sql    = 'SELECT value FROM '.$this->options['table'].' WHERE var=\''.$name.'\' AND (expire=0 OR expire >'.time().') LIMIT 1';
        $result = sqlite_query($this->handler, $sql);
        if (sqlite_num_rows($result)) {
            $content   =  sqlite_fetch_single($result);
            if(C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
                //启用数据压缩
                $content   =   gzuncompress($content);
            }
            return unserialize($content);
        }
        return false;
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value,$expire=null) {
        N('cache_write',1);
        $name  = $this->options['prefix'].sqlite_escape_string($name);
        $value = sqlite_escape_string(serialize($value));
        if(is_null($expire)) {
            $expire  =  $this->options['expire'];
        }
        $expire	=	($expire==0)?0: (time()+$expire) ;//缓存有效期为0表示永久缓存
        if( C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
            //数据压缩
            $value   =   gzcompress($value,3);
        }
        $sql  = 'REPLACE INTO '.$this->options['table'].' (var, value,expire) VALUES (\''.$name.'\', \''.$value.'\', \''.$expire.'\')';
        if(sqlite_query($this->handler, $sql)){
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
    public function rm($name) {
        $name  = $this->options['prefix'].sqlite_escape_string($name);
        $sql  = 'DELETE FROM '.$this->options['table'].' WHERE var=\''.$name.'\'';
        sqlite_query($this->handler, $sql);
        return true;
    }

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clear() {
        $sql  = 'DELETE FROM '.$this->options['table'];
        sqlite_query($this->handler, $sql);
        return ;
    }
}
