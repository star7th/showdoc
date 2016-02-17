<?php
namespace Home\Controller;
use Think\Controller;
class UserController extends BaseController {


	//注册
	public function register(){
		if (!IS_POST) {
			  $this->display ();
			}else{
			  $username = I("username");
			  $password = I("password");
			  $confirm_password = I("confirm_password");
			  $v_code = I("v_code");
			  if ($v_code && $v_code == session('v_code')) {
			  	if ( $password != '' && $password == $confirm_password) {

			  		if ( ! D("User")->isExist($username) ) {
						$ret = D("User")->register($username,$password);
						if ($ret) {
					      $this->message("注册成功！",U('Home/User/login'));					    
						}else{
						  $this->message("用户名或密码不正确");
						}
			  		}else{
			  			$this->message("用户名已经存在啦！");
			  		}

			  	}else{
			  		$this->message("两次输入的密码不一致！");
			  	}
			  }else{
			    $this->message("验证码不正确");
			  }

			}
	}



	//登录
	public function login()
	{
		if (!IS_POST) {
			//如果有cookie记录，则自动登录
			$cookie_token = cookie('cookie_token');
			if ($cookie_token) {
				$ret = D("User")->where("cookie_token = '%s' ",array($cookie_token))->find();
				if ($ret && $ret['cookie_token_expire'] > time() ) {
					$login_user = $ret ;
					session("login_user" , $login_user);
					$this->message("自动登录成功！正在跳转...",U('Home/Item/index'));
					exit();
				}
			}
		  $this->display ();

		}else{
		  $username = I("username");
		  $password = I("password");
		  $v_code = I("v_code");
		  if ($v_code && $v_code == session('v_code')) {
		    $ret = D("User")->checkLogin($username,$password);
		    if ($ret) {
		      session("login_user" , $ret );
		      $cookie_token = md5(time().rand().'efeffthdh');
		      $cookie_token_expire = time() + 60*60*24*90 ;
	          cookie('cookie_token',$cookie_token,60*60*24*90);
		      D("User")->where(" uid = '$ret[uid]' ")->save(array("last_login_time"=>time(),"cookie_token"=>$cookie_token,"cookie_token_expire"=>$cookie_token_expire));
		      unset($ret['password']);

	          $this->message("登录成功！",U('Home/Item/index'));		        
		    }else{
		      $this->message("用户名或密码不正确");
		    }

		  }else{
		    $this->message("验证码不正确");
		  }

		}
	}

	//生成验证码
	public function verify(){
	  //生成验证码图片
	  Header("Content-type: image/PNG");
	  $im = imagecreate(44,18); // 画一张指定宽高的图片
	  $back = ImageColorAllocate($im, 245,245,245); // 定义背景颜色
	  imagefill($im,0,0,$back); //把背景颜色填充到刚刚画出来的图片中
	  $vcodes = "";
	  srand((double)microtime()*1000000);
	  //生成4位数字
	  for($i=0;$i<4;$i++){
	  $font = ImageColorAllocate($im, rand(100,255),rand(0,100),rand(100,255)); // 生成随机颜色
	  $authnum=rand(1,9);
	  $vcodes.=$authnum;
	  imagestring($im, 5, 2+$i*10, 1, $authnum, $font);
	  }
	  $_SESSION['v_code'] = $vcodes;

	  for($i=0;$i<200;$i++) //加入干扰象素
	  {
	    $randcolor = ImageColorallocate($im,rand(0,255),rand(0,255),rand(0,255));
	    imagesetpixel($im, rand()%70 , rand()%30 , $randcolor); // 画像素点函数
	  }
	  ImagePNG($im);
	  ImageDestroy($im);
	}

	public function setting(){
		$user = $this->checkLogin();
		if (!IS_POST) {
		  $this->assign("user",$user);
		  $this->display ();

		}else{
			$username = $user['username'];
			$password = I("password");
			$new_password = I("new_password");
			$ret = D("User")->checkLogin($username,$password);
			if ($ret) {
					$ret = D("User")->updatePwd($user['uid'],$new_password);
					if ($ret) {
						$this->message("修改成功！",U("Home/Item/index"));
					}else{
						$this->message("修改失败！");

					}

				}else{	
					$this->message("原密码不正确");
				}

		}
	}

	//退出登录
	public function exist(){
		$login_user = $this->checkLogin();
		session("login_user" , NULL);
		cookie('cookie_token',NULL);
		$this->message("退出成功！",U('Home/index/index'));
	}
}