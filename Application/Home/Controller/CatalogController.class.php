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

}
