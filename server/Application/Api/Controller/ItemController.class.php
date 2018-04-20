<?php
namespace Api\Controller;
use Think\Controller;
class ItemController extends BaseController {


    //单个项目信息
    public function info(){
        $this->checkLogin(false);
        $item_id = I("item_id");
        $item_domain = I("item_domain/s");
        $current_page_id = I("page_id/d");
        if (! is_numeric($item_id)) {
            $item_domain = $item_id ;
        }
        //判断个性域名
        if ($item_domain) {
            $item = D("Item")->where("item_domain = '%s'",array($item_domain))->find();
            if ($item['item_id']) {
                $item_id = $item['item_id'] ;
            }
        }
        $login_user = session("login_user");
        $uid = $login_user['uid'] ? $login_user['uid'] : 0 ;
        
        if(!$this->checkItemVisit($uid , $item_id)){
            $this->sendError(10303);
            return ;
        } 

        $item = D("Item")->where("item_id = '$item_id' ")->find();
        if (!$item) {
            sleep(1);
            $this->sendError(10101,'项目不存在或者已删除');
            return false;
        }
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

        $default_page_id = I("default_page_id/d");
        $keyword = I("keyword");
        $default_cat_id2 = $default_cat_id3 = 0 ;

        $login_user = session("login_user");
        $uid = $login_user['uid'] ? $login_user['uid'] : 0 ;
        $is_login =   $uid > 0 ? true :false;
            
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

        //如果带了默认展开的页面id，则获取该页面所在的二级目录和三级目录
        if ($default_page_id) {
            $page = D("Page")->where(" page_id = '$default_page_id' ")->find();
            if ($page) {
                $default_cat_id3 = $page['cat_id'] ;
                $cat2 = D("Catalog")->where(" cat_id = '$default_cat_id3' and parent_cat_id > 0  ")->find();
                if ($cat2) {
                    $default_cat_id2 = $cat2['parent_cat_id'];
                }else{
                    $default_cat_id2 = $default_cat_id3;
                    $default_cat_id3 = 0 ;
                }
            }
        }

        if (LANG_SET == 'en-us') {
            $help_url = "https://www.showdoc.cc/help-en";
        }
        else{
            $help_url = "https://www.showdoc.cc/help";
        }
        $menu =array(
            "pages" => $pages ,
            "catalogs" => $catalogs ,
            ) ;

        $return = array(
            "item_id"=>$item_id ,
            "item_domain"=>$item['item_domain'] ,
            "is_archived"=>$item['is_archived'] ,
            "default_page_id"=>(string)$default_page_id ,
            "default_cat_id2"=>$default_cat_id2 ,
            "default_cat_id3"=>$default_cat_id3 ,
            "unread_count"=>$unread_count ,
            "item_type"=>1 ,
            "menu"=>$menu ,
            "is_login"=>$is_login,
            "ItemPermn"=>$ItemPermn ,
            "ItemCreator"=>$ItemCreator ,

            );
        $this->sendResult($return);
    }

    //展示单页项目
    private function _show_single_page_item($item){
        $item_id = $item['item_id'];

        $current_page_id = I("page_id/d");

        $login_user = session("login_user");
        $uid = $login_user['uid'] ? $login_user['uid'] : 0 ;
        $is_login =   $uid > 0 ? true :false;
        //获取页面
        $page = D("Page")->where(" item_id = '$item_id' ")->find();

        $domain = $item['item_domain'] ? $item['item_domain'] : $item['item_id'];
        $share_url = get_domain().__APP__.'/'.$domain;

        $ItemPermn = $this->checkItemPermn($uid , $item_id) ;

        $ItemCreator = $this->checkItemCreator($uid , $item_id);

        $menu = array() ;
        $menu['pages'] = $page ;
        $return = array(
            "item_id"=>$item_id ,
            "item_domain"=>$item['item_domain'] ,
            "is_archived"=>$item['is_archived'] ,
            "current_page_id"=>$current_page_id ,
            "unread_count"=>$unread_count ,
            "item_type"=>2 ,
            "menu"=>$menu ,
            "is_login"=>$is_login,
            "ItemPermn"=>$ItemPermn ,
            "ItemCreator"=>$ItemCreator ,

            );
        $this->sendResult($return);
    }


