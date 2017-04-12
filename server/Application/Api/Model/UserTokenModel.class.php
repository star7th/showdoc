<?php
namespace Api\Model;
use Api\Model\BaseModel;
/**
 * 
 * @author star7th      
 */
class UserTokenModel extends BaseModel {

	public function createToken($uid,$token_expire = 0 ){
		$token_expire = $token_expire > 0  ? (time() + $token_expire ) : (time() + 60*60*24*90 );
		$token = md5(md5($uid.$token_expire.time().rand()."showdoc")."rdgsvgsrgr67hghf54t").md5($uid.$token_expire.time().rand()."showdoc");
		$data['uid'] = $uid ;
		$data['token'] = $token ;
		$data['token_expire'] = $token_expire ;
		$data['ip'] = getIPaddress() ;
		$data['addtime'] = time() ;
		$ret = $this->add($data);
		if ($ret) {
			//删除过期的token 
			$this->where( "token_expire < ".time() )->delete();
			return $token ;
		}
		return false ;
	}

	public function getToken($token){
		return $this->where("token='%s'",array($token))->find();
	}

	public function setLastTime($token){
		return $this->where("token='%s'",array($token))->save(array("last_check_time"=>time()));
	}
}