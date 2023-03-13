<?php

namespace Api\Model;

use Api\Model\BaseModel;
use AsyncAws\S3\S3Client;

/**
 * 
 * @author star7th      
 */
class AttachmentModel extends BaseModel
{

	protected $autoCheckFields = false;  //一定要关闭字段缓存，不然会报找不到表的错误

	//获取某个用户的当前已使用附件流量
	public function getUserFlow($uid)
	{
		$month = Date("Y-m");
		$file_flow = D("FileFlow")->where(" uid = '%s'  and date_month = '$month' ", array($uid))->find();
		if ($file_flow) {
			return intval($file_flow['used']);
		} else {
			D("FileFlow")->add(array(
				"uid" => $uid,
				"used" => 0,
				"date_month" => $month,

			));
			return 0;
		}
	}

	//记录某个用户流量
	public function recordUserFlow($uid, $file_size)
	{
		$month = Date("Y-m");
		$used = $this->getUserFlow($uid);
		return D("FileFlow")->where(" uid = '%s'  and date_month = '$month' ", array($uid))->save(array(
			"used" => $used + intval($file_size)
		));
	}

	public function deleteFile($file_id)
	{
		$file_id = intval($file_id);
		$file = D("UploadFile")->where("file_id = '$file_id' ")->find();
		$real_url = $file['real_url'];
		$array = explode("/Public/Uploads/", $real_url);
		$file_path = "../Public/Uploads/" . $array[1];
		if ( $array[1] && file_exists($file_path)) {
			@unlink($file_path);
		}else{
			$this->deleteOss($real_url);
		}
		
		D("UploadFile")->where(" file_id = '$file_id' ")->delete();
		D("FilePage")->where(" file_id = '$file_id' ")->delete();
		return true;
	}

	//上传文件，返回url
	public function upload($_files, $file_key, $uid, $item_id = 0, $page_id = 0, $check_filename = true)
	{
		$uploadFile = $_files[$file_key];

		if ($check_filename && !$this->isAllowedFilename($_files[$file_key]['name'])) {
			return false;
		}

		$oss_open = D("Options")->get("oss_open");
		if ($oss_open) {
			$url = $this->uploadOss($uploadFile);
			if ($url) {
				$sign = md5($url . time() . rand());
				$insert = array(
					"sign" => $sign,
					"uid" => $uid,
					"item_id" => $item_id,
					"page_id" => $page_id,
					"display_name" => $uploadFile['name'],
					"file_type" => $uploadFile['type'],
					"file_size" => $uploadFile['size'],
					"real_url" => $url,
					"addtime" => time(),
				);
				$file_id = D("UploadFile")->add($insert);
				$insert = array(
					"file_id" => $file_id,
					"item_id" => $item_id,
					"page_id" => $page_id,
					"addtime" => time(),
				);
				$ret = D("FilePage")->add($insert);
				$url = server_url("api/attachment/visitFile", array("sign" => $sign));
				return $url;
			}
		} else {
			$upload = new \Think\Upload(); // 实例化上传类
			$upload->maxSize  = 1003145728; // 设置附件上传大小
			$upload->rootPath = './../Public/Uploads/'; // 设置附件上传目录
			$upload->savePath = ''; // 设置附件上传子目录
			$info = $upload->uploadOne($uploadFile);
			if (!$info) { // 上传错误提示错误信息
				var_dump($upload->getError());
				return;
			} else { // 上传成功 获取上传文件信息
				$url = site_url() . '/Public/Uploads/' . $info['savepath'] . $info['savename'];
				$sign = md5($url . time() . rand());
				$insert = array(
					"sign" => $sign,
					"uid" => $uid,
					"item_id" => $item_id,
					"page_id" => $page_id,
					"display_name" => $uploadFile['name'],
					"file_type" => $uploadFile['type'],
					"file_size" => $uploadFile['size'],
					"real_url" => $url,
					"addtime" => time(),
				);
				$file_id = D("UploadFile")->add($insert);
				$insert = array(
					"file_id" => $file_id,
					"item_id" => $item_id,
					"page_id" => $page_id,
					"addtime" => time(),
				);
				$ret = D("FilePage")->add($insert);
				$url = server_url("api/attachment/visitFile", array("sign" => $sign));
				return $url;
			}
		}
		return false;
	}

