<?php
namespace Home\Controller;
use Think\Controller;
class ItemController extends BaseController {
    //项目列表页
    public function index(){
        $login_user = $this->checkLogin();        

        //跳转到web目录
        header("location:./web/#/item/index");
        exit();
        
        $share_url = get_domain().__APP__.'/uid/'.$login_user['uid'];

        $this->assign("login_user" , $login_user);
    	$this->assign("share_url" , $share_url);
        $this->display();
    }
    //我公开的项目列表
    public function showByUid(){
        $login_user = $this->checkLogin(false); //如果用户有登录，则赋值给$login_user
        $uid = I("uid/d");
        $show_user = D("User")->where(" uid = '$uid' ")->find();
        if ($show_user) {
            $items  = D("Item")->where(" password = '' and  ( uid = '$show_user[uid]' or item_id in ( select item_id from ".C('DB_PREFIX')."item_member where uid = '$show_user[uid]' ) ) ")->select();
            $this->assign("items" , $items);
            $this->assign("show_user" , $show_user);
            $this->assign("login_user" , $login_user);
            
        }
        if (LANG_SET == 'en-us') {
            $help_url = "https://www.showdoc.cc/help-en";
        }
        else{
            $help_url = "https://www.showdoc.cc/help";
        }

        $this->assign("help_url" , $help_url);
        $this->display();

    }

    //新建项目
    public function add(){
        $login_user = $this->checkLogin();
        $this->display ();
    }

    //根据项目类型展示项目
    //这些参数都不需要用到，只是为了兼容父类的方法。php8需要compatible with父类的同名方法
    public function show($content='', $charset = '', $contentType = '', $prefix = ''){
        $this->checkLogin(false);
        $item_id = I("item_id/d");
        $item_domain = I("item_domain/s");
        $current_page_id = I("page_id/d");

        //判断个性域名
        if ($item_domain) {
            $item = D("Item")->where("item_domain = '%s'",array($item_domain))->find();
            if ($item['item_id']) {
                $item_id = $item['item_id'] ;
            }
        }

        //跳转到web目录
        header("location:./web/#/".$item_id."?page_id=".$current_page_id);
        exit();
        
        
        $login_user = session("login_user");
        $uid = $login_user['uid'] ? $login_user['uid'] : 0 ;
            
        $this->checkItemVisit($uid , $item_id);


        $item = D("Item")->where("item_id = '$item_id' ")->find();
        if ($item['item_type'] == 1 ) {
            $this->_show_regular_item($item);
        }
        elseif ($item['item_type'] == 2 ) {
            $this->_show_single_page_item($item);
        }else{
           $this->_show_regular_item($item); 
        }
        

    }

    //展示常规项目
    private function _show_regular_item($item){
        $item_id = $item['item_id'];

        $current_page_id = I("page_id/d");
        $keyword = I("keyword");

        $login_user = session("login_user");
        $uid = $login_user['uid'] ? $login_user['uid'] : 0 ;
            
        //是否有搜索词
        if ($keyword) {
            $keyword = \SQLite3::escapeString($keyword) ;
            $pages = D("Page")->where("item_id = '$item_id' and ( page_title like '%{$keyword}%' or page_content like '%{$keyword}%' ) ")->order(" s_number asc  ")->field("page_id,author_uid,cat_id,page_title,addtime")->select();
        
        }else{
            //获取所有父目录id为0的页面
            $pages = D("Page")->where("cat_id = '0' and item_id = '$item_id' ")->order(" s_number asc  ")->field("page_id,author_uid,cat_id,page_title,addtime")->select();
            //获取所有二级目录
            $catalogs = D("Catalog")->where("item_id = '$item_id' and level = 2  ")->order(" s_number asc  ")->select();
            if ($catalogs) {
                foreach ($catalogs as $key => &$catalog) {
                    //该二级目录下的所有子页面
                    $temp = D("Page")->where("cat_id = '$catalog[cat_id]' ")->order(" s_number asc  ")->field("page_id,author_uid,cat_id,page_title,addtime")->select();
                    $catalog['pages'] = $temp ? $temp: array();

                    //该二级目录下的所有子目录
                    $temp = D("catalog")->where("parent_cat_id = '$catalog[cat_id]' ")->order(" s_number asc  ")->select();
                    $catalog['catalogs'] = $temp ? $temp: array();
                    if($catalog['catalogs']){
                        //获取所有三级目录的子页面
                        foreach ($catalog['catalogs'] as $key3 => &$catalog3) {
                            //该二级目录下的所有子页面
                            $temp = D("Page")->where("cat_id = '$catalog3[cat_id]' ")->order(" s_number asc  ")->field("page_id,author_uid,cat_id,page_title,addtime")->select();
                            $catalog3['pages'] = $temp ? $temp: array();
                        }                        
                    }               
                }
            }
        }

        $domain = $item['item_domain'] ? $item['item_domain'] : $item['item_id'];
        $share_url = get_domain().__APP__.'/'.$domain;

        $ItemPermn = $this->checkItemPermn($uid , $item_id) ;

        $ItemCreator = $this->checkItemCreator($uid , $item_id);

        if (LANG_SET == 'en-us') {
            $help_url = "https://www.showdoc.cc/help-en";
        }
        else{
            $help_url = "https://www.showdoc.cc/help";
        }
        
        $this->assign("help_url" , $help_url);
        $this->assign("current_page_id" , $current_page_id);
        $this->assign("keyword" , $keyword);
        $this->assign("ItemPermn" , $ItemPermn);
        $this->assign("ItemCreator" , $ItemCreator);
        $this->assign("share_url" , $share_url);
        $this->assign("catalogs" , $catalogs);
        $this->assign("pages" , $pages);
        $this->assign("item" , $item);
        $this->assign("login_user" , $login_user);
        $this->display("show_regular");
    }

