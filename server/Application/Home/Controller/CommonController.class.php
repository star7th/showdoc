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


    //重置管理员用户密码
    //使用方式：
    //若用官方自动脚本或者docker方式安装，则需在命令行里执行docker exec showdoc php  /var/www/html/index.php home/common/repasswd
    //若是手动安装php环境的，则在命令行中切换到showdoc目录，执行php index.php home/common/repasswd
    //执行后会把管理员用户showdoc的密码重置为123456
    public function repasswd(){
    if (preg_match("/cli/i", php_sapi_name()) ) {
        if(D("User")->where("username = 'showdoc' ")->find()){
            D("User")->where("username = 'showdoc' ")->save(array("groupid"=> 1,'password'=>"a89da13684490eb9ec9e613f91d24d00" )) ;
        }else{
             D("User")->add(array('username'=>"showdoc" ,"groupid"=>1,'password'=>"a89da13684490eb9ec9e613f91d24d00" , 'reg_time'=>time()));
        }
        echo "ok \n" ;
    }else{
        echo "please run in command line";
    }

    }

}