	//上传到oss。参数$uploadFile是文件上传流，如$_FILES['file'] .也可以自己拼凑
	public function uploadOss($uploadFile)
	{
		$oss_setting_json = D("Options")->get("oss_setting");
		$oss_setting = json_decode($oss_setting_json, 1);

		if ($oss_setting && $oss_setting['oss_type'] && ($oss_setting['oss_type'] == 's3_storage' || $oss_setting['oss_type'] == 'aliyun')) {

			return $this->uploadS3($uploadFile, $oss_setting);
		}

		if ($oss_setting && $oss_setting['oss_type'] && $oss_setting['oss_type'] == 'qiniu') {

			$oss_setting['endpoint'] = $this->getQiuniuEndpointByKey($oss_setting['key'], $oss_setting['bucket']);
			return $this->uploadS3($uploadFile, $oss_setting);
		}
		// 腾讯云
		if ($oss_setting && $oss_setting['oss_type'] && $oss_setting['oss_type'] == 'qcloud') {
			// 腾讯云，一开始让用户填写region而没填写endpoint，所以要自己拼接
			$oss_setting['endpoint'] = "https://cos.{$oss_setting['region']}.myqcloud.com";
			// 腾讯云的SecretId相当于s3的key， secretKey相当于s3的secret
			$oss_setting['key'] = $oss_setting['secretId'];
			$oss_setting['secret'] = $oss_setting['secretKey'];
			return $this->uploadS3($uploadFile, $oss_setting);
		}


		return false;
	}

	// 通过s3协议上传
	// 注意传进来的oss_setting数组需要先转换成合法格式
	public function uploadS3($uploadFile, $oss_setting)
	{

		$ext = strrchr($uploadFile['name'], '.'); //获取扩展名
		$oss_path = "showdoc_" . get_rand_str() . $ext;

		// 如果不包含协议头，自己给它补充
		if (!strstr($oss_setting['endpoint'], '://')) {
			$oss_setting['endpoint'] = 'https://' . $oss_setting['endpoint'];
		}

		$s3 = new S3Client([
			'accessKeyId' => $oss_setting['key'],
			'accessKeySecret' => $oss_setting['secret'],
			'endpoint' => $oss_setting['endpoint'],
			'sendChunkedBody' => false
		]);

		// Send a PutObject request and get the result object.
		$resObj = $s3->putObject([
			'Bucket' => $oss_setting['bucket'],
			'Key'    => $oss_path,
			'Body'   => fopen($uploadFile['tmp_name'], 'rb'),
			// 增加浏览器的缓存控制头，减缓服务器压力，增加用户体验
			// 参考文章：https://csswizardry.com/2019/03/cache-control-for-civilians/
			'CacheControl' => 'public, max-age=31536000, s-maxage=31536000, immutable',
			// 设置正确的“Content-Type”响应头，避免浏览器将图片等文件当成数据流直接下载
			'ContentType' => $uploadFile['type']
		]);

		// 不抛出异常，默认就是成功的

		if ($oss_setting['domain']) {
			return $oss_setting['protocol'] . '://' . $oss_setting['domain'] . "/" . $oss_path;
		} else {
			$tmp_array = parse_url($oss_setting['endpoint']);
			$endpoint_host = $tmp_array['host'];
			return 'https://' . $oss_setting['bucket'] . '.' . $endpoint_host . '/' . $oss_path;
		}
	}

	//从oss中删除
	public function deleteOss($file_url)
	{
		$oss_setting_json = D("Options")->get("oss_setting");
		$oss_setting = json_decode($oss_setting_json, 1);
		if ($oss_setting && $oss_setting['oss_type'] && ($oss_setting['oss_type'] == 's3_storage' || $oss_setting['oss_type'] == 'aliyun')) {
			return $this->deleteS3($file_url, $oss_setting);
		}

		if ($oss_setting && $oss_setting['oss_type'] && $oss_setting['oss_type'] == 'qiniu') {
			$oss_setting['endpoint'] = $this->getQiuniuEndpointByKey($oss_setting['key'], $oss_setting['bucket']);
			return $this->deleteS3($file_url, $oss_setting);
		}
		//var_dump($config);
		// 腾讯云
		if ($oss_setting && $oss_setting['oss_type'] && $oss_setting['oss_type'] == 'qcloud') {

			// 腾讯云，一开始让用户填写region而没填写endpoint，所以要自己拼接
			$oss_setting['endpoint'] = "https://cos.{$oss_setting['region']}.myqcloud.com";
			// 腾讯云的SecretId相当于s3的key， secretKey相当于s3的secret
			$oss_setting['key'] = $oss_setting['secretId'];
			$oss_setting['secret'] = $oss_setting['secretKey'];
			return $this->deleteS3($file_url, $oss_setting);
		}


		return false;
	}

