<?php
namespace Home\Controller;
use Think\Controller;
class PageController extends BaseController {

    //展示某个项目的单个页面
    public function index(){
        import("Vendor.Parsedown.Parsedown");
        $page_id = I("page_id/d");
        $page = D("Page")->where(" page_id = '$page_id' ")->find();
        $login_user = $this->checkLogin(false);
        if (!$this->checkItemVisit($login_user['uid'] , $page['item_id'])) {
            $this->message(L('no_permissions'));
            return;
        }

        $ItemPermn = $this->checkItemPermn($login_user['uid'] , $page['item_id']) ;
        $ItemCreator = $this->checkItemCreator($login_user['uid'],$page['item_id']);
		$page['page_md_content'] = $page['page_content'];

        //$Parsedown = new \Parsedown();
        //$page['page_content'] = $Parsedown->text(htmlspecialchars_decode($page['page_content']));
        $this->assign("page" , $page);
        $this->display();
    }

    //展示单个页面
    public function single(){
        import("Vendor.Parsedown.Parsedown");
        $page_id = I("page_id/d");
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
            $this->message(L('no_permissions'));
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

    //删除页面
    public function delete(){
        $page_id = I("page_id/d")? I("page_id/d") : 0;
        $page = D("Page")->where(" page_id = '$page_id' ")->find();

        $login_user = $this->checkLogin();
        if (!$this->checkItemCreator($login_user['uid'] , $page['item_id']) && $login_user['uid'] != $page['author_uid']) {
            $this->message(L('no_permissions_to_delete_page',array("author_username"=>$page['author_username'])));
            return;
        }

        if ($page) {
            
            $ret = D("Page")->where(" page_id = '$page_id' ")->delete();
            //更新项目时间
            D("Item")->where(" item_id = '$page[item_id]' ")->save(array("last_update_time"=>time()));

        }
        if ($ret) {
           $this->message(L('delete_succeeded'),U("Home/item/show?item_id={$page['item_id']}"));
        }else{
           $this->message(L('delete_failed'),U("Home/item/show?item_id={$page['item_id']}"));
        }
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
