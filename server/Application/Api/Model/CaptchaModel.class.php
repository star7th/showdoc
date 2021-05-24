<?php
namespace Api\Model;
use Api\Model\BaseModel;
/**
 * 
 * @author star7th      
 */
class CaptchaModel extends BaseModel {

	public function check($captcha_id , $captcha, $none =''){
		$time = time() ;
		$captcha_id = intval($captcha_id) ;
		$captcha_array = $this->where(" captcha_id = '$captcha_id' and expire_time > $time ")->find();
		if ($captcha_array['captcha'] && $captcha_array['captcha'] == $captcha) {
			//检查完就设置该验证码过期
			$this->where(" captcha_id = '$captcha_id'")->save(array("expire_time"=>0));
			return true ;
		}else{
			//删除掉所有过期的二维码
			//$this->where(" expire_time < '$time' ")->delete();
		}
		return false;
	}
}