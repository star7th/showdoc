<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Jay <yangweijiester@gmail.com> <http://code-tech.diandian.com>
// +----------------------------------------------------------------------
namespace Think\Upload\Driver;
use Think\Upload\Driver\Bcs\BaiduBcs;
class Bcs {
    /**
     * 上传文件根目录
     * @var string
     */
    private $rootPath;
    const DEFAULT_URL = 'bcs.duapp.com';

    /**
     * 上传错误信息
     * @var string
     */
    private $error = '';

    public $config = array(
    	'AccessKey'=> '',
        'SecretKey'=> '', //百度云服务器
        'bucket'   => '', //空间名称
        'rename'   => false,
        'timeout'  => 3600, //超时时间
    );

    public $bcs = null;

    /**
     * 构造函数，用于设置上传根路径
     * @param array  $config FTP配置
     */
    public function __construct($config){
        /* 默认FTP配置 */
        $this->config = array_merge($this->config, $config);
        
        $bcsClass = dirname(__FILE__). "/Bcs/bcs.class.php";
        if(is_file($bcsClass))
            require_once($bcsClass);
        $this->bcs = new BaiduBCS ( $this->config['AccessKey'], $this->config['SecretKey'], self:: DEFAULT_URL );
    }

    /**
     * 检测上传根目录(百度云上传时支持自动创建目录，直接返回)
     * @param string $rootpath   根目录
     * @return boolean true-检测通过，false-检测失败
     */
    public function checkRootPath($rootpath){
        /* 设置根目录 */
        $this->rootPath = str_replace('./', '/', $rootpath);
    	return true;
    }

    /**
     * 检测上传目录(百度云上传时支持自动创建目录，直接返回)
     * @param  string $savepath 上传目录
     * @return boolean          检测结果，true-通过，false-失败
     */
	public function checkSavePath($savepath){
		return true;
    }

    /**
     * 创建文件夹 (百度云上传时支持自动创建目录，直接返回)
     * @param  string $savepath 目录名称
     * @return boolean          true-创建成功，false-创建失败
     */
    public function mkdir($savepath){
    	return true;
    }

    /**
     * 保存指定文件
     * @param  array   $file    保存的文件信息
     * @param  boolean $replace 同名文件是否覆盖
     * @return boolean          保存状态，true-成功，false-失败
     */
    public function save(&$file,$replace=true) {
        $opt = array ();
        $opt ['acl'] = BaiduBCS::BCS_SDK_ACL_TYPE_PUBLIC_WRITE;
        $opt ['curlopts'] = array (
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 1800
        );
        $object = "/{$file['savepath']}{$file['savename']}";
        $response = $this->bcs->create_object ( $this->config['bucket'], $object, $file['tmp_name'], $opt );
        $url = $this->download($object);
        $file['url'] = $url;
        return $response->isOK() ? true : false;
    }

    public function download($file){
        $file = str_replace('./', '/', $file);
        $opt = array();
        $opt['time'] = mktime('2049-12-31'); //这是最长有效时间!--
        $response = $this->bcs->generate_get_object_url ( $this->config['bucket'], $file, $opt );
        return $response;
    }

    /**
     * 获取最后一次上传错误信息
     * @return string 错误信息
     */
    public function getError(){
        return $this->error;
    }

    /**
     * 请求百度云服务器
     * @param  string   $path    请求的PATH
     * @param  string   $method  请求方法
     * @param  array    $headers 请求header
     * @param  resource $body    上传文件资源
     * @return boolean
     */
    private function request($path, $method, $headers = null, $body = null){
        $ch  = curl_init($path);

        $_headers = array('Expect:');
        if (!is_null($headers) && is_array($headers)){
            foreach($headers as $k => $v) {
                array_push($_headers, "{$k}: {$v}");
            }
        }

        $length = 0;
        $date   = gmdate('D, d M Y H:i:s \G\M\T');

        if (!is_null($body)) {
            if(is_resource($body)){
                fseek($body, 0, SEEK_END);
                $length = ftell($body);
                fseek($body, 0);

                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_INFILE, $body);
                curl_setopt($ch, CURLOPT_INFILESIZE, $length);
            } else {
                $length = @strlen($body);
                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        } else {
            array_push($_headers, "Content-Length: {$length}");
        }

        // array_push($_headers, 'Authorization: ' . $this->sign($method, $uri, $date, $length));
        array_push($_headers, "Date: {$date}");

        curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'PUT' || $method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }

        if ($method == 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        list($header, $body) = explode("\r\n\r\n", $response, 2);

        if ($status == 200) {
            if ($method == 'GET') {
                return $body;
            } else {
                $data = $this->response($header);
                return count($data) > 0 ? $data : true;
            }
        } else {
            $this->error($header);
            return false;
        }
    }

    /**
     * 获取响应数据
     * @param  string $text 响应头字符串
     * @return array        响应数据列表
     */
    private function response($text){
        $items = json_decode($text, true);
        return $items;
    }

    /**
     * 生成请求签名
     * @return string          请求签名
     */
    private function sign($method, $Bucket, $object='/', $size=''){
        if(!$size)
            $size = $this->config['size'];
        $param = array(
            'ak'=>$this->config['AccessKey'],
            'sk'=>$this->config['SecretKey'],
            'size'=>$size,
            'bucket'=>$Bucket,
            'host'=>self :: DEFAULT_URL,
            'date'=>time()+$this->config['timeout'],
            'ip'=>'',
            'object'=>$object
        );
        $response = $this->request($this->apiurl.'?'.http_build_query($param), 'POST');
        if($response)
            $response = json_decode($response, true);
        return $response['content'][$method];
    }


    /**
     * 获取请求错误信息
     * @param  string $header 请求返回头信息
     */
    private function error($header) {
        list($status, $stash) = explode("\r\n", $header, 2);
        list($v, $code, $message) = explode(" ", $status, 3);
        $message = is_null($message) ? 'File Not Found' : "[{$status}]:{$message}";
        $this->error = $message;
    }

}
