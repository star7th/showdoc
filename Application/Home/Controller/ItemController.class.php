<?php
namespace Home\Controller;
use Think\Controller;
class ItemController extends BaseController {
    //项目列表页
    public function index(){
        $login_user = $this->checkLogin();        
        $items  = D("Item")->where("uid = '$login_user[uid]' or item_id in ( select item_id from ".C('DB_PREFIX')."item_member where uid = '$login_user[uid]' ) ")->select();
        //读取需要置顶的项目
        $top_items = D("ItemTop")->where("uid = '$login_user[uid]'")->select();
        if ($top_items) {
            $top_item_ids = array() ;
            foreach ($top_items as $key => $value) {
                $top_item_ids[] = $value['item_id'];
            }
            foreach ($items as $key => $value) {
                $items[$key]['top'] = 0 ;
                if (in_array($value['item_id'], $top_item_ids) ) {
                    $items[$key]['top'] = 1 ;
                    $tmp = $items[$key] ;
                    unset($items[$key]);
                    array_unshift($items,$tmp) ;
                }
            }

            $items = array_values($items);
        }
        
        $share_url = get_domain().__APP__.'/uid/'.$login_user['uid'];

        $this->assign("items" , $items);
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
        $item_id = I("item_id/d");
        if (!IS_POST) {
          $item = D("Item")->where("item_id = '$item_id' ")->find();
          $this->assign("item" , $item);
          $this->display ();

        }else{
            $item_name = I("item_name");
            $item_domain = I("item_domain") ? I("item_domain") : '';
            $copy_item_id = I("copy_item_id");
            $password = I("password");
            $item_description = I("item_description");
            $item_type = I("item_type");

            if ($item_domain) {
                $item = D("Item")->where("item_domain = '%s' and item_id !='%s' ",array($item_domain,$item_id))->find();
                if ($item) {
                    //个性域名已经存在
                    $this->message(L('domain_already_exists'));
                    return false;
                }
                if(!ctype_alnum($item_domain) ||  is_numeric($item_domain) ){
                    //echo '个性域名只能是字母或数字的组合';exit;
                    $this->message(L('item_domain_illegal'));
                    return false;
                }
            }
            
            //如果是复制项目
            if ($copy_item_id > 0) {
                if (!$this->checkItemPermn($login_user['uid'] , $copy_item_id)) {
                    $this->message(L('no_permissions'));
                    return;
                }
                $ret = D("Item")->copy($copy_item_id,$login_user['uid'],$item_name,$item_description,$password,$item_domain);
                if ($ret) {
                    $this->message(L('operation_succeeded'),U('Home/Item/index'));              
                }else{
                    $this->message(L('operation_failed'),U('Home/Item/index'));
                }
                return ;
            }
            if ($item_id > 0 ) {
                $data = array(
                    "item_name" => $item_name ,
                    "item_domain" => $item_domain ,
                    "password" => $password ,
                    "item_description" => $item_description ,
                    );
                $ret = D("Item")->where("item_id = '$item_id' ")->save($data);
            }else{
                $insert = array(
                    "uid" => $login_user['uid'] ,
                    "username" => $login_user['username'] ,
                    "item_name" => $item_name ,
                    "password" => $password ,
                    "item_description" => $item_description ,
                    "item_domain" => $item_domain ,
                    "item_type" => $item_type ,
                    "addtime" =>time()
                    );
                $item_id = D("Item")->add($insert);
            }

            if ($item_id) {
                //如果是单页应用，则新建一个默认页
                if ($item_type == 2 ) {
                    $insert = array(
                        'author_uid' => $login_user['uid'] ,
                        'author_username' => $login_user['username'],
                        "page_title" => $item_name ,
                        "item_id" => $item_id ,
                        "cat_id" => 0 ,
                        "page_content" => '欢迎使用showdoc。点击右上方的编辑按钮进行编辑吧！' ,
                        "addtime" =>time()
                        );
                    D("Page")->add($insert);
                }
                $this->message(L('operation_succeeded'),U('Home/Item/index'));              
            }else{
                $this->message(L('operation_failed'),U('Home/Item/index'));
            }
        }
    }

    //根据项目类型展示项目
    public function show(){
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
            $pages = D("Page")->where("item_id = '$item_id' and ( page_title like '%{$keyword}%' or page_content like '%{$keyword}%' ) ")->order(" `s_number` asc  ")->field("page_id,author_uid,cat_id,page_title,addtime")->select();
        
        }else{
            //获取所有父目录id为0的页面
            $pages = D("Page")->where("cat_id = '0' and item_id = '$item_id' ")->order(" `s_number` asc  ")->field("page_id,author_uid,cat_id,page_title,addtime")->select();
            //获取所有二级目录
            $catalogs = D("Catalog")->where("item_id = '$item_id' and level = 2  ")->order(" `s_number` asc  ")->select();
            if ($catalogs) {
                foreach ($catalogs as $key => &$catalog) {
                    //该二级目录下的所有子页面
                    $temp = D("Page")->where("cat_id = '$catalog[cat_id]' ")->order(" `s_number` asc  ")->field("page_id,author_uid,cat_id,page_title,addtime")->select();
                    $catalog['pages'] = $temp ? $temp: array();

                    //该二级目录下的所有子目录
                    $temp = D("catalog")->where("parent_cat_id = '$catalog[cat_id]' ")->order(" `s_number` asc  ")->select();
                    $catalog['catalogs'] = $temp ? $temp: array();
                    if($catalog['catalogs']){
                        //获取所有三级目录的子页面
                        foreach ($catalog['catalogs'] as $key3 => &$catalog3) {
                            //该二级目录下的所有子页面
                            $temp = D("Page")->where("cat_id = '$catalog3[cat_id]' ")->order(" `s_number` asc  ")->field("page_id,author_uid,cat_id,page_title,addtime")->select();
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

    public function itemList(){
        $login_user = $this->checkLogin();        
        $items  = D("Item")->where("uid = '$login_user[uid]' ")->select();
        $items = $items ? $items : array();
        $this->sendResult($items);
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