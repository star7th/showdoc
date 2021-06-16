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

        $array = explode("/Public/Uploads/", $url) ;
        $file_path = "../Public/Uploads/".$array[1] ;
        $oss_open = D("Options")->get("oss_open" ) ;
        if (!$oss_open 
            && file_exists($file_path)
            && $ret['display_name']
            && !strstr(strtolower($file_path),'.bmp')
            && !strstr(strtolower($file_path),'.jpg')
            && !strstr(strtolower($file_path),'.png')
            && !strstr(strtolower($file_path),'.pdf')
         ) {
                $this->_downloadFile($file_path, $ret['display_name']);
        }else{
                header("location:{$url}");
            }
        
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
        
        if (strstr(strip_tags(strtolower($_FILES['editormd-image-file']['name'])), ".php") || strstr(strip_tags(strtolower($_FILES['editormd-image-file']['name'])), ".htm") ) {
            return false;
        }

        $url = D("Attachment")->upload($_FILES , 'editormd-image-file' , $login_user['uid'] , $item_id , $page_id ) ;
        if ($url) {
            echo json_encode(array("url"=>$url,"success"=>1));
        }

    }

    //上传附件
    public function attachmentUpload(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d") ? I("item_id/d") : 0 ;
        $page_id = I("page_id/d") ? I("page_id/d") : 0 ;
        $uploadFile = $_FILES['file'] ;
 
        // 如果附件是要上传绑定到某个页面，那么检验项目权限。如果不绑定，只是上传到自己的文件库，则不需要校验项目权限
        if( $page_id > 0 || $item_id > 0){
            if (!$this->checkItemEdit($login_user['uid'] , $item_id)) {
                $this->sendError(10103);
                return;
            }
        }
        
        if (!$uploadFile) {
           return false;
        }
        
        if (strstr(strip_tags(strtolower($uploadFile['name'])), ".php") || strstr(strip_tags(strtolower($uploadFile['name'])), ".htm") ) {
            $this->sendError(10100,'不支持此文件类型');
            return false;
        }

        $url = D("Attachment")->upload($_FILES , 'file' , $login_user['uid'] , $item_id , $page_id ) ;
        if ($url) {
            echo json_encode(array("url"=>$url,"success"=>1));
        }

    }
    //页面的上传附件列表
    public function pageAttachmentUploadList(){
        $login_user = $this->checkLogin(false);
        $item_id = I("item_id/d") ? I("item_id/d") : 0 ;
        $page_id = I("page_id/d") ? I("page_id/d") : 0 ;
        if (!$page_id) {
            $this->sendError(10103,"请至少先保存一次页面内容");
            return;
        }
        $return = array() ;
        $files = D("UploadFile")->join(" file_page on file_page.file_id = upload_file.file_id")->field("upload_file.* , file_page.item_id as item_id ,file_page.page_id as page_id  ")->where("file_page.page_id = '$page_id' ")->order("file_page.addtime desc")->select();
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
        $page_id = I("page_id/d") ? I("page_id/d") : 0 ;
        $count = D("FilePage")->where(" file_id = '$file_id' and page_id > 0   ")->count() ;
        if($count <= 1 ){
            $this->deleteMyAttachment();
        }else{
            $page = M("Page")->where(" page_id = '$page_id' ")->find();
            if (!$this->checkItemEdit($login_user['uid'] , $page['item_id'])) {
                $this->sendError(10103);
                return;
            }
            $res = D("FilePage")->where(" file_id = '$file_id' and page_id = '$page_id'   ")->delete() ;
            if($res){
                $this->sendResult(array());
            }else{
                $this->sendError(10101,"删除失败");
            }

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

        $ret = D("Attachment")->deleteFile($file_id);
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
        $where = " uid  = {$login_user['uid']} ";
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

    //将已上传文件绑定到页面中
    public function bindingPage(){
        $login_user = $this->checkLogin();
        $file_id = I("file_id/d") ? I("file_id/d") : 0 ;
        $page_id = I("page_id/d");
        $file = D("UploadFile")->where("file_id = '$file_id' and uid ='$login_user[uid]' ")->find();
        $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if (!$this->checkItemEdit($login_user['uid'] , $page['item_id'])) {
            $this->sendError(10103);
            return;
        }
        $insert = array(
            "file_id" => $file_id,
            "item_id" => $page['item_id'] ,
            "page_id" => $page_id,
            "addtime" => time(),
            );
        $ret = D("FilePage")->add($insert);
        if( $ret){
            $this->sendResult(array());
        }else{
            $this->sendError(10101);
        }

    }

    //输出本地文件到浏览器
    public function _downloadFile($filename, $rename='showdoc') {

    
        //设置脚本的最大执行时间，设置为0则无时间限制
        set_time_limit(3000);
        ini_set('max_execution_time', '0');
    
        //通过header()发送头信息
        //因为不知道文件是什么类型的，告诉浏览器输出的是字节流
        header('content-type:application/octet-stream');
    
        //告诉浏览器返回的文件大小类型是字节
        header('Accept-Ranges:bytes');
    
        //获得文件大小
        $filesize = filesize($filename);//(此方法无法获取到远程文件大小)，远程文件用下面get_headers方法
        //$header_array = get_headers($filename, true);
        //$filesize = $header_array['Content-Length'];
        //var_dump($header_array);exit();
        //告诉浏览器返回的文件大小
        header('Accept-Length:'.$filesize);
        //告诉浏览器文件作为附件处理并且设定最终下载完成的文件名称
        header('content-disposition:attachment;filename='.basename($rename));
    
        //针对大文件，规定每次读取文件的字节数为4096字节，直接输出数据
        $read_buffer = 4096;
        $handle = fopen($filename, 'rb');
        //总的缓冲的字节数
        $sum_buffer = 0;
        //只要没到文件尾，就一直读取
        while(!feof($handle) && $sum_buffer<$filesize) {
            echo fread($handle,$read_buffer);
            $sum_buffer += $read_buffer;
        }
    
        //关闭句柄
        fclose($handle);

    }

}
