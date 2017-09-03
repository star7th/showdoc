<?php
namespace Api\Controller;
use Think\Controller;
class CatalogController extends BaseController {

    //获取目录列表
    public function catList(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d");
        if (!$this->checkItemVisit($login_user['uid'] , $item_id)) {
            $this->sendError(10303);
            return ;
        }
        if ($item_id > 0 ) {
            $ret = D("Catalog")->where(" item_id = '$item_id' ")->order(" 's_number', addtime asc  ")->select();
        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
            $this->sendResult($return);
        }
    }

    //获取二级目录列表
    public function secondCatList(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d");
        if (!$this->checkItemVisit($login_user['uid'] , $item_id)) {
            $this->sendError(10303);
            return ;
        }
        if ($item_id > 0 ) {
            $ret = D("Catalog")->where(" item_id = '$item_id' and level =2  ")->order(" 's_number', addtime asc  ")->select();
        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
            $this->sendResult($return);
        }
    }

    //获取二级目录的子目录列表，即三级目录列表（如果存在的话）
    public function childCatList(){
        $cat_id = I("cat_id/d");
        if ($cat_id > 0 ) {
            $ret = D("Catalog")->where(" parent_cat_id = '$cat_id' ")->order(" 's_number', addtime asc  ")->select();
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