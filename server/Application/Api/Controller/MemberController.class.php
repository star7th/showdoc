<?php
namespace Api\Controller;
use Think\Controller;
class MemberController extends BaseController {


    //保存
    public function save(){ 
        $member_group_id =  I("member_group_id/d");
        $item_id = I("item_id/d");  
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        if(!$this->checkItemCreator($uid , $item_id)){
            $this->sendError(10303);
            return ;
        } 

        $username = I("username");
        $member = D("User")->where(" username = '%s' ",array($username))->find();

        if (!$member) {
            $this->sendError(10209);
            return ;
        }

        $data['username'] = $member['username'] ;
        $data['uid'] = $member['uid'] ;
        $data['item_id'] = $item_id ;
        $data['member_group_id'] = $member_group_id ;
        $data['addtime'] = time() ;
        

        $id = D("ItemMember")->add($data);
        $return = D("ItemMember")->where(" item_member_id = '$id' ")->find();

        if (!$return) {
            $this->sendError(10101);
        }else{
            $this->sendResult($return);
        }
        
    }

    //获取成员列表
    public function getList(){
        $item_id = I("item_id/d");  
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        if(!$this->checkItemCreator($uid , $item_id)){
            $this->sendError(10303);
            return ;
        } 
        if ($item_id > 0 ) {
            $ret = D("ItemMember")->where(" item_id = '$item_id' ")->order(" addtime asc  ")->select();
        }
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s",$value['addtime']);
                $value['member_group'] = $value['member_group_id'] == 1 ? "编辑" :"只读"; 
            }
        }
        $this->sendResult($ret);
    }

    //删除成员
    public function delete(){
        $item_id = I("item_id/d");  
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        if(!$this->checkItemCreator($uid , $item_id)){
            $this->sendError(10303);
            return ;
        } 
        $item_member_id = I("item_member_id/d");

        if ($item_member_id) {
            $member_array = D("ItemMember")->where(" item_id = '%d' and item_member_id = '%d'  ",array($item_id,$item_member_id))->find();
            $ret = D("ItemMember")->where(" item_id = '%d' and item_member_id = '%d'  ",array($item_id,$item_member_id))->delete();

        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $this->sendError(10101);
        }
    }




}