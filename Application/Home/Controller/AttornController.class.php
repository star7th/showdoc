<?php
namespace Home\Controller;
use Think\Controller;
class AttornController extends BaseController {

    //转让页面
    public function index(){
        $item_id =  I("item_id");
        $login_user = $this->checkLogin();
        if (!$this->checkItemCreator($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }
        $this->assign("item_id" , $item_id);

        $this->display();        
    }

    //保存
    public function save(){
        $login_user = $this->checkLogin();

        $username = I("username");
        $item_id = I("item_id/d");
        $password = I("password");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();

        if(! D("User")-> checkLogin($item['username'],$password)){
            $return['error_code'] = 10102 ;
            $return['error_message'] = L('incorrect_password') ;
            $this->sendResult($return);
            return ;
        }

        $member = D("User")->where(" username = '%s' ",array($username))->find();

        if (!$member) {
            $return['error_code'] = 10201 ;
            $return['error_message'] = L('user_does_not_exist') ;
            $this->sendResult($return);
            return ;
        }

        $data['username'] = $member['username'] ;
        $data['uid'] = $member['uid'] ;
        

        $id = D("Item")->where(" item_id = '$item_id' ")->save($data);

        $return = D("Item")->where("item_id = '$item_id' ")->find();

        if (!$return) {
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
        }

        $this->sendResult($return);
        
    }

}