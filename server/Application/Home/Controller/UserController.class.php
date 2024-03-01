<?php

namespace Home\Controller;

use Think\Controller;

class UserController extends BaseController
{


	//注册
	public function register()
	{

		//跳转到web目录
		header("location:./web/#/user/register");
		exit();

	}



	//登录
	public function login()
	{

		//跳转到web目录
		header("location:./web/#/user/login");
		exit();

	}

	//生成验证码
	public function verify()
	{
		//生成验证码图片
		Header("Content-type: image/PNG");
		$im = imagecreate(44, 18); // 画一张指定宽高的图片
		$back = ImageColorAllocate($im, 245, 245, 245); // 定义背景颜色
		imagefill($im, 0, 0, $back); //把背景颜色填充到刚刚画出来的图片中
		$vcodes = "";
		srand((float)microtime() * 1000000);
		//生成4位数字
		for ($i = 0; $i < 4; $i++) {
			$font = ImageColorAllocate($im, rand(100, 255), rand(0, 100), rand(100, 255)); // 生成随机颜色
			$authnum = rand(1, 9);
			$vcodes .= $authnum;
			imagestring($im, 5, 2 + $i * 10, 1, $authnum, $font);
		}
		$_SESSION['v_code'] = $vcodes;

		for ($i = 0; $i < 200; $i++) //加入干扰象素
		{
			$randcolor = ImageColorallocate($im, rand(0, 255), rand(0, 255), rand(0, 255));
			imagesetpixel($im, rand() % 70, rand() % 30, $randcolor); // 画像素点函数
		}
		ImagePNG($im);
		ImageDestroy($im);
	}

	//退出登录
	public function exist()
	{
		$login_user = $this->checkLogin();
		session("login_user", NULL);
		cookie('cookie_token', NULL);
		session(null);
		$this->message(L('logout_succeeded'), U('Home/index/index'));
	}
}
