<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace Think;
class Upload {
    /**
     * 默认上传配置
     * @var array
     */
    private $config = array(
        'mimes'         =>  array(), //允许上传的文件MiMe类型
        'maxSize'       =>  0, //上传的文件大小限制 (0-不做限制)
        'exts'          =>  array(), //允许上传的文件后缀
        'autoSub'       =>  true, //自动子目录保存文件
        'subName'       =>  array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
        'rootPath'      =>  './Uploads/', //保存根路径
        'savePath'      =>  '', //保存路径
        'saveName'      =>  array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
        'saveExt'       =>  '', //文件保存后缀，空则使用原后缀
        'replace'       =>  false, //存在同名是否覆盖
        'hash'          =>  true, //是否生成hash编码
        'callback'      =>  false, //检测文件是否存在回调，如果存在返回文件信息数组
        'driver'        =>  '', // 文件上传驱动
        'driverConfig'  =>  array(), // 上传驱动配置
    );

    /**
     * 上传错误信息
     * @var string
     */
    private $error = ''; //上传错误信息

    /**
     * 上传驱动实例
     * @var Object
     */
    private $uploader;

    /**
     * 构造方法，用于构造上传实例
     * @param array  $config 配置
     * @param string $driver 要使用的上传驱动 LOCAL-本地上传驱动，FTP-FTP上传驱动
     */
    public function __construct($config = array(), $driver = '', $driverConfig = null){
        /* 获取配置 */
        $this->config   =   array_merge($this->config, $config);

        /* 设置上传驱动 */
        $this->setDriver($driver, $driverConfig);

        /* 调整配置，把字符串配置参数转换为数组 */
        if(!empty($this->config['mimes'])){
            if(is_string($this->mimes)) {
                $this->config['mimes'] = explode(',', $this->mimes);
            }
            $this->config['mimes'] = array_map('strtolower', $this->mimes);
        }
        if(!empty($this->config['exts'])){
            if (is_string($this->exts)){
                $this->config['exts'] = explode(',', $this->exts);
            }
            $this->config['exts'] = array_map('strtolower', $this->exts);
        }
    }

    /**
     * 使用 $this->name 获取配置
     * @param  string $name 配置名称
     * @return multitype    配置值
     */
    public function __get($name) {
        return $this->config[$name];
    }

    public function __set($name,$value){
        if(isset($this->config[$name])) {
            $this->config[$name] = $value;
            if($name == 'driverConfig'){
                //改变驱动配置后重置上传驱动
                //注意：必须选改变驱动然后再改变驱动配置
                $this->setDriver(); 
            }
        }
    }

    public function __isset($name){
        return isset($this->config[$name]);
    }

    /**
     * 获取最后一次上传错误信息
     * @return string 错误信息
     */
    public function getError(){
        return $this->error;
    }

    /**
     * 上传单个文件
     * @param  array  $file 文件数组
     * @return array        上传成功后的文件信息
     */
    public function uploadOne($file){
        $info = $this->upload(array($file));
        return $info ? $info[0] : $info;
    }

