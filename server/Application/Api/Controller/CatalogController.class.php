<?php
namespace Api\Controller;
use Think\Controller;
class CatalogController extends BaseController {

    //获取目录列表
    public function catList(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d");
        if (!$this->checkItemVisit($login_user['uid'] , $item_id)) {
            $this->sendError(10103);
            return ;
        }
        if ($item_id > 0 ) {
            $ret = D("Catalog")->getList($item_id);
        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $this->sendResult(array());
        }
    }

    //获取目录列表
    public function catListGroup(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d");
        if (!$this->checkItemVisit($login_user['uid'] , $item_id)) {
            $this->sendError(10103);
            return ;
        }
        if ($item_id > 0 ) {
            $ret = D("Catalog")->getList($item_id,true);
        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
           $this->sendResult(array());
        }
    }

    //获取二级目录列表
    public function secondCatList(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d");
        if (!$this->checkItemVisit($login_user['uid'] , $item_id)) {
            $this->sendError(10103);
            return ;
        }
        if ($item_id > 0 ) {
            $ret = D("Catalog")->getListByLevel($item_id , 2);
        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $this->sendResult(array());
        }
    }

    //获取二级目录的子目录列表，即三级目录列表（如果存在的话）
    public function childCatList(){
        $login_user = $this->checkLogin();
        $cat_id = I("cat_id/d");
        if ($cat_id > 0 ) {
            $row = D("Catalog")->where(" cat_id = '$cat_id' ")->find() ;
            $item_id = $row['item_id'] ;
            if (!$this->checkItemVisit($login_user['uid'] , $item_id)) {
                $this->sendError(10103);
                return ;
            }
            $ret =  D("Catalog")->getChlid($item_id , $cat_id);
        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $this->sendResult(array());
        }      
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
            $this->sendError(10103);
            return;
        }
        //禁止空目录的生成
        if (!$cat_name) {
            return;
        }
        
        if ($parent_cat_id &&  $parent_cat_id == $cat_id) {
            $this->sendError(10101,"上级目录不能选择自身");
            return;
        }
        
        $data['cat_name'] = $cat_name ;
        $data['s_number'] = $s_number ;
        $data['item_id'] = $item_id ;
        $data['parent_cat_id'] = $parent_cat_id ;
        if ($parent_cat_id > 0 ) {
            $row = D("Catalog")->where(" cat_id = '$parent_cat_id' ")->find() ;
            $data['level'] = $row['level'] +1 ;
        }else{
            $data['level'] = 2;
        }

        if ($cat_id > 0 ) {
            $cat = D("Catalog")->where(" cat_id = '$cat_id' ")->find();
            $item_id = $cat['item_id']; 
            if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
                $this->sendError(10103);
                return;
            }
            //如果一个目录已经是别的目录的父目录，那么它将无法再转为level4目录
            if (D("Catalog")->where(" parent_cat_id = '$cat_id' ")->find() && $data['level'] == 4 ) {
                $this->sendError(10101,"该目录含有子目录，不允许转为底层目录。");
                return;
            }
            
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

        if (D("Page")->where(" cat_id = '$cat_id' and is_del = 0")->find() || D("Catalog")->where(" parent_cat_id = '$cat_id' ")->find()) {
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

    //编辑页面时，自动帮助用户选中目录
    //选中的规则是：编辑页面则选中该页面目录，复制页面则选中目标页面目录;
    //              如果是恢复历史页面则使用历史页面的目录，如果都没有则选中用户上次使用的目录
    public function getDefaultCat(){
        $login_user = $this->checkLogin();
        $page_id = I("page_id/d");
        $item_id = I("item_id/d");
        $page_history_id = I("page_history_id/d");
        $copy_page_id = I("copy_page_id/d");

        if ($page_id > 0 ) {
            if ($page_history_id) {
                $page = D("PageHistory")->where(" page_history_id = '$page_history_id' ")->find();
            }else{
                $page = M("Page")->where(" page_id = '$page_id' ")->find();
            }
            $default_cat_id = $page['cat_id'];
        }
        //如果是复制接口
        elseif ($copy_page_id) {
            $copy_page = M("Page")->where(" page_id = '$copy_page_id' ")->find();
            $page['item_id'] = $copy_page['item_id'];
            $default_cat_id = $copy_page['cat_id'];

        }else{
            //查找用户上一次设置的目录
            $last_page = D("Page")->where(" author_uid ='$login_user[uid]' and item_id = '$item_id' ")->order(" addtime desc ")->limit(1)->find();
            $default_cat_id = $last_page['cat_id'];


        }

        $item_id = $page['item_id'] ?$page['item_id'] :$item_id;

        
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->sendError(10101,L('no_permissions'));
            return;
        }

        $this->sendResult(array("default_cat_id"=>$default_cat_id ));
    }


}