    //展示单页项目
    private function _show_single_page_item($item){
        $item_id = $item['item_id'];

        $current_page_id = I("page_id/d");

        $login_user = session("login_user");
        $uid = $login_user['uid'] ? $login_user['uid'] : 0 ;

        //获取页面
        $page = D("Page")->where(" item_id = '$item_id' ")->find();

        $domain = $item['item_domain'] ? $item['item_domain'] : $item['item_id'];
        $share_url = get_domain().__APP__.'/'.$domain;

        $ItemPermn = $this->checkItemPermn($uid , $item_id) ;

        $ItemCreator = $this->checkItemCreator($uid , $item_id);

        $this->assign("current_page_id" , $current_page_id);
        $this->assign("ItemPermn" , $ItemPermn);
        $this->assign("ItemCreator" , $ItemCreator);
        $this->assign("share_url" , $share_url);
        $this->assign("catalogs" , $catalogs);
        $this->assign("page" , $page);
        $this->assign("item" , $item);
        $this->assign("login_user" , $login_user);
        $this->display("show_single_page");
    }
    //删除项目
    public function delete(){
        $item_id =  I("item_id");
        $login_user = $this->checkLogin();
        if (!$this->checkItemCreator($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }
        $this->assign("item_id" , $item_id);
        $this->display(); 
    }

    //删除项目
    public function ajaxDelete(){
        $login_user = $this->checkLogin();

        $item_id = I("item_id/d");
        $password = I("password");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();

        if(! D("User")-> checkLogin($item['username'],$password)){
            $return['error_code'] = 10102 ;
            $return['error_message'] = L('incorrect_password') ;
            $this->sendResult($return);
            return ;
        }


        D("Page")->where("item_id = '$item_id' ")->delete();
        D("Catalog")->where("item_id = '$item_id' ")->delete();
        D("PageHistory")->where("item_id = '$item_id' ")->delete();
        D("ItemMember")->where("item_id = '$item_id' ")->delete();
        $return = D("Item")->where("item_id = '$item_id' ")->delete();

        if (!$return) {
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
        }

        $this->sendResult($return);
    }

    //输入访问密码
    public function pwd(){
        $item_id = I("item_id/d");
        $CloseVerify = C('CloseVerify');
        $refer_url = I('refer_url');
        //var_dump(urldecode($refer_url));
        $this->assign('CloseVerify',$CloseVerify);
        $this->assign('refer_url',$refer_url);
        if (!IS_POST) {
          $this->assign("item_id" , $item_id);
          $this->display ();

        }else{
          $password = I("password");
          $v_code = I("v_code");
          if ( $CloseVerify ||  ( $v_code && $v_code == session('v_code') )) {
            $item = D("Item")->where("item_id = '$item_id' ")->find();
            if ($item['password'] == $password) {
                session("visit_item_".$item_id , 1 );
                if ($refer_url) {
                    header("location:".base64_decode($refer_url));
                }else{
                    header("location:".U("Home/Item/show").'&item_id='.$item_id);
                }
                
            }else{
                
                $this->message(L('access_password_are_incorrect'));
            }

          }else{
            $this->message(L('verification_code_are_incorrect'));
          }

        }
    }

    //导出word
    public function export(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d");  
        $uid = $login_user['uid'] ;
        $this->checkItemPermn($uid , $item_id) ; 

        $item = D("Item")->where("item_id = '$item_id' ")->find();

        //对于单页项目，直接导出。对于普通项目，则让其选择目录
        if ($item['item_type'] == 2 ) {
            $url = 'server/index.php?s=/api/export/word&item_id='.$item_id ;
            header("location:{$url}");
        }else{
            $this->assign("item_id",$item_id);
            $this->display();
        }
    }

    public function setting(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d");  
        $uid = $login_user['uid'] ;
        $this->checkItemPermn($uid , $item_id) ; 
        $this->assign("item_id",$item_id);
        $this->display();
    }

}