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
		return true ;

	}

}