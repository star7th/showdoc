<?php
	namespace Think\Upload\Driver\Qiniu;

	class QiniuStorage {

		public $QINIU_RSF_HOST = 'http://rsf.qbox.me';
		public $QINIU_RS_HOST = 'http://rs.qbox.me';
		public $QINIU_UP_HOST = 'http://up.qiniu.com';
		public $timeout = '';

		public function __construct($config){
			$this->sk = $config['secrectKey'];
			$this->ak = $config['accessKey'];
			$this->domain = $config['domain'];
			$this->bucket = $config['bucket'];
			$this->timeout = isset($config['timeout'])? $config['timeout'] : 3600;
		}

		static function sign($sk, $ak, $data){
			$sign = hash_hmac('sha1', $data, $sk, true);
			return $ak . ':' . self::Qiniu_Encode($sign);
		}

		static function signWithData($sk, $ak, $data){
			$data = self::Qiniu_Encode($data);
			return self::sign($sk, $ak, $data) . ':' . $data;
		}

		public function accessToken($url, $body=''){
			$parsed_url = parse_url($url);
		    $path = $parsed_url['path'];
		    $access = $path;
		    if (isset($parsed_url['query'])) {
		        $access .= "?" . $parsed_url['query'];
		    }
		    $access .= "\n";

		    if($body){
		        $access .= $body;
		    }
		    return self::sign($this->sk, $this->ak, $access);
		}

		public function UploadToken($sk ,$ak ,$param){
			$param['deadline'] = $param['Expires'] == 0? 3600: $param['Expires'];
			$param['deadline'] += time();
			$data = array('scope'=> $this->bucket, 'deadline'=>$param['deadline']);
			if (!empty($param['CallbackUrl'])) {
				$data['callbackUrl'] = $param['CallbackUrl'];
			}
			if (!empty($param['CallbackBody'])) {
				$data['callbackBody'] = $param['CallbackBody'];
			}
			if (!empty($param['ReturnUrl'])) {
				$data['returnUrl'] = $param['ReturnUrl'];
			}
			if (!empty($param['ReturnBody'])) {
				$data['returnBody'] = $param['ReturnBody'];
			}
			if (!empty($param['AsyncOps'])) {
				$data['asyncOps'] = $param['AsyncOps'];
			}
			if (!empty($param['EndUser'])) {
				$data['endUser'] = $param['EndUser'];
			}
			$data = json_encode($data);
			return self::SignWithData($sk, $ak, $data);
		}

		public function upload($config, $file){
			$uploadToken = $this->UploadToken($this->sk, $this->ak, $config);

			$url = "{$this->QINIU_UP_HOST}";
			$mimeBoundary = md5(microtime());
			$header = array('Content-Type'=>'multipart/form-data;boundary='.$mimeBoundary);
			$data = array();

			$fields = array(
				'token'=>$uploadToken,
				'key'=>$config['saveName']? $config['save_name'] : $file['fileName'],
			);

			if(is_array($config['custom_fields']) && $config['custom_fields'] !== array()){
				$fields = array_merge($fields, $config['custom_fields']);
			}

			foreach ($fields as $name => $val) {
				array_push($data, '--' . $mimeBoundary);
				array_push($data, "Content-Disposition: form-data; name=\"$name\"");
				array_push($data, '');
				array_push($data, $val);
			}

			//文件
			array_push($data, '--' . $mimeBoundary);
			$name = $file['name'];
			$fileName = $file['fileName'];
			$fileBody = $file['fileBody'];
			$fileName = self::Qiniu_escapeQuotes($fileName);
			array_push($data, "Content-Disposition: form-data; name=\"$name\"; filename=\"$fileName\"");
			array_push($data, 'Content-Type: application/octet-stream');
			array_push($data, '');
			array_push($data, $fileBody);

			array_push($data, '--' . $mimeBoundary . '--');
			array_push($data, '');

			$body = implode("\r\n", $data);
			$response = $this->request($url, 'POST', $header, $body);
			return $response;
		}

		public function dealWithType($key, $type){
			$param = $this->buildUrlParam();
			$url = '';

			switch($type){
				case 'img':
					$url = $this->downLink($key);
					if($param['imageInfo']){
						$url .= '?imageInfo';
					}else if($param['exif']){
						$url .= '?exif';
					}else if($param['imageView']){
						$url .= '?imageView/'.$param['mode'];
						if($param['w'])
							$url .= "/w/{$param['w']}";
						if($param['h'])
							$url .= "/h/{$param['h']}";
						if($param['q'])
							$url .= "/q/{$param['q']}";
						if($param['format'])
							$url .= "/format/{$param['format']}";
					}
					break;
				case 'video': //TODO 视频处理
				case 'doc':
					$url = $this->downLink($key);
					$url .= '?md2html';
					if(isset($param['mode']))
						$url .= '/'.(int)$param['mode'];
					if($param['cssurl'])
						$url .= '/'. self::Qiniu_Encode($param['cssurl']);
					break;

			}
			return $url;
		}

		public function buildUrlParam(){
			return $_REQUEST;
		}

		//获取某个路径下的文件列表
		public function getList($query = array(), $path = ''){
			$query = array_merge(array('bucket'=>$this->bucket), $query);
			$url = "{$this->QINIU_RSF_HOST}/list?".http_build_query($query);
			$accessToken = $this->accessToken($url);
			$response = $this->request($url, 'POST', array('Authorization'=>"QBox $accessToken"));
			return $response;
		}

		//获取某个文件的信息
		public function info($key){
			$key = trim($key);
			$url = "{$this->QINIU_RS_HOST}/stat/" . self::Qiniu_Encode("{$this->bucket}:{$key}");
			$accessToken = $this->accessToken($url);
			$response = $this->request($url, 'POST', array(
				'Authorization'=>"QBox $accessToken",
			));
			return $response;
		}

		//获取文件下载资源链接
		public function downLink($key){
			$key = urlencode($key);
			$key = self::Qiniu_escapeQuotes($key);
			$url = "http://{$this->domain}/{$key}";
			return $url;
		}

		//重命名单个文件
		public function rename($file, $new_file){
			$key = trim($file);
			$url = "{$this->QINIU_RS_HOST}/move/" . self::Qiniu_Encode("{$this->bucket}:{$key}") .'/'. self::Qiniu_Encode("{$this->bucket}:{$new_file}");
			trace($url);
			$accessToken = $this->accessToken($url);
			$response = $this->request($url, 'POST', array('Authorization'=>"QBox $accessToken"));
			return $response;
		}

		//删除单个文件
		public function del($file){
			$key = trim($file);
			$url = "{$this->QINIU_RS_HOST}/delete/" . self::Qiniu_Encode("{$this->bucket}:{$key}");
			$accessToken = $this->accessToken($url);
			$response = $this->request($url, 'POST', array('Authorization'=>"QBox $accessToken"));
			return $response;
		}

		//批量删除文件
		public function delBatch($files){
			$url = $this->QINIU_RS_HOST . '/batch';
			$ops = array();
			foreach ($files as $file) {
				$ops[] = "/delete/". self::Qiniu_Encode("{$this->bucket}:{$file}");
			}
			$params = 'op=' . implode('&op=', $ops);
			$url .= '?'.$params;
			trace($url);
			$accessToken = $this->accessToken($url);
			$response = $this->request($url, 'POST', array('Authorization'=>"QBox $accessToken"));
			return $response;
		}

		static function Qiniu_Encode($str) {// URLSafeBase64Encode
			$find = array('+', '/');
			$replace = array('-', '_');
			return str_replace($find, $replace, base64_encode($str));
		}

		static function Qiniu_escapeQuotes($str){
			$find = array("\\", "\"");
			$replace = array("\\\\", "\\\"");
			return str_replace($find, $replace, $str);
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
	        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
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
	                return $this->response($response);
	            }
	        } else {
	            $this->error($header , $body);
	            return false;
	        }
	    }

        /**
	     * 获取响应数据
	     * @param  string $text 响应头字符串
	     * @return array        响应数据列表
	     */
	    private function response($text){
	        $headers = explode(PHP_EOL, $text);
	        $items = array();
	        foreach($headers as $header) {
	            $header = trim($header);
	            if(strpos($header, '{') !== False){
	                $items = json_decode($header, 1);
	                break;
	            }
	        }
	        return $items;
	    }

        /**
	     * 获取请求错误信息
	     * @param  string $header 请求返回头信息
	     */
		private function error($header, $body) {
	        list($status, $stash) = explode("\r\n", $header, 2);
	        list($v, $code, $message) = explode(" ", $status, 3);
	        $message = is_null($message) ? 'File Not Found' : "[{$status}]:{$message}]";
	        $this->error = $message;
	        $this->errorStr = json_decode($body ,1);
	        $this->errorStr = $this->errorStr['error'];
	    }
	}
