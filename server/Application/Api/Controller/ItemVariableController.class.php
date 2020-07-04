<?php
namespace Api\Controller;
use Think\Controller;
class ItemVariableController extends BaseController {


    //保存
    public function save(){ 
        $item_id = I("item_id/d");  
        $var_name = I("var_name");  
        $var_value = I("var_value");  
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        if(!$this->checkItemPermn($uid , $item_id)){
            $this->sendError(10303);
            return ;
        } 
        $data = array() ;
        $data['var_name'] = $var_name ;
        $data['uid'] = $uid ;
        $data['var_value'] = $var_value ;
        $data['item_id'] = $item_id ;
        $data['addtime'] = time() ;

        $id = D("ItemVariable")->add($data);

        if (!$id) {
            $this->sendError(10101);
        }else{
            $this->sendResult($id);
        }
        
    }

    //获取列表
    public function getList(){
        $item_id = I("item_id/d");  
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        if(!$this->checkItemPermn($uid , $item_id)){
            $this->sendError(10303);
            return ;
        } 
        if ($item_id > 0 ) {
            $ret = D("ItemVariable")->where(" item_id = '$item_id' ")->order(" addtime asc  ")->select();
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
        if(!$this->checkItemPermn($uid , $item_id)){
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




}