	// 通过s3协议删除
	// 注意传进来的oss_setting数组需要先转换成合法格式
	public function deleteS3($file_url, $oss_setting)
	{

		$array = parse_url($file_url);
		$file = $array['path'];  // 得到的是url中的路径，例如/path_.txt
		$file = substr($file, 1); // 要把路径前的/去掉，才是得到文件名path_.txt
		// 如果不包含协议头，自己给它补充
		if (!strstr($oss_setting['endpoint'], '://')) {
			$oss_setting['endpoint'] = 'https://' . $oss_setting['endpoint'];
		}

		$s3 = new S3Client([
			'accessKeyId' => $oss_setting['key'],
			'accessKeySecret' => $oss_setting['secret'],
			'endpoint' => $oss_setting['endpoint'],
		]);

		// Send a PutObject request and get the result object.
		$resObj = $s3->deleteObject([
			'Bucket' => $oss_setting['bucket'],
			'Key'    => $file,
		]);

		// 不抛出异常，默认就是成功的


	}

	// 由于历史原因，当初没有让用户填写七牛云的region。而且即使填写了，也不能直接获取到七牛云s3兼容协议上传的endpoint
	// 所以，需要自己调接口查询然后拼凑。七牛这个坑货。
	public function getQiuniuEndpointByKey($key, $bucket)
	{

		$query_url = "https://api.qiniu.com/v2/query?ak={$key}&bucket={$bucket}";
		$res = http_post($query_url, array());

		$array = json_decode($res, true);
		// var_dump($array);exit();
		if ($array && $array['region']) {
			switch ($array['region']) {
				case 'z0':
					return 'https://s3-cn-east-1.qiniucs.com';
					break;
				case 'z1':
					return 'https://s3-cn-north-1.qiniucs.com';
					break;
				case 'z2':
					return 'https://s3-cn-south-1.qiniucs.com';
					break;
				case 'na0':
					return 'https://s3-us-north-1.qiniucs.com';
					break;
				case 'as0':
					return 'https://s3-ap-southeast-1.qiniucs.com';
					break;
				default:
					return false;
					break;
			}
		}
	}

	// 判断文件名是否包含危险的扩展名
	// 准备弃用。因为一个个ban太麻烦了。准备改用白名单机制
	public function isDangerFilename($filename)
	{

		$isDangerStr = function ($filename, $keyword) {
			if (strstr(strip_tags(strtolower($filename)), $keyword)) {
				return true;
			}
			return false;
		};
		if (
			$isDangerStr($filename, ".php")
			|| $isDangerStr($filename, ".svg")
			|| $isDangerStr($filename, ".htm")
			|| $isDangerStr($filename, ".shtm")
			|| $isDangerStr($filename, "%")
			|| $isDangerStr($filename, ".xml")
			|| $isDangerStr($filename, ".xxhtml")
			|| $isDangerStr($filename, ".asp")
			|| $isDangerStr($filename, ".xsl")
			|| $isDangerStr($filename, ".aspx")
			|| $isDangerStr($filename, ".xsd")
			|| $isDangerStr($filename, ".asa")
			|| $isDangerStr($filename, ".cshtml")
			|| $isDangerStr($filename, ".axd")
			|| $isDangerStr($filename, "htm")
		) {
			return true;
		}

		return false;
	}

	// 判断上传的文件扩展名是否处于白名单内
	public function isAllowedFilename($filename)
	{
		$allow_array = array(
			'.jpg', '.jpeg', '.png', '.bmp', '.gif', '.ico', '.webp',
			'.mp3', '.wav', '.mp4', '.mov', '.flac', '.mkv',
			'.zip', '.tar', '.gz', '.tgz', '.ipa', '.apk', '.rar', '.iso',
			'.pdf', '.epub', '.xps', '.doc', '.docx', '.wps',
			'.ppt', '.pptx', '.xls', '.xlsx', '.txt', '.psd', '.csv',
			'.cer', '.ppt', '.pub', '.json', '.css',
		);

		$ext = strtolower(substr($filename, strripos($filename, '.'))); //获取文件扩展名（转为小写后）
		if (in_array($ext, $allow_array)) {
			return true;
		}
		return false;
	}
}
