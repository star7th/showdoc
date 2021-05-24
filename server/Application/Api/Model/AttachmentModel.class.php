<?php
namespace Api\Model;
use Api\Model\BaseModel;
/**
 * 
 * @author star7th      
 */
class AttachmentModel extends BaseModel {

	Protected $autoCheckFields = false;  //一定要关闭字段缓存，不然会报找不到表的错误

	//获取某个用户的当前已使用附件流量
	public function getUserFlow($uid){
		$month = Date("Y-m") ;
		$file_flow = D("FileFlow")->where(" uid = '%s'  and date_month = '$month' " , array($uid))->find() ;
		if($file_flow){
			return intval($file_flow['used']) ;
		}else{
			D("FileFlow")->add(array(
				"uid" => $uid ,
				"used" => 0  ,
				"date_month" => $month ,

			));
			return 0 ;
		}
	}

	//记录某个用户流量
	public function recordUserFlow($uid , $file_size){
		$month = Date("Y-m") ;
		$used = $this->getUserFlow($uid) ;
		return D("FileFlow")->where(" uid = '%s'  and date_month = '$month' " , array($uid))->save(array(
			"used" => $used + intval($file_size) 
		));
	}

	public function deleteFile($file_id){
		$file_id = intval($file_id) ;
		$file = D("UploadFile")->where("file_id = '$file_id' ")->find();
		$real_url = $file['real_url'] ;
		$array = explode("/Public/Uploads/", $real_url) ;
		$file_path = "../Public/Uploads/".$array[1] ;
		if (file_exists($file_path)) {
			@unlink($file_path);
		}
		D("UploadFile")->where(" file_id = '$file_id' ")->delete();
		D("FilePage")->where(" file_id = '$file_id' ")->delete();
		return true ;

	}

	//上传文件，返回url
	public function upload($_files , $file_key , $uid , $item_id = 0  , $page_id = 0  ){
		$uploadFile = $_files[$file_key] ;

		if (strstr(strip_tags(strtolower($uploadFile['name'])), ".php") ) {
			return false;
	}
		$oss_open = D("Options")->get("oss_open" ) ;
		if ($oss_open) {
				$url = $this->uploadOss($uploadFile);
				if ($url) {
						$sign = md5($url.time().rand()) ;
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
						$url = get_domain().U("api/attachment/visitFile",array("sign" => $sign)); 
					  return $url ;
				}
		}else{
			$upload = new \Think\Upload();// 实例化上传类
			$upload->maxSize  = 1003145728 ;// 设置附件上传大小
			$upload->rootPath = './../Public/Uploads/';// 设置附件上传目录
			$upload->savePath = '';// 设置附件上传子目录
			$info = $upload->uploadOne($uploadFile) ;
			if(!$info) {// 上传错误提示错误信息
				var_dump($upload->getError());
				return;
			}else{// 上传成功 获取上传文件信息
				$url = get_domain().__ROOT__.substr($upload->rootPath,1).$info['savepath'].$info['savename'] ;
				$sign = md5($url.time().rand()) ;
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
				$url = get_domain().U("api/attachment/visitFile",array("sign" => $sign));
				return $url ;
			}
		}
		return false;
	}

  //上传到oss。参数$uploadFile是文件上传流，如$_FILES['file'] .也可以自己拼凑
	public function uploadOss($uploadFile){
		$oss_setting_json = D("Options")->get("oss_setting") ;
		$oss_setting = json_decode($oss_setting_json,1);
		if ($oss_setting && $oss_setting['oss_type'] && $oss_setting['oss_type'] == 'aliyun') {
				$config = array(
						"key" => $oss_setting['key'],
						"secret"=> $oss_setting['secret'],
						"endpoint"=> $oss_setting['endpoint'],
						"bucket"=> $oss_setting['bucket'],
				);
				// $oss = new_oss($config['key'] , $config['secret'] , $config['endpoint'] );
				include_once VENDOR_PATH .'Alioss/autoload.php';
				$oss = new \OSS\OssClient($config['key'] ,  $config['secret'] , $config['endpoint'] , false );
				$ext = strrchr($uploadFile['name'], '.'); //获取扩展名
				$oss_path = "showdoc_".time().rand().$ext;
				$res = $oss->uploadFile($config['bucket'],$oss_path,$uploadFile['tmp_name']);
				if ($res && $res['info'] && $res['info']['url']) {
						if ($oss_setting['domain']) {
								return $oss_setting['protocol'] . '://'.$oss_setting['domain']."/".$oss_path ;
						}else{
								return $res['info']['url'] ;
						}
						
				}
		}

		if ($oss_setting && $oss_setting['oss_type'] && $oss_setting['oss_type'] == 'qiniu') {
				$config = array(
										'rootPath' => './',
										'saveName' => array('uniqid', ''),
										'driver' => 'Qiniu',
										'driverConfig' => array(
														'accessKey' => $oss_setting['key'],
														'secrectKey' => $oss_setting['secret'], 
														'protocol'=>$oss_setting['protocol'],
														'domain' => $oss_setting['domain'],
														'bucket' => $oss_setting['bucket'], 
												)
					);
					//上传到七牛
					$Upload = new \Think\Upload($config);
					$info = $Upload->uploadOne($uploadFile);
					if ($info && $info['url']) {
							return $info['url'] ;
					}

		}
		//var_dump($config);
		// 腾讯云
		if ($oss_setting && $oss_setting['oss_type'] && $oss_setting['oss_type'] == 'qcloud') {
			$cosClient = new \Qcloud\Cos\Client(array('region' => $oss_setting['region'],
			'credentials'=> array(
					'secretId'    => $oss_setting['secretId'],
					'secretKey' => $oss_setting['secretKey']
				)));
				$ext = strrchr($uploadFile['name'], '.'); //获取扩展名
				$oss_path = "showdoc_".time().rand().rand().$ext;
				$result = $cosClient->putObject(array(
					'Bucket' => $oss_setting['bucket'],
					'Key' => $oss_path ,
					'Body' => fopen($uploadFile['tmp_name'], 'rb')));
				if ($result && $result['ObjectURL']) {
						if ($oss_setting['domain']) {
								return $oss_setting['protocol'] . '://'.$oss_setting['domain']."/".$oss_path ;
						}else{
								return $result['ObjectURL'] ;
						}
						
				}
		}


		return false ;
	}

}