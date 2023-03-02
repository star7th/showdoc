<?php

namespace Api\Controller;

use Think\Controller;
use Gregwar\Captcha\CaptchaBuilder as CaptchaBuilder;

class CommonController extends BaseController
{


  //生成二维码
  public function qrcode()
  {
    Vendor('Phpqrcode.phpqrcode');
    $url = I("url");
    $url = urldecode($url) ? urldecode($url) : $url;
    $size = I("size") ? I("size") : 6;
    $object = new \QRcode();
    $object->png($url, false, 3, $size, 2);
  }

  //生成验证码
  public function verify()
  {

    if (version_compare(PHP_VERSION, COMPOSER_PHP_VERSION, '>')) {
      $builder = new CaptchaBuilder();
      $builder->build();
      session('v_code', strtolower($builder->getPhrase())); //转成小写后存入session
      header('Content-type: image/PNG');
      $builder->output();
    } else {
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
  }

  public function createCaptcha()
  {
    if (version_compare(PHP_VERSION, COMPOSER_PHP_VERSION, '>')) {
      $captcha = get_rand_str(4);
    } else {
      $captcha = rand(1000, 9999);
    }
    $data = array(
      "mobile" => "",
      "captcha" => $captcha,
      "expire_time" => time() + 60 * 10,
    );
    $captcha_id = D("Captcha")->add($data);
    $this->sendResult(array("captcha_id" => $captcha_id));
  }

  public function showCaptcha()
  {
    $captcha_id = I("captcha_id/d");
    $captcha = D("Captcha")->where("captcha_id = '$captcha_id' ")->find();

    if (version_compare(PHP_VERSION, COMPOSER_PHP_VERSION, '>')) {
      $builder = new CaptchaBuilder($captcha['captcha']);
      $builder->build();
      header('Content-type: image/PNG');
      $builder->output();
    } else {
      $numArray  = array_map('intval', str_split($captcha['captcha']));
      //生成验证码图片
      Header("Content-type: image/PNG");
      $im = imagecreate(44, 18); // 画一张指定宽高的图片
      $back = ImageColorAllocate($im, 245, 245, 245); // 定义背景颜色
      imagefill($im, 0, 0, $back); //把背景颜色填充到刚刚画出来的图片中
      srand((float)microtime() * 1000000);
      //生成4位数字
      for ($i = 0; $i < 4; $i++) {
        $font = ImageColorAllocate($im, rand(100, 255), rand(0, 100), rand(100, 255)); // 生成随机颜色
        imagestring($im, 5, 2 + $i * 10, 1, $numArray[$i], $font);
      }
      for ($i = 0; $i < 200; $i++) //加入干扰象素
      {
        $randcolor = ImageColorallocate($im, rand(0, 255), rand(0, 255), rand(0, 255));
        imagesetpixel($im, rand() % 70, rand() % 30, $randcolor); // 画像素点函数
      }
      ImagePNG($im);
      ImageDestroy($im);
    }
  }

  //获取网站首页配置
  public function homePageSetting()
  {
    $home_page = D("Options")->get("home_page");
    $home_item = D("Options")->get("home_item");
    $open_api_key = D("Options")->get("open_api_key");
    $beian = D("Options")->get("beian");
    $array = array(
      "home_page" => $home_page,
      "home_item" => $home_item,
      "beian" => $beian ? $beian : '',
      "is_show_ai" => $open_api_key ? 1 : 0,
    );
    $this->sendResult($array);
  }

  //返回showdoc版本
  public function version()
  {
    $file = file_get_contents('../composer.json');
    $json = json_decode($file, 1);
    $this->sendResult(array("version" => $json['version']));
  }

  //浏览附件
  public function visitFile()
  {
    R("Attachment/visitFile");
  }
}
