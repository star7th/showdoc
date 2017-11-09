<?php
namespace Home\Controller;
use Think\Controller;
class BaseController extends Controller {

	public function message($msg , $redirect = ''){
		$this->assign("msg" , $msg);
		$this->assign("redirect" , $redirect);
		$this->display ("Common/message");
	}

	public function checkLogin($redirect = true){
    	if (strtolower(C("DB_TYPE")) == 'mysql' ) {
            echo 'ShowDoc does not support mysql any more . https://www.showdoc.cc/help?page_id=31990 ';
            clear_runtime();
            exit();
    	}
		if ( ! session("login_user")) {
			$cookie_token = cookie('cookie_token');
			if ($cookie_token) {
				$ret = D("UserToken")->getToken($cookie_token);
				if ($ret && $ret['token_expire'] > time() ) {
					$login_user = D("User")->where("uid = $ret[uid]")->find();
					unset($ret['password']);
					session("login_user" , $login_user);
					return $login_user ;
				}
			}
			if ($redirect) {
				$this->message("你尚未登录！",U('Home/User/login'));
				exit();
			}
		}else{
			return  session("login_user") ;
		}
	}

	/**
	 * 返回json数据
	 */
	public function sendResult($array){
		if (isset($array['error_code'])) {
			$result['error_code'] = $array['error_code'] ;
			$result['error_message'] = $array['error_message'] ;
		}
		else{
			$result['error_code'] = 0 ;
			$result['data'] = $array ;
		}
		echo json_encode($result);
	}

	//判断某用户是否有项目管理权限（项目成员member_group_id为1，以及 项目创建者）
	protected function checkItemPermn($uid , $item_id){

		if (!$uid) {
			return false;
		}

		if (session("mamage_item_".$item_id)) {
			return true;
		}

		$item = D("Item")->where("item_id = '%d' ",array($item_id))->find();
		if ($item['uid'] && $item['uid'] == $uid) {
			session("mamage_item_".$item_id , 1 );
			return true;
		}
		$ItemMember = D("ItemMember")->where("item_id = '%d' and uid = '%d' and member_group_id = 1 ",array($item_id,$uid))->find();
		if ($ItemMember) {
			session("mamage_item_".$item_id , 1 );
			return true;
		}
		return false;
	}

	//判断某用户是否为项目创建者
	protected function checkItemCreator($uid , $item_id){
		if (!$uid) {
			return false;
		}
		if (session("creat_item_".$item_id)) {
			return true;
		}

		$item = D("Item")->where("item_id = '%d' ",array($item_id))->find();
		if ($item['uid'] && $item['uid'] == $uid) {
			session("creat_item_".$item_id , 1 );
			return true;
		}
		return false;
	}

	//判断某用户是否有项目访问权限（公开项目的话所有人可访问，私有项目则项目成员、项目创建者和访问密码输入者可访问）
	protected function checkItemVisit($uid , $item_id, $refer_url= ''){
		if (session("visit_item_".$item_id)) {
			return true;
		}

		if ($this->checkItemCreator($uid , $item_id)) {
			session("visit_item_".$item_id , 1 );
			return true;
		}

		$ItemMember = D("ItemMember")->where("item_id = '%d' and uid = '%d'  ",array($item_id,$uid))->find();
		if ($ItemMember) {
			session("visit_item_".$item_id , 1 );
			return true;
		}

		$item = D("Item")->where("item_id = '%d' ",array($item_id))->find();
		if ($item['password']) {
			//跳转到输入访问密码框
			header("location:".U("Home/item/pwd",array("item_id"=>$item_id,"refer_url"=>base64_encode($refer_url))));
		}else{
			session("visit_item_".$item_id , 1 );
			return true;
		}

	}


}