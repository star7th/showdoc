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
            $ret = D("Catalog")->filteMemberCat($login_user['uid'] , $ret);
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
            $ret = D("Catalog")->filteMemberCat($login_user['uid'] , $ret);
        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
           $this->sendResult(array());
        }
    }
    
    
     //获取目录列表，其中目录名将按层级描述。比如某个目录的名字为“我的项目/用户接口/用户登录”
     public function catListName(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d");
        if (!$this->checkItemVisit($login_user['uid'] , $item_id)) {
            $this->sendError(10103);
            return ;
        }
        if ($item_id > 0 ) {
            $ret = D("Catalog")->getList($item_id,true);
            $ret = D("Catalog")->filteMemberCat($login_user['uid'] , $ret);
        }
        if ($ret) {
            $return = array() ;

            //匿名递归函数，准备递归改名
            // uee 指令后面引用参数转递。方便函数内部中使用。
            $rename = function ($catalog, $p_cat_name) use (&$return , &$rename) {
               if($catalog){
                    foreach ($catalog as $key => $value) {
                        $value['cat_name'] = $p_cat_name .'/'. $value['cat_name'] ;
                        $sub = $value['sub'] ;
                        unset($value['sub']);
                        $return[] = $value ;
                        if($sub){
                            $rename($sub , $value['cat_name'] ) ;
                        }
                    }
               }
            };

            foreach ($ret as $key => $value) {
                $sub = $value['sub'] ;
                unset($value['sub']);
                $return[] = $value ;
                if($sub){
                    $rename($sub , $value['cat_name'] ) ;
                }
                
            }
            $this->sendResult($return);
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
        $s_number = I("s_number/d") ? I("s_number/d") : '' ;
        $cat_id = I("cat_id/d")? I("cat_id/d") : 0;
        $parent_cat_id = I("parent_cat_id/d")? I("parent_cat_id/d") : 0;
        $item_id =  I("item_id/d");

        $login_user = $this->checkLogin();
        if (!$this->checkItemEdit($login_user['uid'] , $item_id)) {
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
        if($s_number)$data['s_number'] = $s_number ;
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
            if (!$this->checkItemEdit($login_user['uid'] , $item_id)) {
                $this->sendError(10103);
                return;
            }
            //如果一个目录已经是别的目录的父目录，那么它将无法再转为level4目录
            //if (D("Catalog")->where(" parent_cat_id = '$cat_id' ")->find() && $data['level'] == 4 ) {
                //$this->sendError(10101,"该目录含有子目录，不允许转为底层目录。");
                //return;
            //}
            
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
        if (!$this->checkItemEdit($login_user['uid'] , $item_id)) {
            $return['error_code'] = -1 ;
            $return['error_message'] = L('no_permissions');
            $this->sendResult($return);
            return;
        }

        if ($cat_id > 0 ) {
            
            $ret = D("Catalog")->deleteCat($cat_id);

        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $return['error_code'] = -1 ;
            $return['error_message'] = 'request  fail' ;
            $this->sendResult($return);
        }
    }
    
    // 此方法开始慢慢少用。现在准备让前端自己用逻辑判断目录，不再需要后台获取。新建页面的时候默认使用当前打开页面的目录。
    // 但由于可能有些老旧客户端用到，先暂时保留该接口
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

        
        if (!$this->checkItemEdit($login_user['uid'] , $item_id)) {
            $this->sendError(10101,L('no_permissions'));
            return;
        }

        $this->sendResult(array("default_cat_id"=>$default_cat_id ));
    }

    //批量更新
    public function batUpdate(){
        $cats = I("cats");
        $item_id = I("item_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemEdit($login_user['uid'] , $item_id)) {
            $this->sendError(10103);
            return ;
        }
        $ret = '';
        $data_array = json_decode(htmlspecialchars_decode($cats) , true) ;
        if ($data_array) {
            foreach ($data_array as $key => $value) {
                if ($value['cat_name']) {
                    $ret = D("Catalog")->where(" cat_id = '%d' and item_id = '%d' ",array($value['cat_id'],$item_id) )->save(array(
                        "cat_name" => $value['cat_name'] ,
                        "parent_cat_id" => $value['parent_cat_id'] ,
                        "level" => $value['level'] ,
                        "s_number" => $value['s_number'] ,
                        ));
                }
                if ($value['page_id'] > 0) {
                    $ret = D("Page")->where(" page_id = '%d' and item_id = '%d' " ,array($value['page_id'],$item_id) )->save(array(
                        "cat_id" => $value['parent_cat_id'] ,
                        "s_number" => $value['s_number'] ,
                        ));
                }

            }
        }

        $this->sendResult(array());
    }


    //获取某个目录下所有页面的标题
    public function getPagesBycat(){
        $cat_id = I("cat_id/d")? I("cat_id/d") : 0;
        $item_id =  I("item_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemEdit($login_user['uid'] , $item_id)) {
            $this->sendError(10103);
            return ;
        }
        $return = D("Page")->where("cat_id = '$cat_id' and  item_id = '$item_id' and is_del = 0  ")->field("page_id , page_title,s_number")->order("s_number asc , page_id asc")->select();
        $this->sendResult($return);

    }

    //  复制或移动目录
    public function copy(){
        // 参数new_p_cat_id 复制完目录后，挂在哪个父目录下。这里是父目录id。可为0
        // $to_item_id 要复制到的项目id。可以是同一个项目，可以是跨项目。默认是同一个项目
        $cat_id = I("cat_id/d");
        $new_p_cat_id = I("new_p_cat_id/d") ? I("new_p_cat_id/d") : 0;
        $to_item_id = I("to_item_id/d") ? I("to_item_id/d") : 0 ;
        $is_del = I("is_del/d") ? I("is_del/d") : 0 ; // 复制完是否删除原目录（相当于移动目录）
        $login_user = $this->checkLogin();
        if (!$this->checkItemEdit($login_user['uid'] , $to_item_id)) {
            $this->sendError(10103);
            return ;
        }
        $old_cat_ary = D("Catalog")->where("cat_id = '$cat_id' ")->find() ;
        if (!$this->checkItemEdit($login_user['uid'] , $old_cat_ary['item_id'])) {
            $this->sendError(10103);
            return ;
        }
        $res = D("Catalog")->copy($login_user['uid'] , $cat_id ,$new_p_cat_id , $to_item_id  );
        if($is_del && $res){
            D("Catalog")->deleteCat($cat_id) ;
        }
        $this->sendResult($res);
    }

}
