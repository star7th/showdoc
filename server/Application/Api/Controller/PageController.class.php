<?php
namespace Api\Controller;
use Think\Controller;
class PageController extends BaseController {

    //页面详情
    public function info(){
        $page_id = I("page_id/d");
        $page = D("Page")->where(" page_id = '$page_id' ")->find();
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
        $page = $page ? $page : array();
        if ($page) {
           //unset($page['page_content']);
           $page['addtime'] = date("Y-m-d H:i:s",$page['addtime']);
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
            
            $ret = D("Page")->where(" page_id = '$page_id' ")->delete();
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
        
        if (strstr(strtolower($_FILES['editormd-image-file']['name']), ".php") ) {
            return false;
        }

        $qiniu_config = C('UPLOAD_SITEIMG_QINIU') ;
        if (!empty($qiniu_config['driverConfig']['secrectKey'])) {
          //上传到七牛
          $Upload = new \Think\Upload(C('UPLOAD_SITEIMG_QINIU'));
          $info = $Upload->upload($_FILES);
          $url = $info['editormd-image-file']['url'] ;
          if ($ret) {
              echo json_encode(array("url"=>$url,"success"=>1));
          }
        }else{
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize  = 3145728 ;// 设置附件上传大小
            $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->rootPath = './../Public/Uploads/';// 设置附件上传目录
            $upload->savePath = '';// 设置附件上传子目录
            $info = $upload->upload() ;
            if(!$info) {// 上传错误提示错误信息
              $this->error($upload->getError());
              return;
            }else{// 上传成功 获取上传文件信息
              $url = get_domain().__ROOT__.substr($upload->rootPath,1).$info['editormd-image-file']['savepath'].$info['editormd-image-file']['savename'] ;
              echo json_encode(array("url"=>$url,"success"=>1));
            }
        }

    }

}