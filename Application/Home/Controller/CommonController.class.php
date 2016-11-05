<?php
namespace Home\Controller;
use Think\Controller;
class CommonController extends BaseController {


    //保存
    public function qrcode(){
        Vendor('Phpqrcode.phpqrcode');
        $url = I("url");
        $size = I("size") ? I("size") : 6;
        $object = new \QRcode();
        $object->png($url, false, 3 , $size, 2);             
    }

}