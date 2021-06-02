<?php
namespace Api\Controller;
use Think\Controller;
class PageController extends BaseController {

    //页面详情
    public function info(){
        $page_id = I("page_id/d");
        $page = D("Page")->where(" page_id = '$page_id' ")->find();
        if (!$page  || $page['is_del'] == 1) {
            sleep(1);
            $this->sendError(10101);
            return false;
        }
        $login_user = $this->checkLogin(false);
        if (!$this->checkItemVisit($login_user['uid'] , $page['item_id'])) {
            $this->sendError(10103);
            return;
        }
        $page = $page ? $page : array();
        if ($page) {
           //unset($page['page_content']);
           $page['addtime'] = date("Y-m-d H:i:s",$page['addtime']);
           //判断是否包含附件信息
           $page['attachment_count'] = D("FilePage")->where("page_id = '$page_id' ")->count();

           $singlePage = M("SinglePage")->where(" page_id = '%d' ",array($page_id))->limit(1)->find();
           if ($singlePage) {
                $page['unique_key'] =  $singlePage['unique_key'] ;
           }else{
                $page['unique_key'] = '' ;
           }

        }
        $this->sendResult($page);
        // 埋个点，升级数据库
        R("Update/checkDb" , array(false));
    }
    //删除页面
    public function delete(){
        $page_id = I("page_id/d")? I("page_id/d") : 0;
        $page = D("Page")->where(" page_id = '$page_id' ")->find();

        $login_user = $this->checkLogin();
        if (!$this->checkItemManage($login_user['uid'] , $page['item_id']) && $login_user['uid'] != $page['author_uid']) {
            $this->sendError(10303);
            return ;
        }

        if ($page) {
            
            $ret = D("Page")->softDeletePage($page_id);
            //更新项目时间
            D("Item")->where(" item_id = '$page[item_id]' ")->save(array("last_update_time"=>time()));

        }
        if ($ret) {
           $this->sendResult(array());
        }else{
           $this->sendError(10101);
        }
    }

    //保存
    public function save(){
        $login_user = $this->checkLogin();
        $page_id = I("page_id/d") ? I("page_id/d") : 0 ;
        $is_urlencode = I("is_urlencode/d") ? I("is_urlencode/d") : 0 ; //页面内容是否经过了转义
        $page_title = I("page_title") ?I("page_title") : L("default_title");
        $page_comments = I("page_comments") ?I("page_comments") :'';
        $page_content = I("page_content");
        $cat_id = I("cat_id/d")? I("cat_id/d") : 0;
        $item_id = I("item_id/d")? I("item_id/d") : 0;
        $s_number = I("s_number/d")? I("s_number/d") : '';

        $login_user = $this->checkLogin();
        if (!$this->checkItemEdit($login_user['uid'] , $item_id)) {
            $this->sendError(10103);
            return;
        }
        if (!$page_content) {
            $this->sendError(10103,"不允许保存空内容，请随便写点什么");
            return;
        }
        if ($is_urlencode) {
            $page_content = urldecode($page_content);
        }
        $data['page_title'] = $page_title ;
        $data['page_content'] = $page_content ;
        $data['page_comments'] = $page_comments ;
        if($s_number)$data['s_number'] = $s_number ;
        $data['item_id'] = $item_id ;
        $data['cat_id'] = $cat_id ;
        $data['addtime'] = time();
        $data['author_uid'] = $login_user['uid'] ;
        $data['author_username'] = $login_user['username'];
        


        if ($page_id > 0 ) {
            
            // 设置里的历史版本数量
            $history_version_count = D("Options")->get("history_version_count" ) ;
            if(!$history_version_count){
                $history_version_count = 20 ;
                D("Options")->set("history_version_count" ,$history_version_count) ;
            }

            //在保存前先把当前页面的版本存档
            $page = D("Page")->where(" page_id = '$page_id' ")->find();
            if (!$this->checkItemEdit($login_user['uid'] , $page['item_id'])) {
                $this->sendError(10103);
                return;
            }
            $insert_history = array(
                'page_id'=>$page['page_id'],
                'item_id'=>$page['item_id'],
                'cat_id'=>$page['cat_id'],
                'page_title'=>$page['page_title'],
                'page_comments'=>$page['page_comments'],
                'page_content'=>base64_encode( gzcompress($page['page_content'], 9)),
                's_number'=>$page['s_number'],
                'addtime'=>$page['addtime'],
                'author_uid'=>$page['author_uid'],
                'author_username'=>$page['author_username'],
                );
             D("PageHistory")->add($insert_history);

            $ret = D("Page")->where(" page_id = '$page_id' ")->save($data);

            //统计该page_id有多少历史版本了
            $Count = D("PageHistory")->where(" page_id = '$page_id' ")->Count();
            if ($Count > $history_version_count ) {
               //每个单页面只保留最多$history_version_count个历史版本
               $ret = D("PageHistory")->where(" page_id = '$page_id' ")->limit($history_version_count)->order("page_history_id desc")->select();
               D("PageHistory")->where(" page_id = '$page_id' and page_history_id < ".$ret[$history_version_count-1]['page_history_id'] )->delete();
            }

            //如果是单页项目，则将页面标题设置为项目名
            $item_array = D("Item")->where(" item_id = '$item_id' ")->find();
            if ($item_array['item_type'] == 2 ) {
                D("Item")->where(" item_id = '$item_id' ")->save(array("last_update_time"=>time(),"item_name"=>$page_title));
            }else{
                D("Item")->where(" item_id = '$item_id' ")->save(array("last_update_time"=>time()));
            }

            $return = D("Page")->where(" page_id = '$page_id' ")->find();
        }else{
            
            $page_id = D("Page")->add($data);

            //更新项目时间
            D("Item")->where(" item_id = '$item_id' ")->save(array("last_update_time"=>time()));

            $return = D("Page")->where(" page_id = '$page_id' ")->find();
        }
        if (!$return) {
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
        }
        $this->sendResult($return);
        
    }


