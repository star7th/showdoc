<?php
namespace Api\Controller;
use Think\Controller;
/*
    项目分组管理
 */
class ItemGroupController extends BaseController {

    //添加和编辑
    public function save(){
        $login_user = $this->checkLogin();

        $group_name = I("group_name");
        $item_ids = I("item_ids");
        $id = I("id/d");

        if ($id) {
            
            D("ItemGroup")->where(" id = '$id' ")->save(array("group_name"=>$group_name,"item_ids"=>$item_ids));

        }else{
            $data = array() ;
            $data['uid'] = $login_user['uid'] ;
            $data['group_name'] = $group_name ;
            $data['item_ids'] = $item_ids ;
            $data['created_at'] = date("Y-m-d H:i:s") ;
            $data['updated_at'] = date("Y-m-d H:i:s") ;
            $id = D("ItemGroup")->add($data);  
        }

        usleep(200000);
        $return = D("ItemGroup")->where(" id = '$id' ")->find();

        if (!$return && !$id ) {
            $return = array() ;
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
        }

        $this->sendResult($return);
        
    }

    //获取列表
    public function getList(){
        $login_user = $this->checkLogin();
        if ($login_user['uid'] > 0 ) {
            $ret = D("ItemGroup")->where(" uid = '$login_user[uid]' ")->order(" s_number asc,id asc  ")->select();
        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $this->sendResult(array());
        }
    }

    //删除
    public function delete(){
        $id = I("id/d")? I("id/d") : 0;
        $login_user = $this->checkLogin();
        if ($id && $login_user['uid']) {
            $ret = D("ItemGroup")->where(" id = '$id' and uid = '$login_user[uid]'")->delete();
        }
        if ($ret) {
            D("ItemGroup")->where(" id = '$id' ")->delete();
            D("ItemSort")->where(" item_group_id = '$id' ")->delete();
           $this->sendResult($ret);
        }else{
           $this->sendError(10101);
        }
    }

    // 给我的项目组们保存顺序
    public function saveSort(){
        $login_user = $this->checkLogin();
        $groups = I("groups") ;
        $data_array = json_decode(htmlspecialchars_decode($groups) , true) ;
        $uid = $login_user['uid'] ;
        if($data_array){
            foreach ($data_array as $key => $value) {
                $id = intval($value['id']);
                $ret = D("ItemGroup")->where(" id = '$id' and uid = '{$uid}'")->save(array('s_number'=>$value['s_number']));
            }
        }
        $this->sendResult(array());

    }


}