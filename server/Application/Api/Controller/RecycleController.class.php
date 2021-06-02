<?php
namespace Api\Controller;
use Think\Controller;
class RecycleController extends BaseController {



    //获取被删除的页面列表
    public function getList(){
        $item_id = I("item_id/d");  
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        if(!$this->checkItemManage($uid , $item_id)){
            $this->sendError(10303);
            return ;
        } 
        if ($item_id > 0 ) {
            $ret = D("Recycle")->where(" item_id = '$item_id' ")->order(" del_time desc  ")->select();
        }
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value['del_time'] = date("Y-m-d H:i:s",$value['del_time']);
            }
        }
        $this->sendResult($ret);
    }


    //恢复页面
    public function recover(){
        $item_id = I("item_id/d");  
        $page_id = I("page_id/d");  
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        if(!$this->checkItemManage($uid , $item_id)){
            $this->sendError(10303);
            return ;
        } 
        if ($item_id > 0 ) {
            M("Page")->where(" page_id = '$page_id' ")->save(array("is_del"=>0));
            D("Page")->where(" page_id = '$page_id' ")->save(array("is_del"=>0 ,"cat_id"=>0));
            $ret = D("Recycle")->where(" item_id = '$item_id' and page_id = '$page_id' ")->delete();
        }
        $this->sendResult(array());
    }



}