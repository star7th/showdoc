<?php
namespace Api\Controller;
use Think\Controller;
use Gregwar\Captcha\CaptchaBuilder as CaptchaBuilder;
class CommonController extends BaseController {


    //生成二维码
    public function qrcode(){
        Vendor('Phpqrcode.phpqrcode');
        $url = I("url");
        $url = urldecode($url) ? urldecode($url) : $url;
        $size = I("size") ? I("size") : 6;
        $object = new \QRcode();
        $object->png($url, false, 3 , $size, 2);             
    }

    //生成验证码
    public function verify(){
      $builder = new CaptchaBuilder();
      $builder->build();
      session('v_code', strtolower($builder->getPhrase()) ) ; //转成小写后存入session
      header('Content-type: image/PNG');
      $builder->output();
    }

    public function createCaptcha(){
        $captcha = rand(1000, 9999) ;
        $data = array(
          "mobile" =>"",
          "captcha" =>$captcha,
          "expire_time" =>time()+60*10,
          );
        $captcha_id = D("Captcha")->add($data);
        $this->sendResult(array("captcha_id"=>$captcha_id));
    }

    public function showCaptcha(){
      $captcha_id = I("captcha_id/d");
      $captcha = D("Captcha")->where("captcha_id = '$captcha_id' ")->find();
      $builder = new CaptchaBuilder($captcha['captcha']);
      $builder->build();
      header('Content-type: image/PNG');
      $builder->output();

    }

    //获取网站首页配置
    public function homePageSetting(){
        $home_page = D("Options")->get("home_page" ) ;
        $home_item = D("Options")->get("home_item" ) ;
        $beian = D("Options")->get("beian" ) ;
        $array = array(
            "home_page"=>$home_page ,
            "home_item"=>$home_item ,
            "beian"=>$beian? $beian :'',
            );
        $this->sendResult($array);
    }

    //返回showdoc版本
    public function version(){
      $file = file_get_contents('../composer.json');
      $json = json_decode($file , 1 );
      $this->sendResult(array("version"=>$json['version']));
    }

    //浏览附件
    public function visitFile(){
       R("Attachment/visitFile");
    }
    
}