<?php
namespace Api\Controller;
use Think\Controller;
class MockController extends BaseController {

    //添加
    public function add(){
        $page_id = I("page_id/d");  
        $template = I("template");  
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if(!$this->checkItemCreator($uid , $page['item_id'])){
            $this->sendError(10303);
            return ;
        }

        $json = json_decode(htmlspecialchars_decode($template)) ;
        if(!$json){
            $this->sendError(10101,'为了服务器安全，只允许写符合json语法的字符串');
            return ;
        }
        $unique_key = md5(time().rand()."gbgdhbdgtfgfK3@bv45342asfsdfjhyfgkj54fofgfbv45342asfsdg");
        //假如已经该页面存在mock
        $mock_page = D("Mock")->where(" page_id = '$page_id' ")->find();
        if($mock_page){
            $unique_key = $mock_page['unique_key'] ;
            D("Mock")->where("page_id = '$page_id' ")->save(array(
                "uid"=>$uid ,
                "template"=>$template ,
                "last_update_time" => date("Y-m-d H:i:s"),
            ));
        }else{
            $id = D("Mock")->add(array(
                "unique_key"=>$unique_key ,
                "uid"=>$uid ,
                "page_id"=>$page_id ,
                "item_id"=> $page['item_id'] ,
                "template"=>$template ,
                "addtime" => date("Y-m-d H:i:s"),
                "last_update_time" => date("Y-m-d H:i:s"),
                "view_times"=>0 
            ));
        }

        $this->sendResult(array(
            "page_id"=>$page_id ,
            "unique_key"=>$unique_key 
        ));
    }

    // 根据页面id获取mock信息
    public function infoByPageId(){
        $page_id = I("page_id/d");  
        $uid = $login_user['uid'] ;
        $page = D("Mock")->where(" page_id = '$page_id' ")->find();
        $login_user = $this->checkLogin(false);
        if (!$this->checkItemVisit($login_user['uid'] , $page['item_id'])) {
            $this->sendError(10103);
            return;
        }
        $this->sendResult($page);
    }

    // 根据唯一key获取mock的响应数据
    public function infoByKey(){
        $unique_key = $_REQUEST["unique_key"];  
        $page = D("Mock")->where(" unique_key = '%s' ",array($unique_key))->find();
        $template = $page['template'] ;
        $res = http_post("http://127.0.0.1:7123/mock",array(
            "template"=> htmlspecialchars_decode($page['template']) 
        ));
        if($res){
            $json = json_decode($res) ;
            if(!$json){
                $this->sendError(10101,'为了服务器安全，只允许写符合json语法的字符串');
                return ;
            }
            echo $res ;
        }else{
            echo "mock服务暂时不可用。网站管理员需要另行安装mock服务，详情请打开https://www.showdoc.com.cn/p/1ee8a176dd0ccc65609005f3a36c2cc7";
        }
        
    }

}