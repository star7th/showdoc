<?php
namespace Home\Controller;
use Think\Controller;
class ItemController extends BaseController {
	//项目列表页
    public function index(){
    	$login_user = $this->checkLogin();        
    	$items  = D("Item")->where("uid = '$login_user[uid]' or item_id in ( select item_id from ".C('DB_PREFIX')."item_member where uid = '$login_user[uid]' ) ")->select();
        
        $share_url = get_domain().__APP__.'/uid/'.$login_user['uid'];

        $this->assign("items" , $items);
        $this->assign("login_user" , $login_user);
    	$this->assign("share_url" , $share_url);
        $this->display();
    }
    //我公开的项目列表
    public function showByUid(){
        $login_user = $this->checkLogin(false); //如果用户有登录，则赋值给$login_user
        $uid = I("uid/d");
        $show_user = D("User")->where(" uid = '$uid' ")->find();
        if ($show_user) {
            $items  = D("Item")->where(" password = '' and  ( uid = '$show_user[uid]' or item_id in ( select item_id from ".C('DB_PREFIX')."item_member where uid = '$show_user[uid]' ) ) ")->select();
            $this->assign("items" , $items);
            $this->assign("show_user" , $show_user);
            $this->assign("login_user" , $login_user);
            
        }
        if (LANG_SET == 'en-us') {
            $help_url = "http://www.showdoc.cc/help-en";
        }
        else{
            $help_url = "http://www.showdoc.cc/help";
        }

        $this->assign("help_url" , $help_url);
        $this->display();

    }

    //新建项目
    public function add(){
    	$login_user = $this->checkLogin();
        $item_id = I("item_id/d");
		if (!IS_POST) {
          $item = D("Item")->where("item_id = '$item_id' ")->find();
          $this->assign("item" , $item);
		  $this->display ();

		}else{
            $item_name = I("item_name");
			$item_domain = I("item_domain");

            if ($item_domain) {
                $item = D("Item")->where("item_domain = '$item_domain' and item_id !='$item_id' ")->find();
                if ($item) {
                    //个性域名已经存在
                    $this->message(L('domain_already_exists'));
                    return false;
                }
                if(!ctype_alnum($item_domain) ||  is_numeric($item_domain) ){
                    //echo '个性域名只能是字母或数字的组合';exit;
                    $this->message(L('item_domain_illegal'));
                    return false;
                }
            }


            $password = I("password");
			$item_description = I("item_description");
            if ($item_id > 0 ) {
                $data = array(
                    "item_name" => $item_name ,
                    "item_domain" => $item_domain ,
                    "password" => $password ,
                    "item_description" => $item_description ,
                    );
                $ret = D("Item")->where("item_id = '$item_id' ")->save($data);
            }else{
                $insert = array(
                    "uid" => $login_user['uid'] ,
                    "username" => $login_user['username'] ,
                    "item_name" => $item_name ,
                    "password" => $password ,
                    "item_description" => $item_description ,
                    "item_domain" => $item_domain ,
                    "addtime" =>time()
                    );
                $ret = D("Item")->add($insert);
            }

			if ($ret) {
				$this->message(L('operation_succeeded'),U('Home/Item/index'));		        
			}else{
				$this->message(L('operation_failed'),U('Home/Item/index'));
			}
		}
    }