    //历史版本列表
    public function history(){
        $login_user = $this->checkLogin(false);
        $page_id = I("page_id/d") ? I("page_id/d") : 0 ;
        $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if (!$this->checkItemVisit($login_user['uid'] , $page['item_id'])) {
            $this->sendError(10103);
            return;
        }

        $PageHistory = D("PageHistory")->where("page_id = '$page_id' ")->order(" addtime desc")->limit(20)->select();

        if ($PageHistory) {
            foreach ($PageHistory as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s" , $value['addtime']);
                $page_content = uncompress_string($value['page_content']);
                if (!empty($page_content)) {
                    $value['page_content'] = htmlspecialchars_decode($page_content) ;
                }
            }

            $this->sendResult($PageHistory);
        }else{
            $this->sendResult(array());
        }
                

    }


    // 更新历史备注信息
    public function updateHistoryComments(){
        $login_user = $this->checkLogin(false);
        $page_id = I("page_id/d") ? I("page_id/d") : 0 ;
        $page_comments = I("page_comments") ;
        $page_history_id = I("page_history_id/d") ? I("page_history_id/d") : 0 ;
        $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if (!$this->checkItemEdit($login_user['uid'] , $page['item_id'])) {
            $this->sendError(10103);
            return;
        }
        $res = D("PageHistory")->where(" page_history_id = '$page_history_id' ")->save(array(
            "page_comments"=>$page_comments
        ));
        $this->sendResult($res);
    }


    //返回当前页面和历史某个版本的页面以供比较
    public function diff(){
        $page_id = I("page_id/d");
        $page_history_id = I("page_history_id/d");
        if (!$page_id) {
            return false;
        }
        $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if (!$page) {
            sleep(1);
            $this->sendError(10101);
            return false;
        }
        $login_user = $this->checkLogin(false);
        if (!$this->checkItemVisit($login_user['uid'] , $page['item_id'])) {
            $this->sendError(10103);
            return;
        }

        $history_page = D("PageHistory")->where(" page_history_id = '$page_history_id' ")->find();
        $page_content = uncompress_string($history_page['page_content']); 
        $history_page['page_content'] = $page_content ? $page_content : $history_page['page_content'] ;

        $this->sendResult(array("page"=>$page,"history_page"=>$history_page));
    }


    //上传图片
    public function uploadImg(){
        //重定向控制器和方法
        R("Attachment/uploadImg");
    }

    //上传附件
    public function upload(){
        //重定向控制器和方法
        R("Attachment/attachmentUpload");
    }

