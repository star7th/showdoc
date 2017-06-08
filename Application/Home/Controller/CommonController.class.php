<?php
namespace Home\Controller;
use Think\Controller;
class CommonController extends BaseController {


    //保存
    public function qrcode(){
        Vendor('Phpqrcode.phpqrcode');
        $url = I("url");
        $url = urldecode($url) ? urldecode($url) : $url;
        $size = I("size") ? I("size") : 6;
        $object = new \QRcode();
        $object->png($url, false, 3 , $size, 2);             
    }

    public function checkForUpdate(){
    	$option_data = D("Options")->where("option_name='version' ")->find();
    	$post_data = array(
    		"version" => $option_data['option_value'] ,
    		);
    	$version = $option_data['option_value'];
        // TODO 此功能是留着检测更新用的。未完成。代码有空再写吧
    	//$url = "https://www.showdoc.cc/";
    	//$result = http_post($url , $post_data);
    	//$version_num = str_replace("v", '', $num);
    	//$result = version_compare($version_num, "2.1.5",'<');
    	//echo $result;

    }

}
