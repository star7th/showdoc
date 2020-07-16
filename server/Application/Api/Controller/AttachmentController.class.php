<?php
//附件/图片等等
namespace Api\Controller;
use Think\Controller;
class AttachmentController extends BaseController {

    public function index(){
        
        echo 'Attachment';
       
    }

    //浏览附件
    public function visitFile(){
      $sign = I("sign");
      $imageView2 = I("imageView2");
      $d = D("UploadFile") ;
      $ret = $d->where(" sign = '%s' ",array($sign))->find();
      if ($ret) {
            $beyond_the_quota = 0 ;
            $days = ceil(( time() -$ret['addtime'])/86400);//自添加图片以来的天数
            $adv_day_times = $ret['visit_times'] / $days  ; //平均每天的访问次数
            $flow_rate = ( $ret['file_size'] * $ret['visit_times'] ) / $days ; //日均流量


            //如果是apk文件且在微信浏览器中打开
            if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false  && strpos($ret['real_url'] , '.apk') !== false ) {
                header("Content-type: text/html; charset=utf-8"); 
                echo "<head><title>温馨提示</title></head>";
                echo "<br><h1>微信不支持直接下载，请点击右上角“---”在外部浏览器中打开</h1>";
                return ;

            }

          $d->where(" sign = '%s' ",array($sign))->save(array("visit_times" => $ret['visit_times'] + 1  ,"last_visit_time"=>time()));
            //记录用户流量
            D("Attachment")->recordUserFlow($ret['uid'] , $ret['file_size']) ;

            //$ret['cache_url'] = '' ; //把这个变量赋值为空，禁用掉cache_url;
            if ($ret['cache_url']) {
                $url = $ret['cache_url'] ;
            }else{
                $url = $ret['real_url']  ;
            }

        header("location:{$url}");
      }else{
        echo "www.showdoc.cc";
      }
    }

    //上传图片
    public function uploadImg(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d") ? I("item_id/d") : 0 ;
        $page_id = I("page_id/d") ? I("page_id/d") : 0 ;

        
        if ($_FILES['editormd-image-file']['name'] == 'blob') {
            $_FILES['editormd-image-file']['name'] .= '.jpg';
        }
        
        if (!$_FILES['editormd-image-file']) {
           return false;
        }
        
        if (strstr(strip_tags(strtolower($_FILES['editormd-image-file']['name'])), ".php") ) {
            return false;
        }

        $oss_open = D("Options")->get("oss_open" ) ;
        if ($oss_open) {
            $uploadFile = $_FILES['editormd-image-file'] ;
            $url = upload_oss($uploadFile);
            if ($url) {
                $sign = md5($url.time().rand()) ;
                $insert = array(
                "sign" => $sign,
                "uid" => $login_user['uid'],
                "item_id" => $item_id,
                "page_id" => $page_id,
                "display_name" => $uploadFile['name'],
                "file_type" => $uploadFile['type'],
                "file_size" => $uploadFile['size'],
                "real_url" => $url,
                "addtime" => time(),
                );
                $ret = D("UploadFile")->add($insert);
                $url = get_domain().U("api/attachment/visitFile",array("sign" => $sign))."&showdoc=.jpg"; 
                echo json_encode(array("url"=>$url,"success"=>1));
            }
            return ;
        }

        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize  = 1003145728 ;// 设置附件上传大小
        $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './../Public/Uploads/';// 设置附件上传目录
        $upload->savePath = '';// 设置附件上传子目录
        $info = $upload->uploadOne($_FILES['editormd-image-file']) ;
        if(!$info) {// 上传错误提示错误信息
          $this->error($upload->getError());
          return;
        }else{// 上传成功 获取上传文件信息
          $url = get_domain().__ROOT__.substr($upload->rootPath,1).$info['savepath'].$info['savename'] ;
          $sign = md5($url.time().rand()) ;
          $uploadFile = $_FILES['editormd-image-file'] ;
          $insert = array(
            "sign" => $sign,
            "uid" => $login_user['uid'],
            "item_id" => $item_id,
            "page_id" => $page_id,
            "display_name" => $uploadFile['name'],
            "file_type" => $uploadFile['type'],
            "file_size" => $uploadFile['size'],
            "real_url" => $url,
            "addtime" => time(),
            );
          $ret = D("UploadFile")->add($insert);
          $url = get_domain().U("api/attachment/visitFile",array("sign" => $sign))."&showdoc=.jpg";
          echo json_encode(array("url"=>$url,"success"=>1));
        }

    }

    //页面的上传附件
    public function pageAttachmentUpload(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d") ? I("item_id/d") : 0 ;
        $page_id = I("page_id/d") ? I("page_id/d") : 0 ;
        $uploadFile = $_FILES['file'] ;
 
        if (!$page_id) {
            $this->sendError(10103,"请至少先保存一次页面内容");
            return;
        }
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->sendError(10103);
            return;
        }
        
        if (!$uploadFile) {
           return false;
        }
        
        if (strstr(strip_tags(strtolower($uploadFile['name'])), ".php") ) {
            return false;
        }

        $oss_open = D("Options")->get("oss_open" ) ;
        if ($oss_open) {
            $url = upload_oss($uploadFile);
            if ($url) {
                $sign = md5($url.time().rand()) ;
                $insert = array(
                "sign" => $sign,
                "uid" => $login_user['uid'],
                "item_id" => $item_id,
                "page_id" => $page_id,
                "display_name" => $uploadFile['name'],
                "file_type" => $uploadFile['type'],
                "file_size" => $uploadFile['size'],
                "real_url" => $url,
                "addtime" => time(),
                );
                $ret = D("UploadFile")->add($insert);
                $url = get_domain().U("api/attachment/visitFile",array("sign" => $sign)); 
                echo json_encode(array("url"=>$url,"success"=>1));
            }
            return ;
        }

        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize  = 4145728000 ;// 设置附件上传大小
        $upload->rootPath = './../Public/Uploads/';// 设置附件上传目录
        $upload->savePath = '';// 设置附件上传子目录
        $info = $upload->uploadOne($uploadFile) ;
        if(!$info) {// 上传错误提示错误信息
          $this->error($upload->getError());
          return;
        }else{// 上传成功 获取上传文件信息
          $url = get_domain().__ROOT__.substr($upload->rootPath,1).$info['savepath'].$info['savename'] ;
          $sign = md5($url.time().rand()) ;
          $insert = array(
            "sign" => $sign,
            "uid" => $login_user['uid'],
            "item_id" => $item_id,
            "page_id" => $page_id,
            "display_name" => $uploadFile['name'],
            "file_type" => $uploadFile['type'],
            "file_size" => $uploadFile['size'],
            "real_url" => $url,
            "addtime" => time(),
            );
          $ret = D("UploadFile")->add($insert);
          $url = get_domain().U("api/attachment/visitFile",array("sign" => $sign));
          echo json_encode(array("url"=>$url,"success"=>1));
        }

    }
    //页面的上传附件列表
    public function pageAttachmentUploadList(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d") ? I("item_id/d") : 0 ;
        $page_id = I("page_id/d") ? I("page_id/d") : 0 ;
        if (!$page_id) {
            $this->sendError(10103,"请至少先保存一次页面内容");
            return;
        }
        $return = array() ;
        $files = D("UploadFile")->where("page_id = '$page_id' ")->order("addtime desc")->select();
        if ($files) {
            $item_id = $files[0]['item_id'] ;
            if (!$this->checkItemVisit($login_user['uid'] , $item_id)) {
                $this->sendError(10103);
                return;
            }
            foreach ($files as $key => $value) {
                $url = '';
                if($value['sign']){
                   $url =  get_domain().U("api/attachment/visitFile",array("sign" => $value['sign'])) ;
                }else{
                  $url =  $value['real_url'] ;
                }
                $return[] = array(
                    "file_id"=>$value['file_id'],
                    "display_name"=>$value['display_name'],
                    "url"=>$url,
                    "addtime"=> date("Y-m-d H:i:s" , $value['addtime'] ),
                    );
            }

        }
        $this->sendResult($return);

    }

    //删除页面中已上传文件
    public function deletePageUploadFile(){
        $login_user = $this->checkLogin();
        $file_id = I("file_id/d") ? I("file_id/d") : 0 ;

        $file = D("UploadFile")->where("file_id = '$file_id' ")->find();
        $item_id = $file['item_id'] ;
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->sendError(10103);
            return;
        }
        $ret = D("Page")->deleteFile($file_id);
        if ($ret) {
            $this->sendResult(array());
        }else{
            $this->sendError(10101,"删除失败");
        }
    }

    //获取全站的附件列表。给管理员查看附件用
    public function getAllList(){
        $login_user = $this->checkLogin();
        $this->checkAdmin(); //重要，校验管理员身份
        $page = I("page/d");
        $count = I("count/d");
        $attachment_type = I("attachment_type/d");
        $display_name = I("display_name");
        $username = I("username");
        $return = array() ;
        $where = ' 1 = 1 ';
        if($attachment_type == 1 ){
            $where .=" and file_type like '%image%' " ;
        }
        if($attachment_type == 2 ){
            $where .=" and file_type not like '%image%' " ;
        }
        if($display_name){
            $display_name =  \SQLite3::escapeString($display_name) ;
            $where .=" and display_name  like '%{$display_name}%' " ;
        }
        if($username){
            $username =  \SQLite3::escapeString($username) ;
            $uid = D("User")->where(" username = '{$username}' ")->getField('uid') ;
            $uid = $uid ? $uid  : -99 ;
            $where .=" and uid  = '{$uid}' " ;
        }
        $files = D("UploadFile")->where($where)->order("addtime desc")->page($page ,$count)->select();
        if ($files) {
            foreach ($files as $key => $value) {
                $username = '';
                if($value['uid']){
                    $username = D("User")->where(" uid = {$value['uid']} ")->getField('username') ;
                }
                $url = '';
                if($value['sign']){
                   $url =  get_domain().U("api/attachment/visitFile",array("sign" => $value['sign'])) ;
                }else{
                  $url =  $value['real_url'] ;
                }
                $return['list'][] = array(
                    "file_id"=>$value['file_id'],
                    "username"=>$username,
                    "uid"=>$value['uid'],
                    "file_type"=>$value['file_type'],
                    "visit_times"=>$value['visit_times'],
                    "file_size"=>$value['file_size'],
                    "item_id"=>$value['item_id'],
                    "page_id"=>$value['page_id'],
                    "file_size_m"=>round( $value['file_size']/(1024*1024),3),
                    "display_name"=>$value['display_name']?$value['display_name']:'',
                    "url"=>$url ,
                    "addtime"=> date("Y-m-d H:i:s" , $value['addtime'] ),
                    "last_visit_time"=> date("Y-m-d H:i:s" , $value['last_visit_time'] ),
                    );
            }

        }
        $return['total'] = D("UploadFile")->where($where)->count();
        $used = D("UploadFile")->where($where)->getField('sum(file_size)');
        $return['used'] = $used ;
        $return['used_m'] = round( $used/(1024*1024),3) ;
        $this->sendResult($return);
    }

    //删除附件
    public function deleteAttachment(){
        $login_user = $this->checkLogin();
        $this->checkAdmin(); //重要，校验管理员身份
        $file_id = I("file_id/d") ? I("file_id/d") : 0 ;

        $file = D("UploadFile")->where("file_id = '$file_id' ")->find();

        $ret = D("Page")->deleteFile($file_id);
        if ($ret) {
            $this->sendResult(array());
        }else{
            $this->sendError(10101,"删除失败");
        }
    }

    //获取我的附件列表
    public function getMyList(){
        $login_user = $this->checkLogin();
        $page = I("page/d");
        $count = I("count/d");
        $attachment_type = I("attachment_type/d");
        $display_name = I("display_name");
        $username = I("username");
        $return = array() ;
        $where = " uid  = '{$login_user[uid]}' ";
        if($attachment_type == 1 ){
            $where .=" and file_type like '%image%' " ;
        }
        if($attachment_type == 2 ){
            $where .=" and file_type not like '%image%' " ;
        }
        if($display_name){
            $display_name =  \SQLite3::escapeString($display_name) ;
            $where .=" and display_name  like '%{$display_name}%' " ;
        }
        $files = D("UploadFile")->where($where)->order("addtime desc")->page($page ,$count)->select();
        if ($files) {
            foreach ($files as $key => $value) {
                $username = '';
                $return['list'][] = array(
                    "file_id"=>$value['file_id'],
                    "uid"=>$value['uid'],
                    "file_type"=>$value['file_type'],
                    "visit_times"=>$value['visit_times'],
                    "file_size"=>$value['file_size'],
                    "item_id"=>$value['item_id'],
                    "page_id"=>$value['page_id'],
                    "file_size_m"=>round( $value['file_size']/(1024*1024),3),
                    "display_name"=>$value['display_name']?$value['display_name']:'',
                    "url"=>get_domain().U("api/attachment/visitFile",array("sign" => $value['sign'])),
                    "addtime"=> date("Y-m-d H:i:s" , $value['addtime'] ),
                    "last_visit_time"=> date("Y-m-d H:i:s" , $value['last_visit_time'] ),
                    );
            }

        }
        $return['total'] = D("UploadFile")->where($where)->count();
        $used = D("UploadFile")->where($where)->getField('sum(file_size)');
        $return['used'] = $used ;
        $return['used_m'] = round( $used/(1024*1024),3) ;
        $used_flow =  D("Attachment")->getUserFlow($login_user['uid']) ; ; //该用户的本月使用流量
        $return['used_flow_m'] = round( $used_flow/(1024*1024),3) ;

        $this->sendResult($return);
    }

    //删除附件
    public function deleteMyAttachment(){
        $login_user = $this->checkLogin();
        $file_id = I("file_id/d") ? I("file_id/d") : 0 ;

        $file = D("UploadFile")->where("file_id = '$file_id' and uid ='$login_user[uid]' ")->find();

        if($file){
            $ret = D("Page")->deleteFile($file_id);
            if ($ret) {
                $this->sendResult(array());
                return ;
            }
        }
        $this->sendError(10101,"删除失败");
    }

}
