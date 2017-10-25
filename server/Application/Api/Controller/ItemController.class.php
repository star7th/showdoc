<?php
namespace Api\Controller;
use Think\Controller;
class ItemController extends BaseController {

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
        $api_key = I("api_key");
        $api_token = I("api_token");
        $cat_name = I("cat_name");
        $cat_name_sub = I("cat_name_sub");
        $page_title = I("page_title");
        $page_content = I("page_content");
        $s_number = I("s_number") ? I("s_number") : 99;

        $ret = D("ItemToken")->getTokenByKey($api_key);
        if ($ret && $ret['api_token'] == $api_token) {
            $item_id = $ret['item_id'] ;
            D("ItemToken")->setLastTime($item_id);
        }else{
            $this->sendError(10306);
            return false;
        }

        //如果传送了二级目录
        if ($cat_name) {
            $cat_name_array = D("Catalog")->where(" item_id = '$item_id' and level = 2 and cat_name = '%s' ",array($cat_name))->find();
            //如果不存在则新建
            if (!$cat_name_array) {
                $add_data = array(
                    "cat_name" => $cat_name, 
                    "item_id" => $item_id, 
                    "addtime" => time(),
                    "level" => 2 
                    );
                D("Catalog")->add($add_data);
                $cat_name_array = D("Catalog")->where(" item_id = '$item_id' and level = 2 and cat_name = '%s' ",array($cat_name))->find();
            }
        }

        //如果传送了三级目录
        if ($cat_name_sub) {
            $cat_name_sub_array = D("Catalog")->where(" item_id = '$item_id' and level = 3 and cat_name = '%s' ",array($cat_name_sub))->find();
            //如果不存在则新建
            if (!$cat_name_sub_array) {
                $add_data = array(
                    "cat_name" => $cat_name_sub, 
                    "item_id" => $item_id, 
                    "parent_cat_id" => $cat_name_array['cat_id'], 
                    "addtime" => time(),
                    "level" => 3 
                    );
                D("Catalog")->add($add_data);
                $cat_name_sub_array = D("Catalog")->where(" item_id = '$item_id' and level = 3 and cat_name = '%s' ",array($cat_name_sub))->find();
            }
        }

        //目录id
        $cat_id = 0 ;
        if ($cat_name_array && $cat_name_array['cat_id'] > 0 ) {
            $cat_id = $cat_name_array['cat_id'] ;
        }

        if ($cat_name_sub_array && $cat_name_sub_array['cat_id'] > 0 ) {
            $cat_id = $cat_name_sub_array['cat_id'] ;
        }

        if ($page_content) {
            $page_array = D("Page")->where(" item_id = '$item_id'  and cat_id = '$cat_id'  and page_title ='%s' ",array($page_title))->find();
            //如果不存在则新建
            if (!$page_array) {
                $add_data = array(
                    "author_username" => "from_api", 
                    "item_id" => $item_id, 
                    "cat_id" => $cat_id, 
                    "page_title" => $page_title, 
                    "page_content" => $page_content, 
                    "s_number" => $s_number, 
                    "addtime" => time(),
                    );
                $page_id = D("Page")->add($add_data);
            }else{
                $page_id = $page_array['page_id'] ;
                $update_data = array(
                    "author_username" => "from_api", 
                    "item_id" => $item_id, 
                    "cat_id" => $cat_id, 
                    "page_title" => $page_title, 
                    "page_content" => $page_content, 
                    "s_number" => $s_number, 
                    );
                D("Page")->where(" page_id = '$page_id' ")->save($update_data);
            }
        }

        if ($page_id) {
            $ret = D("Page")->where(" page_id = '$page_id' ")->find();
            $this->sendResult($ret);
        }else{
            $this->sendError(10101);
        }
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

}