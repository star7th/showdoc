<?php

namespace Api\Controller;

use Think\Controller;

class RunapiController extends BaseController
{


  //添加环境
  public function addEnv()
  {
    $login_user = $this->checkLogin();
    $env_id = I("env_id/d");
    $env_name = I("env_name");
    $item_id = I("item_id/d");
    $uid = $login_user['uid'];
    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }
    $res = false;
    if ($env_id) {
      $res = D("RunapiEnv")->where("id = '%d' and item_id = '%d' ", array($env_id, $item_id))->save(array(
        "env_name" => $env_name,
        "uid" => $uid,
        "last_update_time" => date("Y-m-d H:i:s"),
      ));
      $this->sendResult(array("env_id" => $env_id));
    } else {
      $env_id = D("RunapiEnv")->add(array(
        "env_name" => $env_name,
        "item_id" => $item_id,
        "uid" => $uid,
        "addtime" => date("Y-m-d H:i:s"),
        "last_update_time" => date("Y-m-d H:i:s"),
      ));
      $this->sendResult(array("env_id" => $env_id));
    }
  }

  //更新环境
  public function updateEnv()
  {
    $this->addEnv();
  }

  //获取环境列表
  public function getEnvList()
  {
    $item_id = I("item_id/d");
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];
    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }
    $res = D("RunapiEnv")->where("item_id = '%d' ", array($item_id))->select();
    if ($res) {
      $this->sendResult($res);
    } else {
      //如果尚未有环境，则帮其创建一个默认环境
      $env_id = D("RunapiEnv")->add(array(
        "env_name" => '默认环境',
        "item_id" => $item_id,
        "uid" => $uid,
        "addtime" => date("Y-m-d H:i:s"),
        "last_update_time" => date("Y-m-d H:i:s"),
      ));
      //并且把项目变量都绑定到该默认环境中
      D("ItemVariable")->where(array('item_id' => $item_id))->save(array(
        "env_id" => $env_id
      ));
      sleep(1);
      $this->getEnvList();
    }
  }
  //删除环境
  public function delEnv()
  {
    $env_id = I("env_id/d");
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];
    $res = D("RunapiEnv")->where(array('id' => $env_id))->find();
    $item_id = $res['item_id'];
    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }
    $res = D("RunapiEnvSelectd")->where(array('id' => $env_id))->delete();
    $res = D("RunapiEnv")->where(array('id' => $env_id))->delete();
    $res = D("ItemVariable")->where(array('env_id' => $env_id))->delete();
    if ($res) {
      $this->sendResult($res);
    } else {
      $this->sendResult(array());
    }
  }

  //设置某个环境变量为选中
  public function selectEnv()
  {
    $env_id = I("env_id/d");
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];
    $res = D("RunapiEnv")->where(array('id' => $env_id))->find();
    $item_id = $res['item_id'];
    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }
    D("RunapiEnvSelectd")->where("item_id = '%d' and uid = '%d' ", array($item_id, $uid))->delete();
    $res = D("RunapiEnvSelectd")->add(array(
      "item_id" => $item_id,
      "uid" => $uid,
      "env_id" => $env_id,
    ));
    if ($res) {
      $this->sendResult($res);
    } else {
      $this->sendResult(array());
    }
  }

  //获取用户选中的环境
  public function getSelectEnv()
  {
    $item_id = I("item_id/d");
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];
    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }
    $res = D("RunapiEnvSelectd")->where("item_id = '%d' and uid = '%d' ", array($item_id, $uid))->find();
    if ($res) {
      $this->sendResult($res);
    } else {
      $this->sendResult(array(
        "env_id" => 0,
      ));
    }
  }

  //获取全局参数
  public function getGlobalParam()
  {
    $item_id = I("item_id/d");
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];
    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }
    $return = D("Runapi")->getGlobalParam($item_id);
    $this->sendResult($return);
  }

  //修改全局参数
  public function updateGlobalParam()
  {
    $item_id = I("item_id/d");
    $param_type = I("param_type");
    $content_json_str = I("content_json_str");
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];
    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }
    $res = D("RunapiGlobalParam")->where("param_type = '%s' and item_id = '%d' ", array($param_type, $item_id))->save(array(
      "content_json_str" => $content_json_str,
      "last_update_time" => date("Y-m-d H:i:s"),
    ));
    if ($res) {
      $this->sendResult($res);
    } else {
      $this->sendResult(array());
    }
  }

  /**
   * 获取数据库连接列表（按项目+环境）
   */
  public function getDbConfigList()
  {
    $item_id = I("item_id/d");
    $env_id = I("env_id/d");
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];
    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }
    if (!$env_id) {
      $this->sendError(10101, '缺少 env_id');
      return;
    }
    $list = D("RunapiDbConfig")->where("item_id = '%d' and env_id = '%d' ", array($item_id, $env_id))->order("is_default desc,id asc")->select();
    $this->sendResult($list ? $list : array());
  }

  /**
   * 新增/更新数据库连接配置
   */
  public function saveDbConfig()
  {
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];

    $config_id = I("config_id/d");
    $item_id = I("item_id/d");
    $env_id = I("env_id/d");
    $config_name = I("config_name", "默认");
    $db_type = I("db_type", "mysql");
    $host = I("host");
    $port = I("port/d");
    $username = I("username");
    $password = I("password");
    $database = I("database");
    $options = I("options");
    $is_default = I("is_default/d", 0);

    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }
    if (!$env_id) {
      $this->sendError(10101, '缺少 env_id');
      return;
    }
    $allow_type = array('mysql', 'postgresql', 'sqlite');
    if (!in_array($db_type, $allow_type)) {
      $this->sendError(10101, '不支持的数据库类型');
      return;
    }
    if (!$config_name) {
      $config_name = '默认';
    }

    $data = array(
      "item_id" => $item_id,
      "env_id" => $env_id,
      "config_name" => $config_name,
      "db_type" => $db_type,
      "host" => $host,
      "port" => $port,
      "username" => $username,
      "password" => $password,
      "database" => $database,
      "options" => $options,
      "is_default" => $is_default ? 1 : 0,
      "last_update_time" => date("Y-m-d H:i:s"),
      "uid" => $uid,
    );

    if ($config_id) {
      $row = D("RunapiDbConfig")->where("id = '%d' ", array($config_id))->find();
      if (!$row || $row['item_id'] != $item_id) {
        $this->sendError(10101, '配置不存在');
        return;
      }
      D("RunapiDbConfig")->where("id = '%d' ", array($config_id))->save($data);
    } else {
      $data["addtime"] = date("Y-m-d H:i:s");
      $config_id = D("RunapiDbConfig")->add($data);
    }

    if ($is_default) {
      D("RunapiDbConfig")->where("item_id = '%d' and env_id = '%d' and id != '%d' ", array($item_id, $env_id, $config_id))->save(array("is_default" => 0, "last_update_time" => date("Y-m-d H:i:s")));
    }

    $this->sendResult(array("config_id" => $config_id));
  }

  /**
   * 删除数据库连接配置
   */
  public function delDbConfig()
  {
    $config_id = I("config_id/d");
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];
    $row = D("RunapiDbConfig")->where("id = '%d' ", array($config_id))->find();
    if (!$row) {
      $this->sendError(10101, '配置不存在');
      return;
    }
    if (!$this->checkItemEdit($uid, $row['item_id'])) {
      $this->sendError(10303);
      return;
    }
    D("RunapiDbConfig")->where("id = '%d' ", array($config_id))->delete();
    $this->sendResult(array());
  }
}
