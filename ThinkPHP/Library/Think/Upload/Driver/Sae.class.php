<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614<weibo.com/luofei614>
// +----------------------------------------------------------------------

namespace Think\Upload\Driver;
class Sae{
    /**
     * Storage的Domain
     * @var string
     */
    private $domain     =   '';

    private $rootPath   =   '';

    /**
     * 本地上传错误信息
     * @var string
     */
    private $error      =   ''; 

    /**
     * 构造函数，设置storage的domain， 如果有传配置，则domain为配置项，如果没有传domain为第一个路径的目录名称。 
     * @param mixed $config 上传配置     
     */
    public function __construct($config = null){
        if(is_array($config) && !empty($config['domain'])){
            $this->domain   =   strtolower($config['domain']);
        }
    }

    /**
     * 检测上传根目录
     * @param string $rootpath   根目录
     * @return boolean true-检测通过，false-检测失败
     */
    public function checkRootPath($rootpath){
        $rootpath = trim($rootpath,'./');
        if(!$this->domain){
            $rootpath = explode('/', $rootpath);
            $this->domain = strtolower(array_shift($rootpath));
            $rootpath = implode('/', $rootpath);
        }

        $this->rootPath =  $rootpath;
        $st =   new \SaeStorage();
        if(false===$st->getDomainCapacity($this->domain)){
          $this->error  =   '您好像没有建立Storage的domain['.$this->domain.']';
          return false;
        }
        return true;
    }

    /**
     * 检测上传目录
     * @param  string $savepath 上传目录
     * @return boolean          检测结果，true-通过，false-失败
     */
    public function checkSavePath($savepath){
        return true;
    }

    /**
     * 保存指定文件
     * @param  array   $file    保存的文件信息
     * @param  boolean $replace 同名文件是否覆盖
     * @return boolean          保存状态，true-成功，false-失败
     */
    public function save(&$file, $replace=true) {
        $filename = ltrim($this->rootPath .'/'. $file['savepath'] . $file['savename'],'/');
        $st =   new \SaeStorage();
        /* 不覆盖同名文件 */ 
        if (!$replace && $st->fileExists($this->domain,$filename)) {
            $this->error = '存在同名文件' . $file['savename'];
            return false;
        }

        /* 移动文件 */
        if (!$st->upload($this->domain,$filename,$file['tmp_name'])) {
            $this->error = '文件上传保存错误！['.$st->errno().']:'.$st->errmsg();
            return false;
        }else{
            $file['url'] = $st->getUrl($this->domain, $filename);
        }
        return true;
    }

    public function mkdir(){
        return true;
    }

    /**
     * 获取最后一次上传错误信息
     * @return string 错误信息
     */
    public function getError(){
        return $this->error;
    }

}