    //我的项目列表
    public function myList(){
        $login_user = $this->checkLogin();        
        $items  = D("Item")->field("item_id,item_name,last_update_time,item_description")->where("uid = '$login_user[uid]' or item_id in ( select item_id from ".C('DB_PREFIX')."item_member where uid = '$login_user[uid]' ) ")->order("item_id asc")->select();
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

        $items = $items ? $items : array();
        $this->sendResult($items);

    }

    //项目详情
    public function detail(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d");  
        $uid = $login_user['uid'] ;
        if(!$this->checkItemCreator($uid , $item_id)){
            $this->sendError(10303);
            return ;
        }  
        $items  = D("Item")->where("item_id = '$item_id' ")->find();
        $items = $items ? $items : array();
        $this->sendResult($items);
    }

    //更新项目信息
    public function update(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d");  
        $item_name = I("item_name");  
        $item_description = I("item_description");  
        $item_domain = I("item_domain");  
        $password = I("password");
        $uid = $login_user['uid'] ;
        if(!$this->checkItemCreator($uid , $item_id)){
            $this->sendError(10303);
            return ;
        }

        if ($item_domain) {
            
            if(!ctype_alnum($item_domain) ||  is_numeric($item_domain) ){
                //echo '个性域名只能是字母或数字的组合';exit;
                $this->sendError(10305);
                return false;
            }

            $item = D("Item")->where("item_domain = '%s' and item_id !='%s' ",array($item_domain,$item_id))->find();
            if ($item) {
                //个性域名已经存在
                $this->sendError(10304);
                return false;
            }
        }
        $save_data = array(
            "item_name" => $item_name ,
            "item_description" => $item_description ,
            "item_domain" => $item_domain ,
            "password" => $password ,
            );
        $items  = D("Item")->where("item_id = '$item_id' ")->save($save_data);
        $items = $items ? $items : array();
        $this->sendResult($items);  
    }

    //转让项目
    public function attorn(){
        $login_user = $this->checkLogin();

        $username = I("username");
        $item_id = I("item_id/d");
        $password = I("password");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();

        if(!$this->checkItemCreator($login_user['uid'] , $item['item_id'])){
            $this->sendError(10303);
            return ;
        }

        if(! D("User")-> checkLogin($item['username'],$password)){
            $this->sendError(10208);
            return ;
        }

        $member = D("User")->where(" username = '%s' ",array($username))->find();

        if (!$member) {
            $this->sendError(10209);
            return ;
        }

        $data['username'] = $member['username'] ;
        $data['uid'] = $member['uid'] ;
        

        $id = D("Item")->where(" item_id = '$item_id' ")->save($data);

        $return = D("Item")->where("item_id = '$item_id' ")->find();

        if (!$return) {
            $this->sendError(10101);
        }

        $this->sendResult($return);
    }

    //删除项目
    public function delete(){
        $login_user = $this->checkLogin();

        $item_id = I("item_id/d");
        $password = I("password");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();

        if(!$this->checkItemCreator($login_user['uid'] , $item['item_id'])){
            $this->sendError(10303);
            return ;
        }

        if(! D("User")-> checkLogin($item['username'],$password)){
            $this->sendError(10208);
            return ;
        }


        D("Page")->where("item_id = '$item_id' ")->delete();
        D("Catalog")->where("item_id = '$item_id' ")->delete();
        D("PageHistory")->where("item_id = '$item_id' ")->delete();
        D("ItemMember")->where("item_id = '$item_id' ")->delete();
        $return = D("Item")->where("item_id = '$item_id' ")->delete();

        if (!$return) {
            $this->sendError(10101);
        }else{
        }

        $this->sendResult($return);
    }
    //归档项目
    public function archive(){
        $login_user = $this->checkLogin();

        $item_id = I("item_id/d");
        $password = I("password");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();

        if(!$this->checkItemCreator($login_user['uid'] , $item['item_id'])){
            $this->sendError(10303);
            return ;
        }

        if(! D("User")-> checkLogin($item['username'],$password)){
            $this->sendError(10208);
            return ;
        }

        $return = D("Item")->where("item_id = '$item_id' ")->save(array("is_archived"=>1));

        if (!$return) {
            $this->sendError(10101);
        }else{
            $this->sendResult($return);
        }

        
    }
    public function getKey(){
        $login_user = $this->checkLogin();

        $item_id = I("item_id/d");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();

        if(!$this->checkItemCreator($login_user['uid'] , $item['item_id'])){
            $this->sendError(10303);
            return ;
        }

        $item_token  = D("ItemToken")->getTokenByItemId($item_id);
        if (!$item_token) {
            $this->sendError(10101);
        }
        $this->sendResult($item_token);

    }

