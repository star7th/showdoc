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

        $item_id = $Catalog['item_id'] ? $Catalog['item_id'] : I("item_id");

        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message("你无权限");
            return;
        }

        $this->assign("item_id" , $item_id);

        $this->display();        
    }

    //保存目录
    public function save(){
        $cat_name = I("cat_name");
        $order = I("order/d") ? I("order/d") : 99 ;
        $cat_id = I("cat_id/d")? I("cat_id/d") : 0;
        $item_id =  I("item_id/d");

        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message("你无权限");
            return;
        }

        $data['cat_name'] = $cat_name ;
        $data['order'] = $order ;
        $data['item_id'] = $item_id ;
        

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
            $ret = D("Catalog")->where(" item_id = '$item_id' ")->order(" 'order', addtime asc  ")->select();
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
            $return['error_message'] = '你无权限' ;
            $this->sendResult($return);
            return;
        }

        if (D("Page")->where(" cat_id = '$cat_id' ")->find()) {
            $return['error_code'] = -1 ;
            $return['error_message'] = '为了安全，不允许直接删除非空目录。请先删除或转移该目录下的所有页面' ;
            $this->sendResult($return);
            return;
        }

        if ($cat_id > 0 ) {
            
            $ret = D("Catalog")->where(" cat_id = '$cat_id' ")->limit(1)->delete();

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