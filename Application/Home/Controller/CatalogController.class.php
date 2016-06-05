<?php
namespace Home\Controller;
use Think\Controller;
class CatalogController extends BaseController {

    //编辑页面
    public function edit(){

        $cat_id = I("cat_id/d");

        $Catalog = D("Catalog")->where(" cat_id = '$cat_id' ")->find();

        if ($Catalog) {
            $this->assign("Catalog" , $Catalog);
        }

        if ($Catalog['parent_cat_id']) {
            $this->assign("default_parent_cat_id" , $Catalog['parent_cat_id']);
        }
        
        $item_id = $Catalog['item_id'] ? $Catalog['item_id'] : I("item_id");

        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $this->assign("item_id" , $item_id);

        $this->display();        
    }

    //保存目录
    public function save(){
        $cat_name = I("cat_name");
        $s_number = I("s_number/d") ? I("s_number/d") : 99 ;
        $cat_id = I("cat_id/d")? I("cat_id/d") : 0;
        $parent_cat_id = I("parent_cat_id/d")? I("parent_cat_id/d") : 0;
        $item_id =  I("item_id/d");

        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $data['cat_name'] = $cat_name ;
        $data['s_number'] = $s_number ;
        $data['item_id'] = $item_id ;
        $data['parent_cat_id'] = $parent_cat_id ;
        if ($parent_cat_id > 0 ) {
           $data['level'] = 3;
        }else{
            $data['level'] = 2;
        }

        if ($cat_id > 0 ) {
            
            $ret = D("Catalog")->where(" cat_id = '$cat_id' ")->save($data);
            $return = D("Catalog")->where(" cat_id = '$cat_id' ")->find();

        }else{
            $data['addtime'] = time();
            $cat_id = D("Catalog")->add($data);
            $return = D("Catalog")->where(" cat_id = '$cat_id' ")->find();
            
        }
        if (!$return) {
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
        }
        $this->sendResult($return);
        
    }

    //获取目录列表
    public function catList(){
        $item_id = I("item_id/d");
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
        $item_id = I("item_id/d");
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

    //获取一个目录的子目录列表（如果存在的话）
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
    //删除目录
    public function delete(){
        $cat_id = I("cat_id/d")? I("cat_id/d") : 0;
        $cat = D("Catalog")->where(" cat_id = '$cat_id' ")->find();
        $item_id = $cat['item_id'];
        
        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $return['error_code'] = -1 ;
            $return['error_message'] = L('no_permissions');
            $this->sendResult($return);
            return;
        }

        if (D("Page")->where(" cat_id = '$cat_id' ")->find()) {
            $return['error_code'] = -1 ;
            $return['error_message'] = L('no_delete_empty_catalog') ;
            $this->sendResult($return);
            return;
        }

        if ($cat_id > 0 ) {
            
            $ret = D("Catalog")->where(" cat_id = '$cat_id' ")->delete();

        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $return['error_code'] = -1 ;
            $return['error_message'] = 'request  fail' ;
            $this->sendResult($return);
        }
    }




}