<?php
namespace Api\Controller;
use Think\Controller;
class FlowController extends BaseController {

    private $pages ;

    //保存
    public function save(){
        $login_user = $this->checkLogin();
        $id = I("id/d");
        $flow_name = I("flow_name");
        $item_id = I("item_id/d");
        $env_id = I("env_id/d");
        $times = isset($_REQUEST['times']) ? I("times/d") : 1 ;
        $time_interval = isset($_REQUEST['time_interval']) ? I("time_interval/d") : 0 ;
        $error_continue =isset($_REQUEST['error_continue']) ? I("error_continue/d") : 1;
        $save_change = isset($_REQUEST['save_change']) ? I("save_change/d") : 1 ;
 
        $date_time = date("Y-m-d H:i:s");
        if($id){
            $res = D("RunapiFlow")->where(" id = '{$id}' ")->find();
            if(!$this->checkItemEdit($login_user['uid'] , $res['item_id'])){
                $this->sendError(10303);
                return ;
            }
            $data = array() ;
            $data['last_update_time'] = $date_time ;
            if($flow_name){
                $data['flow_name'] = $flow_name ;
            }
            if(isset($_REQUEST['env_id'])){
                $data['env_id'] = $env_id ;
            }
            if(isset($_REQUEST['times'])){
                $data['times'] = $times ;
            }        
            if(isset($_REQUEST['time_interval'])){
                $data['time_interval'] = $time_interval ;
            }
            if(isset($_REQUEST['error_continue'])){
                $data['error_continue'] = $error_continue ;
            }
            if(isset($_REQUEST['save_change'])){
                $data['save_change'] = $save_change ;
            }        
            D("RunapiFlow")->where(" id = '{$id}' ")->save($data);

        }else{
            if(!$this->checkItemEdit($login_user['uid'] , $item_id)){
                $this->sendError(10303);
                return ;
            }
            $data = array() ;
            $data['username'] = $login_user['username'] ;
            $data['uid'] = $login_user['uid'] ;
            $data['flow_name'] = $flow_name ;
            $data['env_id'] = $env_id ;
            $data['item_id'] = $item_id ;
            $data['times'] = $times ;
            $data['time_interval'] = $time_interval ;
            $data['error_continue'] = $error_continue ;
            $data['save_change'] = $save_change ;
            $data['addtime'] = $date_time ;
            $data['last_update_time'] = $date_time ;
            // 如果环境小于等于0，尝试获取项目的第一个环境变量赋值
            if($env_id <= 0){
                $res = D("RunapiEnv")->where(" item_id = '{$item_id}' ")->find() ;
                if($res && $res['id']){
                    $data['env_id'] =  $res['id'] ;
                }
            }
            $id = D("RunapiFlow")->add($data);

        }
        usleep(300000);
        $res = D("RunapiFlow")->where(" id = '{$id}' ")->find();
        $this->sendResult($res);
        
    }