    /**
     * 上传文件
     * @param 文件信息数组 $files ，通常是 $_FILES数组
     */
    public function upload($files='') {
        if('' === $files){
            $files  =   $_FILES;
        }
        if(empty($files)){
            $this->error = '没有上传的文件！';
            return false;
        }

        /* 检测上传根目录 */
        if(!$this->uploader->checkRootPath($this->rootPath)){
            $this->error = $this->uploader->getError();
            return false;
        }

        /* 检查上传目录 */
        if(!$this->uploader->checkSavePath($this->savePath)){
            $this->error = $this->uploader->getError();
            return false;
        }

        /* 逐个检测并上传文件 */
        $info    =  array();
        if(function_exists('finfo_open')){
            $finfo   =  finfo_open ( FILEINFO_MIME_TYPE );
        }
        // 对上传文件数组信息处理
        $files   =  $this->dealFiles($files);    
        foreach ($files as $key => $file) {
            $file['name']  = strip_tags($file['name']);
            if(!isset($file['key']))   $file['key']    =   $key;
            /* 通过扩展获取文件类型，可解决FLASH上传$FILES数组返回文件类型错误的问题 */
            if(isset($finfo)){
                $file['type']   =   finfo_file ( $finfo ,  $file['tmp_name'] );
            }

            /* 获取上传文件后缀，允许上传无后缀文件 */
            $file['ext']    =   pathinfo($file['name'], PATHINFO_EXTENSION);

            /* 文件上传检测 */
            if (!$this->check($file)){
                continue;
            }

            /* 获取文件hash */
            if($this->hash){
                $file['md5']  = md5_file($file['tmp_name']);
                $file['sha1'] = sha1_file($file['tmp_name']);
            }

            /* 调用回调函数检测文件是否存在 */
            $data = call_user_func($this->callback, $file);
            if( $this->callback && $data ){
                if ( file_exists('.'.$data['path'])  ) {
                    $info[$key] = $data;
                    continue;
                }elseif($this->removeTrash){
                    call_user_func($this->removeTrash,$data);//删除垃圾据
                }
            }

            /* 生成保存文件名 */
            $savename = $this->getSaveName($file);
            if(false == $savename){
                continue;
            } else {
                $file['savename'] = $savename;
            }

            /* 检测并创建子目录 */
            $subpath = $this->getSubPath($file['name']);
            if(false === $subpath){
                continue;
            } else {
                $file['savepath'] = $this->savePath . $subpath;
            }

            /* 对图像文件进行严格检测 */
            $ext = strtolower($file['ext']);
            if(in_array($ext, array('gif','jpg','jpeg','bmp','png','swf'))) {
                $imginfo = getimagesize($file['tmp_name']);
                if(empty($imginfo) || ($ext == 'gif' && empty($imginfo['bits']))){
                    $this->error = '非法图像文件！';
                    continue;
                }
            }

            /* 保存文件 并记录保存成功的文件 */
            if ($this->uploader->save($file,$this->replace)) {
                unset($file['error'], $file['tmp_name']);
                $info[$key] = $file;
            } else {
                $this->error = $this->uploader->getError();
            }
        }
        if(isset($finfo)){
            finfo_close($finfo);
        }
        return empty($info) ? false : $info;
    }

    /**
     * 转换上传文件数组变量为正确的方式
     * @access private
     * @param array $files  上传的文件变量
     * @return array
     */
    private function dealFiles($files) {
        $fileArray  = array();
        $n          = 0;
        foreach ($files as $key=>$file){
            if(is_array($file['name'])) {
                $keys       =   array_keys($file);
                $count      =   count($file['name']);
                for ($i=0; $i<$count; $i++) {
                    $fileArray[$n]['key'] = $key;
                    foreach ($keys as $_key){
                        $fileArray[$n][$_key] = $file[$_key][$i];
                    }
                    $n++;
                }
            }else{
               $fileArray = $files;
               break;
            }
        }
       return $fileArray;
    }

    /**
     * 设置上传驱动
     * @param string $driver 驱动名称
     * @param array $config 驱动配置     
     */
    private function setDriver($driver = null, $config = null){
        $driver = $driver ? : ($this->driver       ? : C('FILE_UPLOAD_TYPE'));
        $config = $config ? : ($this->driverConfig ? : C('UPLOAD_TYPE_CONFIG'));
        $class = strpos($driver,'\\')? $driver : 'Think\\Upload\\Driver\\'.ucfirst(strtolower($driver));
        $this->uploader = new $class($config);
        if(!$this->uploader){
            E("不存在上传驱动：{$name}");
        }
    }

    /**
     * 检查上传的文件
     * @param array $file 文件信息
     */
    private function check($file) {
        /* 文件上传失败，捕获错误代码 */
        if ($file['error']) {
            $this->error($file['error']);
            return false;
        }

        /* 无效上传 */
        if (empty($file['name'])){
            $this->error = '未知上传错误！';
        }

        /* 检查是否合法上传 */
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->error = '非法上传文件！';
            return false;
        }