    //展示单个项目
    public function show(){
        $this->checkLogin(false);
        $item_id = I("item_id/d");
        $item_domain = I("item_domain/s");
        $current_page_id = I("page_id/d");
        //判断个性域名
        if ($item_domain) {
            $item_domain = mysql_escape_string($item_domain);
            $item = D("Item")->where("item_domain = '$item_domain' ")->find();
            if ($item['item_id']) {
                $item_id = $item['item_id'] ;
            }
        }
        $keyword = I("keyword");
        $login_user = session("login_user");
        $uid = $login_user['uid'] ? $login_user['uid'] : 0 ;
            
        $this->checkItemVisit($uid , $item_id);


        $item = D("Item")->where("item_id = '$item_id' ")->find();

        //是否有搜索词
        if ($keyword) {
            $keyword = mysql_escape_string($keyword);
            $pages = D("Page")->where("item_id = '$item_id' and ( page_title like '%{$keyword}%' or page_content like '%{$keyword}%' ) ")->order(" `s_number` asc  ")->select();
        
        }else{
            //获取所有父目录id为0的页面
            $pages = D("Page")->where("cat_id = '0' and item_id = '$item_id' ")->order(" `s_number` asc  ")->select();
            //获取所有二级目录
            $catalogs = D("Catalog")->where("item_id = '$item_id' and level = 2  ")->order(" `s_number` asc  ")->select();
            if ($catalogs) {
                foreach ($catalogs as $key => &$catalog) {
                    //该二级目录下的所有子页面
                    $temp = D("Page")->where("cat_id = '$catalog[cat_id]' ")->order(" `s_number` asc  ")->select();
                    $catalog['pages'] = $temp ? $temp: array();

                    //该二级目录下的所有子目录
                    $temp = D("catalog")->where("parent_cat_id = '$catalog[cat_id]' ")->order(" `s_number` asc  ")->select();
                    $catalog['catalogs'] = $temp ? $temp: array();
                    if($catalog['catalogs']){
                        //获取所有三级目录的子页面
                        foreach ($catalog['catalogs'] as $key3 => &$catalog3) {
                            //该二级目录下的所有子页面
                            $temp = D("Page")->where("cat_id = '$catalog3[cat_id]' ")->order(" `s_number` asc  ")->select();
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

        if (LANG_SET == 'en-us') {
            $help_url = "http://www.showdoc.cc/help-en";
        }
        else{
            $help_url = "http://www.showdoc.cc/help";
        }

        $this->assign("help_url" , $help_url);
        $this->assign("current_page_id" , $current_page_id);
        $this->assign("keyword" , $keyword);
        $this->assign("ItemPermn" , $ItemPermn);
        $this->assign("ItemCreator" , $ItemCreator);
        $this->assign("share_url" , $share_url);
        $this->assign("catalogs" , $catalogs);
        $this->assign("pages" , $pages);
        $this->assign("item" , $item);
        $this->assign("login_user" , $login_user);
    	$this->display();
    }

    //删除项目
    public function delete(){
        $item_id =  I("item_id");
        $login_user = $this->checkLogin();
        if (!$this->checkItemCreator($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }
        $this->assign("item_id" , $item_id);
        $this->display(); 
    }

    //删除项目
    public function ajaxDelete(){
        $login_user = $this->checkLogin();

        $item_id = I("item_id/d");
        $password = I("password");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();

        if(! D("User")-> checkLogin($item['username'],$password)){
            $return['error_code'] = 10102 ;
            $return['error_message'] = L('incorrect_password') ;
            $this->sendResult($return);
            return ;
        }


        D("Page")->where("item_id = '$item_id' ")->delete();
        D("Catalog")->where("item_id = '$item_id' ")->delete();
        D("PageHistory")->where("item_id = '$item_id' ")->delete();
        $return = D("Item")->where("item_id = '$item_id' ")->delete();

        if (!$return) {
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
        }

        $this->sendResult($return);
    }

    //输入访问密码
    public function pwd(){
        $item_id = I("item_id/d");
        if (!IS_POST) {
          $this->assign("item_id" , $item_id);
          $this->display ();

        }else{
          $password = I("password");
          $v_code = I("v_code");
          if ($v_code && $v_code == session('v_code')) {
            $item = D("Item")->where("item_id = '$item_id' ")->find();
            if ($item['password'] == $password) {
                session("visit_item_".$item_id , 1 );
                header("location:".U("Home/Item/show").'&item_id='.$item_id);
            }else{
                
                $this->message(L('access_password_are_incorrect'));
            }

          }else{
            $this->message(L('verification_code_are_incorrect'));
          }

        }
    }

    //导出word
    public function word(){
        import("Vendor.Parsedown.Parsedown");
        $Parsedown = new \Parsedown();
        $item_id =  I("item_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $item = D("Item")->where("item_id = '$item_id' ")->find();

        //获取所有父目录id为0的页面
        $pages = D("Page")->where("cat_id = '0' and item_id = '$item_id' ")->order(" `s_number` asc  ")->select();
        //获取所有二级目录
        $catalogs = D("Catalog")->where("item_id = '$item_id' and level = 2  ")->order(" `s_number` asc  ")->select();
        if ($catalogs) {
            foreach ($catalogs as $key => &$catalog) {
                //该二级目录下的所有子页面
                $temp = D("Page")->where("cat_id = '$catalog[cat_id]' ")->order(" `s_number` asc  ")->select();
                $catalog['pages'] = $temp ? $temp: array();

                //该二级目录下的所有子目录
                $temp = D("catalog")->where("parent_cat_id = '$catalog[cat_id]' ")->order(" `s_number` asc  ")->select();
                $catalog['catalogs'] = $temp ? $temp: array();
                if($catalog['catalogs']){
                    //获取所有三级目录的子页面
                    foreach ($catalog['catalogs'] as $key3 => &$catalog3) {
                        //该二级目录下的所有子页面
                        $temp = D("Page")->where("cat_id = '$catalog3[cat_id]' ")->order(" `s_number` asc  ")->select();
                        $catalog3['pages'] = $temp ? $temp: array();
                    }                        
                }               
            }
        }

        $data = '';
        $parent = 1;

        if ($pages) {
            foreach ($pages as $key => $value) {
                $data .= "<h1>{$parent}、{$value['page_title']}</h1>";
                $data .= '<div style="margin-left:20px;">';
                    $data .= htmlspecialchars_decode($Parsedown->text($value['page_content']));
                $data .= '</div>';
                $parent ++;
            }
        }
        //var_export($catalogs);
        if ($catalogs) {
            foreach ($catalogs as $key => $value) {
                $data .= "<h1>{$parent}、{$value['cat_name']}</h1>";
                $data .= '<div style="margin-left:20px;">';
                    $child = 1 ;
                    if ($value['pages']) {
                        foreach ($value['pages'] as $page) {
                            $data .= "<h2>{$parent}.{$child}、{$page['page_title']}</h2>";
                            $data .= '<div style="margin-left:20px;">';
                                $data .= htmlspecialchars_decode($Parsedown->text($page['page_content']));
                            $data .= '</div>';
                            $child ++;
                        }
                    }
                    if ($value['catalogs']) {
                        $parent2 = 1 ;
                        foreach ($value['catalogs'] as $key3 => $value3) {
                            $data .= "<h2>{$parent}.{$parent2}、{$value3['cat_name']}</h2>";
                            $data .= '<div style="margin-left:20px;">';
                                $child2 = 1 ;
                                if ($value3['pages']) {
                                    foreach ($value3['pages'] as $page3) {
                                        $data .= "<h3>{$parent}.{$parent2}.{$child2}、{$page3['page_title']}</h3>";
                                        $data .= '<div style="margin-left:30px;">';
                                            $data .= htmlspecialchars_decode($Parsedown->text($page3['page_content']));
                                        $data .= '</div>';
                                        $child2 ++;
                                    }
                                }
                            $data .= '</div>';
                            $parent2 ++;
                        }
                    }
                $data .= '</div>';
                $parent ++;
            }
        }

        output_word($data,$item['item_name']);
    }

}