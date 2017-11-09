<?php
namespace Api\Model;
use Api\Model\BaseModel;
/**
 * 
 * @author star7th      
 */
class ItemTokenModel extends BaseModel {

	public function createToken($item_id){
		$api_key = md5(md5($item_id.microtime().rand()."showdoc")."srffsrfgr".rand()).rand();
		$api_token = md5(md5($item_id.microtime().rand()."showdoc")."rgrhbtgd34".rand()).rand();
		$data['item_id'] = $item_id ;
		$data['api_key'] = $api_key ;
		$data['api_token'] = $api_token ;
		$data['addtime'] = time() ;
		$ret = $this->add($data);
		if ($ret) {
			return $ret ;
		}
		return false ;
	}

	public function getTokenByItemId($item_id){
		$item_token = $this->where("item_id='$item_id'")->find();
		if (!$item_token) {
			$this->createToken($item_id);
			$item_token = $this->where("item_id='$item_id'")->find();
		}
		return $item_token ;
	}

	public function getTokenByKey($api_key){
		$item_token = $this->where("api_key='%s'",array($api_key))->find();
		return $item_token ;
	}

	public function setLastTime($item_id){
		return $this->where("item_id='$item_id'")->save(array("last_check_time"=>time()));
	}
}