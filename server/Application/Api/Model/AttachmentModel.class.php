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
				$url = upload_oss($uploadFile);
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

}