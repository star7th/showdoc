<?php
namespace Home\Controller;
use Think\Controller;
class PageController extends BaseController {

    //展示某个项目的单个页面
    public function index(){
        import("Vendor.Parsedown.Parsedown");
        $page_id = I("page_id/d");
        $this->assign("page_id" , $page_id);
        $this->display();
    }

    //展示单个页面
    public function single(){
        $page_id = I("page_id/d");

        //跳转到web目录
        header("location:./web/#/page/".$page_id);
        exit();

        import("Vendor.Parsedown.Parsedown");
        $page = D("Page")->where(" page_id = '$page_id' ")->find();
        $login_user = $this->checkLogin(false);
        if (!$this->checkItemVisit($login_user['uid'] , $page['item_id'],$_SERVER['REQUEST_URI'])) {
            $this->message(L('no_permissions'));
            return;
        }

        $ItemPermn = $this->checkItemPermn($login_user['uid'] , $page['item_id']) ;
        $ItemCreator = $this->checkItemCreator($login_user['uid'],$page['item_id']);

        $page['page_md_content'] = $page['page_content'];
        //$page['page_html_content'] = $Parsedown->text(htmlspecialchars_decode($page['page_content']));
        $this->assign("page" , $page);
        $this->assign("page_id" , $page_id);
        $this->assign("login_user" , $login_user);
        $this->display();
    }

    //返回单个页面的源markdown代码
    public function md(){
        $page_id = I("page_id/d");
        $page = D("Page")->where(" page_id = '$page_id' ")->find();
        echo $page['page_content'];
    }

    //编辑页面
    public function edit(){
        $login_user = $this->checkLogin();
        $page_id = I("page_id/d");
        $item_id = I("item_id/d");

        $page_history_id = I("page_history_id/d");
        $copy_page_id = I("copy_page_id/d");

        if ($page_id > 0 ) {
            if ($page_history_id) {
                $page = D("PageHistory")->where(" page_history_id = '$page_history_id' ")->find();
                $page_content = gzuncompress(base64_decode($page['page_content'])); 
                $page['page_content'] = $page_content ? $page_content : $page['page_content'] ;
            }else{
                $page = D("Page")->where(" page_id = '$page_id' ")->find();
            }
            $default_cat_id = $page['cat_id'];
        }
        //如果是复制接口
        elseif ($copy_page_id) {
            $copy_page = D("Page")->where(" page_id = '$copy_page_id' ")->find();
            $page['page_title'] = $copy_page['page_title']."-copy";
            $page['page_content'] = $copy_page['page_content'];
            $page['item_id'] = $copy_page['item_id'];
            $default_cat_id = $copy_page['cat_id'];

        }else{
            //查找用户上一次设置的目录
            $last_page = D("Page")->where(" author_uid ='$login_user[uid]' and $item_id = '$item_id' ")->order(" addtime desc ")->limit(1)->find();
            $default_cat_id = $last_page['cat_id'];


        }

        $item_id = $page['item_id'] ?$page['item_id'] :$item_id;

        
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $Catalog = D("Catalog")->where(" cat_id = '$default_cat_id' ")->find();
        if ($Catalog['parent_cat_id']) {
            $default_second_cat_id = $Catalog['parent_cat_id'];
            $default_child_cat_id = $default_cat_id;

        }else{
            $default_second_cat_id = $default_cat_id;
        }
        $this->assign("api_doc_templ" , 'MdTemplate/api-doc.'.LANG_SET);
        $this->assign("database_doc_templ" , 'MdTemplate/database.'.LANG_SET);
        $this->assign("page" , $page);
        $this->assign("item_id" , $item_id);
        $this->assign("default_second_cat_id" , $default_second_cat_id);
        $this->assign("default_child_cat_id" , $default_child_cat_id);


        $this->display();        
    }


    //历史版本
    public function history(){
        $page_id = I("page_id/d") ? I("page_id/d") : 0 ;
        $this->assign("page_id" , $page_id);

        $PageHistory = D("PageHistory")->where("page_id = '$page_id' ")->order(" addtime desc")->limit(10)->select();

        if ($PageHistory) {
            foreach ($PageHistory as $key => &$value) {
                $page_content = gzuncompress(base64_decode($value['page_content'])); 
                $value['page_content'] = $page_content ? $page_content : $value['page_content'] ;
                $value['addtime'] = date("Y-m-d H:i:s" , $value['addtime']);
            }
        }

        $this->assign("PageHistory" , $PageHistory);

        $this->display();        

    }

    //上传图片
    public function uploadImg(){
        $qiniu_config = C('UPLOAD_SITEIMG_QINIU') ;
        if ($_FILES['editormd-image-file']['name'] == 'blob') {
            $_FILES['editormd-image-file']['name'] .= '.jpg';
        }
        if (strstr(strtolower($_FILES['editormd-image-file']['name']), ".php") ) {
            return false;
        }
        if (!empty($qiniu_config['driverConfig']['secrectKey'])) {
          //上传到七牛
          $Upload = new \Think\Upload(C('UPLOAD_SITEIMG_QINIU'));
          $info = $Upload->upload($_FILES);
          $url = $info['editormd-image-file']['url'] ;
          echo json_encode(array("url"=>$url,"success"=>1));
        }else{
            $upload = new \Think\Upload();// 实例化上传类
            $upload->maxSize  = 3145728 ;// 设置附件上传大小
            $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
            $upload->rootPath = './Public/Uploads/';// 设置附件上传目录
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

    public function diff(){
        $login_user = $this->checkLogin();
        $page_history_id = I("page_history_id/d");
        $page_id = I("page_id/d");

        $page = D("Page")->where(" page_id = '$page_id' ")->find();
        $cur_page_content = $page['page_content'];

        $item_id = $page['item_id'] ?$page['item_id'] :$item_id;

        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $page = D("PageHistory")->where(" page_history_id = '$page_history_id' ")->find();
        $page_content = gzuncompress(base64_decode($page['page_content'])); 
        $history_page_content = $page_content ? $page_content : $page['page_content'] ;
        
        $this->assign("cur_page_content" , $cur_page_content);
        $this->assign("history_page_content" , $history_page_content);
        $this->display(); 
    }
}
