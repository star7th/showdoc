<?php
namespace Home\Controller;
use Think\Controller;
class ItemController extends BaseController {
	//项目列表页
    public function index(){
    	$login_user = $this->checkLogin();
    	$items  = D("Item")->where("uid = '$login_user[uid]' or item_id in ( select item_id from item_member where uid = '$login_user[uid]' ) ")->select();

        $this->assign("items" , $items);
    	$this->assign("login_user" , $login_user);
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
            $password = I("password");
			$item_description = I("item_description");
            if ($item_id > 0 ) {
                $data = array(
                    "item_name" => $item_name ,
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
                    "addtime" =>time()
                    );
                $ret = D("Item")->add($insert);
            }

			if ($ret) {
				$this->message("操作成功！",U('Home/Item/index'));		        
			}else{
				$this->message("操作失败！",U('Home/Item/index'));
			}
		}
    }

    //展示单个项目
    public function show(){
        $this->checkLogin(false);
        $item_id = I("item_id/d");
        $keyword = I("keyword");
        $login_user = session("login_user");
        $uid = $login_user['uid'] ? $login_user['uid'] : 0 ;
            
        $this->checkItemVisit($uid , $item_id);


        $item = D("Item")->where("item_id = '$item_id' ")->find();

        //是否有搜索词
        if ($keyword) {
            $keyword = mysql_escape_string($keyword);
            $pages = D("Page")->where("item_id = '$item_id' and ( page_title like '%{$keyword}%' or page_content like '%{$keyword}%' ) ")->order(" `order` asc  ")->select();
        
        }else{
            //获取所有父目录id为0的页面
            $pages = D("Page")->where("cat_id = '0' and item_id = '$item_id' ")->order(" `order` asc  ")->select();
            //获取所有目录
            $catalogs = D("Catalog")->where("item_id = '$item_id' ")->order(" `order` asc  ")->select();
            if ($catalogs) {
                foreach ($catalogs as $key => &$catalog) {
                    $temp = D("Page")->where("cat_id = '$catalog[cat_id]' ")->order(" `order` asc  ")->select();
                    $catalog['pages'] = $temp ? $temp: array();
                }
            }
        }



        $share_url = get_domain().__APP__.'/'.$item_id;

        $ItemPermn = $this->checkItemPermn($uid , $item_id) ;

        $ItemCreator = $this->checkItemCreator($uid , $item_id);

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
            $this->message("你无权限");
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
            $return['error_message'] = '密码错误' ;
            $this->sendResult($return);
            return ;
        }


        D("Page")->where("item_id = '$item_id' ")->limit(1000)->delete();
        D("Catalog")->where("item_id = '$item_id' ")->limit(100)->delete();
        D("PageHistory")->where("item_id = '$item_id' ")->limit(1000)->delete();
        $return = D("Item")->where("item_id = '$item_id' ")->limit(1)->delete();

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
                header("location:".U("Home/item/show").'?item_id='.$item_id);
            }else{
                
                $this->message("访问密码不正确");
            }

          }else{
            $this->message("验证码不正确");
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
            $this->message("你无权限");
            return;
        }

        $item = D("Item")->where("item_id = '$item_id' ")->find();
        //获取所有父目录id为0的页面
        $pages = D("Page")->where("cat_id = '0' and item_id = '$item_id' ")->order(" `order` asc  ")->select();
        //获取所有目录
        $catalogs = D("Catalog")->where("item_id = '$item_id' ")->order(" `order` asc  ")->select();
        if ($catalogs) {
            foreach ($catalogs as $key => &$catalog) {
                $temp = D("Page")->where("cat_id = '$catalog[cat_id]' ")->order(" `order` asc  ")->select();
                $catalog['pages'] = $temp ? $temp: array();
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
                $data .= '</div>';
                $parent ++;
            }
        }

        output_word($data,$item['item_name']);
    }

}