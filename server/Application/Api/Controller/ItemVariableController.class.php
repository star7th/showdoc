<?php
namespace Api\Controller;
use Think\Controller;
class ItemVariableController extends BaseController {


    //保存
    public function save(){ 
        $item_id = I("item_id/d");  
        $env_id = I("env_id/d"); 
        $var_name = I("var_name");  
        $var_value = I("var_value");  
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        if(!$this->checkItemEdit($uid , $item_id)){
            $this->sendError(10303);
            return ;
        }

        $id = 0 ;
        $res = D("ItemVariable")->where(" item_id = '{$item_id}' and env_id = '{$env_id}' and var_name = '%s'   " ,array($var_name) )->find() ;
        if($res){
            $id = $res['id'] ;
            D("ItemVariable")->where(" id = '{$id}' ")->save(array("var_value"=>$var_value));
        }else{
            $data = array() ;
            $data['var_name'] = $var_name ;
            $data['uid'] = $uid ;
            $data['var_value'] = $var_value ;
            $data['item_id'] = $item_id ;
            $data['env_id'] = $env_id ;
            $data['addtime'] = time() ;
            $id = D("ItemVariable")->add($data);
        }


        if (!$id) {
            $this->sendError(10101);
        }else{
            $this->sendResult($id);
        }
        
    }

    //获取列表
    public function getList(){
        $item_id = I("item_id/d");
        $env_id = I("env_id/d");
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        if(!$this->checkItemEdit($uid , $item_id)){
            $this->sendError(10303);
            return ;
        } 
        if ($item_id > 0 ) {
            $where = "item_id = '$item_id'";
            if($env_id){
                $where .= " and env_id = '$env_id'";
            }
            $ret = D("ItemVariable")->where($where)->order(" addtime asc  ")->select();
        }
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s",$value['addtime']);
            }
        }
        $this->sendResult($ret);
    }

    //删除
    public function delete(){
        $item_id = I("item_id/d");  
        $id = I("id/d");  
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        if(!$this->checkItemEdit($uid , $item_id)){
            $this->sendError(10303);
            return ;
        } 
         $ret = D("ItemVariable")->where(" item_id = '%d' and id = '%d'  ",array($item_id,$id))->delete();
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $this->sendError(10101);
        }
    }



    //根据name删除
    public function deleteByName(){
        $item_id = I("item_id/d");  
        $env_id = I("env_id/d");  
        $var_name = I("var_name");  
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        if(!$this->checkItemEdit($uid , $item_id)){
            $this->sendError(10303);
            return ;
        } 
        $ret = D("ItemVariable")->where(" item_id = '%d' and env_id = '%d' and var_name = '%s'  ",array($item_id,$env_id,$var_name))->delete();
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $this->sendError(10101);
        }
    }

}