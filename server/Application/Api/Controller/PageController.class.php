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
           $page['attachment_count'] = D("UploadFile")->where("page_id = '$page_id' ")->count();

           $singlePage = M("SinglePage")->where(" page_id = '%d' ",array($page_id))->limit(1)->find();
           if ($singlePage) {
                $page['unique_key'] =  $singlePage['unique_key'] ;
           }else{
                $page['unique_key'] = '' ;
           }

        }
        $this->sendResult($page);
    }
    //删除页面
    public function delete(){
        $page_id = I("page_id/d")? I("page_id/d") : 0;
        $page = D("Page")->where(" page_id = '$page_id' ")->find();

        $login_user = $this->checkLogin();
        if (!$this->checkItemCreator($login_user['uid'] , $page['item_id']) && $login_user['uid'] != $page['author_uid']) {
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
        $s_number = I("s_number/d")? I("s_number/d") : 99;

        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
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
        $data['s_number'] = $s_number ;
        $data['item_id'] = $item_id ;
        $data['cat_id'] = $cat_id ;
        $data['addtime'] = time();
        $data['author_uid'] = $login_user['uid'] ;
        $data['author_username'] = $login_user['username'];

        if ($page_id > 0 ) {
            
            //在保存前先把当前页面的版本存档
            $page = D("Page")->where(" page_id = '$page_id' ")->find();
            if (!$this->checkItemPermn($login_user['uid'] , $page['item_id'])) {
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
            if ($Count > 20 ) {
               //每个单页面只保留最多20个历史版本
               $ret = D("PageHistory")->where(" page_id = '$page_id' ")->limit("20")->order("page_history_id desc")->select();
               D("PageHistory")->where(" page_id = '$page_id' and page_history_id < ".$ret[19]['page_history_id'] )->delete();
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

        $PageHistory = D("PageHistory")->where("page_id = '$page_id' ")->order(" addtime desc")->limit(10)->select();

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
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d") ? I("item_id/d") : 0 ;
        $page_id = I("page_id/d") ? I("page_id/d") : 0 ;

        
        if ($_FILES['editormd-image-file']['name'] == 'blob') {
            $_FILES['editormd-image-file']['name'] .= '.jpg';
        }
        
        if (!$_FILES['editormd-image-file']) {
           return false;
        }
        
        if (strstr(strtolower($_FILES['editormd-image-file']['name']), ".php") ) {
            return false;
        }

        $qiniu_config = C('UPLOAD_SITEIMG_QINIU') ;
        if (!empty($qiniu_config['driverConfig']['secrectKey'])) {
          //上传到七牛
          $Upload = new \Think\Upload(C('UPLOAD_SITEIMG_QINIU'));
          $info = $Upload->upload($_FILES);
          $url = $info['editormd-image-file']['url'] ;
          if ($url) {
              echo json_encode(array("url"=>$url,"success"=>1));
          }
        }else{
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
              echo json_encode(array("url"=>$url,"success"=>1));
            }
        }

    }

    //上传附件
    public function upload(){
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
        
        if (strstr(strtolower($uploadFile['name']), ".php") ) {
            return false;
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
          $insert = array(
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

          echo json_encode(array("url"=>$url,"success"=>1));
        }

    }

    public function uploadList(){
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
                $return[] = array(
                    "file_id"=>$value['file_id'],
                    "display_name"=>$value['display_name'],
                    "url"=>$value['real_url'],
                    "addtime"=> date("Y-m-d H:i:s" , $value['addtime'] ),
                    );
            }

        }
        $this->sendResult($return);

    }

    //删除已上传文件
    public function deleteUploadFile(){
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
        if (!$this->checkItemPermn($login_user['uid'] , $page['item_id'])) {
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
           $page['attachment_count'] = D("UploadFile")->where("page_id = '$page_id' ")->count();

        }
        $this->sendResult($page);
    }


}