    public function uploadList(){
        //重定向控制器和方法
        R("Attachment/pageAttachmentUploadList");
    }

    //删除已上传文件
    public function deleteUploadFile(){
        //重定向控制器和方法
        R("Attachment/deletePageUploadFile");
    }


    //创建单页
    public function createSinglePage(){
        $page_id = I("page_id/d");
        $isCreateSiglePage = I("isCreateSiglePage");
        $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if (!$page || $page['is_del'] == 1) {
            sleep(1);
            $this->sendError(10101);
            return false;
        }
        $login_user = $this->checkLogin(false);
        if (!$this->checkItemEdit($login_user['uid'] , $page['item_id'])) {
            $this->sendError(10103);
            return;
        }
        D("SinglePage")->where(" page_id = '$page_id' ")->delete();
        $unique_key = md5(time().rand()."gbgdhbdgtfgfK3@bv45342regdhbdgtfgftghsdg");
        $add = array(
            "unique_key" => $unique_key ,
            "page_id" => $page_id ,
            );
        if ($isCreateSiglePage == 'true') { //这里的布尔值被转成字符串了
           D("SinglePage")->add($add);
           $this->sendResult($add);
        }else{
            $this->sendResult(array());
        }
        
    }

    //页面详情
    public function infoByKey(){
        $unique_key = I("unique_key");
        if (!$unique_key) {
            return false;
        }
        $singlePage = M("SinglePage")->where(" unique_key = '%s' ",array($unique_key))->find();
        $page_id = $singlePage['page_id'];

        $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if (!$page || $page['is_del'] == 1) {
            sleep(1);
            $this->sendError(10101);
            return false;
        }
        $login_user = $this->checkLogin(false);
        $page = $page ? $page : array();
        if ($page) {
           unset($page['item_id']);
           unset($page['cat_id']);
           $page['addtime'] = date("Y-m-d H:i:s",$page['addtime']);
           //判断是否包含附件信息
           $page['attachment_count'] = D("FilePage")->where("page_id = '$page_id' ")->count();

        }
        $this->sendResult($page);
    }

    //同一个目录下的页面排序
    public function sort(){
        $pages = I("pages");
        $item_id = I("item_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemEdit($login_user['uid'] , $item_id)) {
            $this->sendError(10103);
            return ;
        }
        $ret = '';
        $data_array = json_decode(htmlspecialchars_decode($pages) , true) ;
        if ($data_array) {
            foreach ($data_array as $key => $value) {
                $ret = D("Page")->where(" page_id = '%d' and item_id = '%d' ",array($key ,$item_id ) )->save(array(
                    "s_number" => $value ,
                    ));
            }
        }

        $this->sendResult(array());
    }


    //判断页面是否加了编辑锁
    public function  isLock(){
        $page_id = I("page_id/d");
        $lock = 0 ;
        $now = time() ;
        $login_user = $this->checkLogin(false);
        $res = D("PageLock")->where(" page_id = '$page_id' and page_id > 0 and lock_to > '{$now}' ")->find() ;
        if( $res){
            $lock = 1 ;
        }
        $this->sendResult(array(
            "lock" => $lock,
            "lock_uid" => $res['lock_uid'] ?  $res['lock_uid'] : '',
            "lock_username" => $res['lock_username'] ? $res['lock_username'] : '',
            "is_cur_user" => $res['lock_uid'] == $login_user['uid'] ? 1 : 0 ,
        ));
    }

    //设置页面加锁时间
    public function setLock(){
        $page_id = I("page_id/d");
        $lock_to = I("lock_to/d") ? I("lock_to/d") :(time() + 5*60*60 )  ;
        $item_id = I("item_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemEdit($login_user['uid'] , $item_id)) {
            $this->sendError(10103);
            return ;
        }
        D("PageLock")->where( "page_id = '{$page_id}' ")->delete();
        $id = D("PageLock")->add(array(
            "page_id" => $page_id ,
            "lock_uid" => $login_user['uid'] ,
            "lock_username" => $login_user['username'] ,
            "lock_to" => $lock_to ,
            "addtime" => time() ,
        ));
        $now = time() ;
        D("PageLock")->where( "lock_to < '{$now}' ")->delete();
        $this->sendResult(array("id"=>$id));

    }

}