    //获取列表
    public function getList(){
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d");
        if(!$this->checkItemEdit($login_user['uid'] , $item_id)){
            $this->sendError(10303);
            return ;
        }
        
        $ret = D("RunapiFlow")->where(" item_id = '{$item_id}' ")->order(" id desc  ")->select();
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $this->sendResult(array());
        }
    }

    //删除
    public function delete(){
        $id = I("id/d")? I("id/d") : 0;
        $login_user = $this->checkLogin();
        $res = D("RunapiFlow")->where(" id = '{$id}' ")->find();
        if(!$this->checkItemEdit($login_user['uid'] , $res['item_id'])){
            $this->sendError(10303);
            return ;
        }
        
        $ret = D("RunapiFlow")->where(" id = '$id'")->delete();
        
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
            $this->sendResult($return);
        }
    }

    // 新增接口到flow中
    public function addFlowPage(){
        $login_user = $this->checkLogin();
        $flow_id = I("flow_id/d");
        $page_id = I("page_id/d");
        $flow_res = D("RunapiFlow")->where(" id = '{$flow_id}' ")->find();
        if(!$this->checkItemEdit($login_user['uid'] , $flow_res['item_id'])){
            $this->sendError(10303);
            return ;
        }
        $page_res = $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if(!$this->checkItemEdit($login_user['uid'] , $page_res['item_id'])){
            $this->sendError(10303);
            return ;
        }

        // 获取该flow的最后一个页面的顺序号
        $s_number1 = D("RunapiFlowPage")->where("flow_id = '{$flow_id}'")->order("s_number desc")->getField("s_number");
        $s_number = $s_number1 + 1 ;
        $id = D("RunapiFlowPage")->add(array(
            "flow_id" => $flow_id ,
            "page_id" => $page_id ,
            "s_number" => $s_number ,
            "addtime" => date("Y-m-d H:i:s") ,
        ));
        if($id){
            $this->sendResult($id);
        }else{
            $this->sendError(10101);
        }

    }

    // 从flow中删除接口
    public function deleteFlowPage(){
        $login_user = $this->checkLogin();
        $id = I("id/d");
        $flow_page_res = D("RunapiFlowPage")->where(" id = '{$id}' ")->find();
        $page_id = $flow_page_res['page_id'] ;
        $page_res = $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if(!$this->checkItemEdit($login_user['uid'] , $page_res['item_id'])){
            $this->sendError(10303);
            return ;
        }
        $res = D("RunapiFlowPage")->where(" id = '{$id}' ")->delete();
        if($res){
            $this->sendResult($res);
        }else{
            $this->sendError(10101);
        }

    }
    // 获取某个流程里的接口列表
    public function getFlowPageList(){
        $login_user = $this->checkLogin();
        $flow_id = I("flow_id/d");
        $flow_res = D("RunapiFlow")->where(" id = '{$flow_id}' ")->find();
        if(!$this->checkItemEdit($login_user['uid'] , $flow_res['item_id'])){
            $this->sendError(10303);
            return ;
        }
        $res = D("RunapiFlowPage")->where(array(
            "flow_id" => $flow_id ,
        ))->order("s_number asc ")->select();
        if($res){
            foreach ($res as $key => $value) {
                $res[$key]['page_title'] = $this->_get_page_title($flow_res['item_id'],$value['page_id']);
            }
            $this->sendResult($res);
        }else{
            $this->sendResult(array());
        } 

    }

    private function _get_page_title($item_id,$page_id){
        if(!$this->pages){
            $ret = D("Page")->where(" item_id = '%d' " , array($item_id))->select();
            if($ret){
                $this->pages = $ret ;
            }else{
                return false ;
            }
        }

        foreach ( $this->pages as $key => $value) {
            if($value['page_id'] == $page_id){
                return $value['page_title'] ;
            }
        }
        return false ;
    }


    // 保存顺序关系
    public function saveSort(){
        $login_user = $this->checkLogin();
        $flow_id = I("flow_id/d");
        $orders = I("orders");
        $res = D("RunapiFlow")->where(" id = '{$flow_id}' ")->find();
        if(!$this->checkItemEdit($login_user['uid'] , $res['item_id'])){
            $this->sendError(10303);
            return ;
        }
        $data_array = json_decode(htmlspecialchars_decode($orders) , true) ;
        if($data_array){
            foreach ($data_array as $key => $value) {
                if($value['id']){
                    D("RunapiFlowPage")->where(" flow_id = '%d' and id = '%d' ",array($flow_id , $value['id']))->save(array(
                        "s_number"=>$value['s_number']
                    ));
                }
            }
        }
        $this->sendResult(array());

    }

    // 保存启用关系
    public function setFlowPageEnabled(){
        $login_user = $this->checkLogin();
        $flow_id = I("flow_id/d");
        $ids = I("ids");
        $res = D("RunapiFlow")->where(" id = '{$flow_id}' ")->find();
        if(!$this->checkItemEdit($login_user['uid'] , $res['item_id'])){
            $this->sendError(10303);
            return ;
        }
        $data_array = json_decode(htmlspecialchars_decode($ids) , true) ;
        if($data_array){
            D("RunapiFlowPage")->where(" flow_id = '%d'",array($flow_id))->save(array(
                "enabled"=>0
            ));
            foreach ($data_array as $key => $value) {
                if($value){
                    D("RunapiFlowPage")->where(" flow_id = '%d' and id = '%d' ",array($flow_id , $value))->save(array(
                        "enabled"=>1
                    ));
                }
            }
        }
        $this->sendResult(array());

    }




}