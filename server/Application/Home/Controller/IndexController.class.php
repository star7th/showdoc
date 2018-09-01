<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends BaseController {
    public function index(){
        $tmp = @file_get_contents('./server/Application/Home/Conf/config.php');
        if (strstr($tmp, "showdoc not install")) {
            header("location:./install/index.php");
            exit();
        }

        //跳转到web目录
        header("location:./web/#/");
        exit();

    	$this->checkLogin(false);
    	$login_user = session("login_user");
    	$this->assign("login_user" ,$login_user);
    	if (LANG_SET == 'en-us') {
    		$demo_url = "https://www.showdoc.cc/demo-en";
    		$help_url = "https://www.showdoc.cc/help-en";
    		$creator_url = "https://github.com/star7th";
    	}
    	else{
    		$demo_url = "https://www.showdoc.cc/demo";
    		$help_url = "https://www.showdoc.cc/help";
    		$creator_url = "https://blog.star7th.com/";
    	}
    	$this->assign("demo_url" ,$demo_url);
    	$this->assign("help_url" ,$help_url);
    	$this->assign("creator_url" ,$creator_url);

        $this->display();
    }
}