        /* 检查文件大小 */
        if (!$this->checkSize($file['size'])) {
            $this->error = '上传文件大小不符！';
            return false;
        }

        /* 检查文件Mime类型 */
        //TODO:FLASH上传的文件获取到的mime类型都为application/octet-stream
        if (!$this->checkMime($file['type'])) {
            $this->error = '上传文件MIME类型不允许！';
            return false;
        }

        /* 检查文件后缀 */
        if (!$this->checkExt($file['ext'])) {
            $this->error = '上传文件后缀不允许';
            return false;
        }

        /* 通过检测 */
        return true;
    }


    /**
     * 获取错误代码信息
     * @param string $errorNo  错误号
     */
    private function error($errorNo) {
        switch ($errorNo) {
            case 1:
                $this->error = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值！';
                break;
            case 2:
                $this->error = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值！';
                break;
            case 3:
                $this->error = '文件只有部分被上传！';
                break;
            case 4:
                $this->error = '没有文件被上传！';
                break;
            case 6:
                $this->error = '找不到临时文件夹！';
                break;
            case 7:
                $this->error = '文件写入失败！';
                break;
            default:
                $this->error = '未知上传错误！';
        }
    }

    /**
     * 检查文件大小是否合法
     * @param integer $size 数据
     */
    private function checkSize($size) {
        return !($size > $this->maxSize) || (0 == $this->maxSize);
    }

    /**
     * 检查上传的文件MIME类型是否合法
     * @param string $mime 数据
     */
    private function checkMime($mime) {
        return empty($this->config['mimes']) ? true : in_array(strtolower($mime), $this->mimes);
    }

    /**
     * 检查上传的文件后缀是否合法
     * @param string $ext 后缀
     */
    private function checkExt($ext) {
        return empty($this->config['exts']) ? true : in_array(strtolower($ext), $this->exts);
    }

    /**
     * 根据上传文件命名规则取得保存文件名
     * @param string $file 文件信息
     */
    private function getSaveName($file) {
        $rule = $this->saveName;
        if (empty($rule)) { //保持文件名不变
            /* 解决pathinfo中文文件名BUG */
            $filename = substr(pathinfo("_{$file['name']}", PATHINFO_FILENAME), 1);
            $savename = $filename;
        } else {
            $savename = $this->getName($rule, $file['name']);
            if(empty($savename)){
                $this->error = '文件命名规则错误！';
                return false;
            }
        }

        /* 文件保存后缀，支持强制更改文件后缀 */
        $ext = empty($this->config['saveExt']) ? $file['ext'] : $this->saveExt;

        return $savename . '.' . $ext;
    }

    /**
     * 获取子目录的名称
     * @param array $file  上传的文件信息
     */
    private function getSubPath($filename) {
        $subpath = '';
        $rule    = $this->subName;
        if ($this->autoSub && !empty($rule)) {
            $subpath = $this->getName($rule, $filename) . '/';

            if(!empty($subpath) && !$this->uploader->mkdir($this->savePath . $subpath)){
                $this->error = $this->uploader->getError();
                return false;
            }
        }
        return $subpath;
    }

    /**
     * 根据指定的规则获取文件或目录名称
     * @param  array  $rule     规则
     * @param  string $filename 原文件名
     * @return string           文件或目录名称
     */
    private function getName($rule, $filename){
        $name = '';
        if(is_array($rule)){ //数组规则
            $func     = $rule[0];
            $param    = (array)$rule[1];
            foreach ($param as &$value) {
               $value = str_replace('__FILE__', $filename, $value);
            }
            $name = call_user_func_array($func, $param);
        } elseif (is_string($rule)){ //字符串规则
            if(function_exists($rule)){
                $name = call_user_func($rule);
            } else {
                $name = $rule;
            }
        }
        return $name;
    }

}
