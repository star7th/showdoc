<?php
namespace Api\Controller;
use Think\Controller;
class RunapiController extends BaseController {


    //添加环境
    public function addEnv(){
      $login_user = $this->checkLogin();
      $env_id = I("env_id/d");
      $env_name = I("env_name");
      $item_id = I("item_id/d");
      $uid = $login_user['uid'] ;
      if(!$this->checkItemEdit($uid , $item_id)){
          $this->sendError(10303);
          return ;
      } 
      $res = false; 
      if($env_id){
        $res = D("RunapiEnv")->where("id = {$env_id} and item_id = {$item_id} ")->save(array(
          "env_name" => $env_name ,
          "uid" => $uid ,
          "last_update_time" => date("Y-m-d H:i:s") ,
        ));
        $this->sendResult(array("env_id"=>$env_id));
      }else{
        $env_id = D("RunapiEnv")->add(array(
          "env_name" => $env_name ,
          "item_id" => $item_id ,
          "uid" => $uid ,
          "addtime" => date("Y-m-d H:i:s") ,
          "last_update_time" => date("Y-m-d H:i:s") ,
        ));
        $this->sendResult(array("env_id"=>$env_id));
      }
      
    }

    //更新环境
    public function updateEnv(){
      $this->addEnv();
    }

    //获取环境列表
    public function getEnvList(){
      $item_id = I("item_id/d");
      $login_user = $this->checkLogin();
      $uid = $login_user['uid'] ;
      if(!$this->checkItemEdit($uid , $item_id)){
        $this->sendError(10303);
        return ;
      }
      $res = D("RunapiEnv")->where("item_id = {$item_id} ")->select();
      if($res){
        $this->sendResult($res);
      }else{
        //如果尚未有环境，则帮其创建一个默认环境
        $env_id = D("RunapiEnv")->add(array(
          "env_name" => '默认环境' ,
          "item_id" => $item_id ,
          "uid" => $uid ,
          "addtime" => date("Y-m-d H:i:s") ,
          "last_update_time" => date("Y-m-d H:i:s") ,
        ));
        //并且把项目变量都绑定到该默认环境中
        D("ItemVariable")->where(" item_id = '$item_id'")->save(array(
          "env_id"=>$env_id
        ));
        sleep(1);
        $this->getEnvList();

      }
    }
    //删除环境
    public function delEnv(){
      $env_id = I("env_id/d");
      $login_user = $this->checkLogin();
      $uid = $login_user['uid'] ;
      $res = D("RunapiEnv")->where("id = {$env_id}")->find();
      $item_id = $res['item_id'] ;
      if(!$this->checkItemEdit($uid , $item_id)){
        $this->sendError(10303);
        return ;
      }
      $res = D("RunapiEnvSelectd")->where("id = {$env_id} ")->delete();
      $res = D("RunapiEnv")->where("id = {$env_id} ")->delete();
      $res = D("ItemVariable")->where("env_id = {$env_id}")->delete();
      if($res){
        $this->sendResult($res);
      }else{
        $this->sendResult(array());
      }
    }

    //设置某个环境变量为选中
    public function selectEnv(){
      $env_id = I("env_id/d");
      $login_user = $this->checkLogin();
      $uid = $login_user['uid'] ;
      $res = D("RunapiEnv")->where("id = {$env_id}")->find();
      $item_id = $res['item_id'] ;
      if(!$this->checkItemEdit($uid , $item_id)){
        $this->sendError(10303);
        return ;
      }
      D("RunapiEnvSelectd")->where("item_id = {$item_id} and uid = '$uid' ")->delete();
      $res = D("RunapiEnvSelectd")->add(array(
        "item_id" => $item_id ,
        "uid" => $uid ,
        "env_id" => $env_id ,
      ));
      if($res){
        $this->sendResult($res);
      }else{
        $this->sendResult(array());
      }
    }

    //获取用户选中的环境
    public function getSelectEnv(){
      $item_id = I("item_id/d");
      $login_user = $this->checkLogin();
      $uid = $login_user['uid'] ;
      if(!$this->checkItemEdit($uid , $item_id)){
        $this->sendError(10303);
        return ;
      }
      $res = D("RunapiEnvSelectd")->where("item_id = {$item_id} and uid = '$uid' ")->find();
      if($res){
        $this->sendResult($res);
      }else{
        $this->sendResult(array(
          "env_id" => 0 ,
        ));
      }
    }  

    //获取全局参数
    public function getGlobalParam(){
      $item_id = I("item_id/d");
      $login_user = $this->checkLogin();
      $uid = $login_user['uid'] ;
      if(!$this->checkItemEdit($uid , $item_id)){
        $this->sendError(10303);
        return ;
      }
      $return = D("Runapi")->getGlobalParam($item_id);
      $this->sendResult($return);
    }

    //修改全局参数
    public function updateGlobalParam(){
      $item_id = I("item_id/d");
      $param_type = I("param_type");
      $content_json_str = I("content_json_str");
      $login_user = $this->checkLogin();
      $uid = $login_user['uid'] ;
      if(!$this->checkItemEdit($uid , $item_id)){
        $this->sendError(10303);
        return ;
      }
      $res = D("RunapiGlobalParam")->where("param_type = '%s' and item_id = {$item_id} ",array($param_type))->save(array(
        "content_json_str" => $content_json_str ,
        "last_update_time" => date("Y-m-d H:i:s") ,
      ));
      if($res){
        $this->sendResult($res);
      }else{
        $this->sendResult(array());
      }

    }



}