    public function resetKey(){

        $login_user = $this->checkLogin();

        $item_id = I("item_id/d");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();

        if(!$this->checkItemCreator($login_user['uid'] , $item['item_id'])){
            $this->sendError(10303);
            return ;
        }

        $ret = D("ItemToken")->where("item_id = '$item_id' ")->delete();

        if ($ret) {
            $this->getKey();
        }else{
            $this->sendError(10101);
        }
    }

    public function updateByApi(){
        //转到Open控制器的updateItem方法
        R('Open/updateItem');
    }

    //置顶项目
    public function top(){
        $login_user = $this->checkLogin();

        $item_id = I("item_id/d");
        $action = I("action");

        if ($action == 'top') {
            $ret = D("ItemTop")->add(array("item_id"=>$item_id,"uid"=>$login_user['uid'],"addtime"=>time()));
        }
        elseif ($action == 'cancel') {
            $ret = D("ItemTop")->where(" uid = '$login_user[uid]' and item_id = '$item_id' ")->delete();
        }
        if ($ret) {
            $this->sendResult(array());
        }else{
            $this->sendError(10101);
        }
    }
    
    //验证访问密码
    public function pwd(){
        $item_id = I("item_id/d");
        $password = I("password");
        $v_code = I("v_code");
        $refer_url = I('refer_url');

        //检查用户输错密码的次数。如果超过一定次数，则需要验证 验证码
        $key= 'item_pwd_fail_times_'.$item_id;
        if(!D("VerifyCode")->_check_times($key,10)){
            if (!$v_code || $v_code != session('v_code')) {
                $this->sendError(10206,L('verification_code_are_incorrect'));
                return;
            }
        }

        $item = D("Item")->where("item_id = '$item_id' ")->find();
        if ($item['password'] == $password) {
            session("visit_item_".$item_id , 1 );
            $this->sendResult(array("refer_url"=>base64_decode($refer_url))); 
        }else{
            D("VerifyCode")->_ins_times($key);//输错密码则设置输错次数
            
            if(D("VerifyCode")->_check_times($key,10)){
                $error_code = 10307 ;
            }else{
                $error_code = 10308 ;
            }
            $this->sendError($error_code,L('access_password_are_incorrect'));
        }

    }

    public function itemList(){
        $login_user = $this->checkLogin();        
        $items  = D("Item")->where("uid = '$login_user[uid]' ")->select();
        $items = $items ? $items : array();
        $this->sendResult($items);
    }

    //新建项目
    public function add(){
        $login_user = $this->checkLogin();
        $item_name = I("item_name");
        $item_domain = I("item_domain") ? I("item_domain") : '';
        $copy_item_id = I("copy_item_id");
        $password = I("password");
        $item_description = I("item_description");
        $item_type = I("item_type");

        if ($item_domain) {
            
            if(!ctype_alnum($item_domain) ||  is_numeric($item_domain) ){
                //echo '个性域名只能是字母或数字的组合';exit;
                $this->sendError(10305);
                return false;
            }

            $item = D("Item")->where("item_domain = '%s'  ",array($item_domain))->find();
            if ($item) {
                //个性域名已经存在
                $this->sendError(10304);
                return false;
            }
        }
        
        //如果是复制项目
        if ($copy_item_id > 0) {
            if (!$this->checkItemPermn($login_user['uid'] , $copy_item_id)) {
                $this->sendError(10103);
                return;
            }
            $ret = D("Item")->copy($copy_item_id,$login_user['uid'],$item_name,$item_description,$password,$item_domain);
            if ($ret) {
                $this->sendResult(array());             
            }else{
                $this->sendError(10101);
            }
            return ;
        }
        
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
                $page_id = D("Page")->add($insert);
            }
            $this->sendResult(array());               
        }else{
            $this->sendError(10101);
        }
        
    }


}