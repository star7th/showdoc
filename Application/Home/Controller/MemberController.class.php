<?php
namespace Home\Controller;
use Think\Controller;
class MemberController extends BaseController {

    //编辑页面
    public function edit(){
        $item_id =  I("item_id");
        $login_user = $this->checkLogin();
        if (!$this->checkItemCreator($login_user['uid'] , $item_id)) {
            $this->message("你无权限");
            return;
        }
        $this->assign("item_id" , $item_id);

        $this->display();        
    }

    //保存
    public function save(){
        $item_id =  I("item_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemCreator($login_user['uid'] , $item_id)) {
            $this->message("你无权限");
            return;
        }
        $username = I("username");
        $member = D("User")->where(" username = '%s' ",array($username))->find();

        if (!$member) {
            $return['error_code'] = 10201 ;
            $return['error_message'] = '不存在此用户！' ;
            $this->sendResult($return);
            return ;
        }

        $data['username'] = $member['username'] ;
        $data['uid'] = $member['uid'] ;
        $data['item_id'] = $item_id ;
        $data['addtime'] = time() ;
        

        $id = D("ItemMember")->add($data);
        $return = D("ItemMember")->where(" item_member_id = '$id' ")->find();

        if (!$return) {
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
        }

        $this->sendResult($return);
        
    }

    //获取成员列表
    public function getList(){
        $item_id = I("item_id/d");
        if ($item_id > 0 ) {
            $ret = D("ItemMember")->where(" item_id = '$item_id' ")->order(" 'order', addtime asc  ")->select();
        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
            $this->sendResult($return);
        }
    }

    //删除目录
    public function delete(){
        $item_id = I("item_id/d")? I("item_id/d") : 0;
        $login_user = $this->checkLogin();
        if (!$this->checkItemCreator($login_user['uid'] , $item_id)) {
            $this->message("你无权限");
            return;
        }
        $username = I("username")? I("username") : 0;

        if ($username) {
            
            $ret = D("ItemMember")->where(" item_id = '%d' and username = '%s'  ",array($item_id,$username))->limit(1)->delete();

        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
            $this->sendResult($return);
        }
    }




}