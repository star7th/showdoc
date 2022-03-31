<?php

namespace Api\Model;

use Api\Model\BaseModel;

/**
 * 
 * @author star7th      
 */
class RunapiModel
{

  protected $autoCheckFields = false;

  //获取全局参数
  public function getGlobalParam($item_id)
  {
    $item_id = intval($item_id);
    $return = array(
      'query' => array(),
      'body' => array(),
      'header' => array(),
      'cookies' => array(),
      'preScript' => '',
      'postScript' => '',
    );

    $res = D("RunapiGlobalParam")->where(" param_type = 'query' and item_id = {$item_id} ")->find();
    if ($res) {
      $return['query'] = json_decode(htmlspecialchars_decode($res['content_json_str']), true);
      $return['query'] = $return['query'] ? $return['query'] : array();
    } else {
      D("RunapiGlobalParam")->add(array(
        "param_type" => "query",
        "item_id" => $item_id,
        "content_json_str" => '[]',
        "addtime" => date("Y-m-d H:i:s"),
        "last_update_time" => date("Y-m-d H:i:s"),
      ));
    }
    $res = D("RunapiGlobalParam")->where(" param_type = 'body' and item_id = {$item_id} ")->find();
    if ($res) {
      $return['body'] = json_decode(htmlspecialchars_decode($res['content_json_str']), true);
      $return['body'] = $return['body'] ? $return['body'] : array();
    } else {
      D("RunapiGlobalParam")->add(array(
        "param_type" => "body",
        "item_id" => $item_id,
        "content_json_str" => '[]',
        "addtime" => date("Y-m-d H:i:s"),
        "last_update_time" => date("Y-m-d H:i:s"),
      ));
    }
    $res = D("RunapiGlobalParam")->where(" param_type = 'header' and item_id = {$item_id} ")->find();
    if ($res) {
      $return['header'] = json_decode(htmlspecialchars_decode($res['content_json_str']), true);
      $return['header'] = $return['header'] ? $return['header'] : array();
    } else {
      D("RunapiGlobalParam")->add(array(
        "param_type" => "header",
        "item_id" => $item_id,
        "content_json_str" => '[]',
        "addtime" => date("Y-m-d H:i:s"),
        "last_update_time" => date("Y-m-d H:i:s"),
      ));
    }
    $res = D("RunapiGlobalParam")->where(" param_type = 'cookies' and item_id = {$item_id} ")->find();
    if ($res) {
      $return['cookies'] = json_decode(htmlspecialchars_decode($res['content_json_str']), true);
      $return['cookies'] = $return['cookies'] ? $return['cookies'] : array();
    } else {
      D("RunapiGlobalParam")->add(array(
        "param_type" => "cookies",
        "item_id" => $item_id,
        "content_json_str" => '[]',
        "addtime" => date("Y-m-d H:i:s"),
        "last_update_time" => date("Y-m-d H:i:s"),
      ));
    }
    $res = D("RunapiGlobalParam")->where(" param_type = 'preScript' and item_id = {$item_id} ")->find();
    if ($res) {
      $return['preScript'] =  htmlspecialchars_decode($res['content_json_str']);
      $return['preScript'] = $return['preScript'] ? $return['preScript'] : '';
    } else {
      D("RunapiGlobalParam")->add(array(
        "param_type" => "preScript",
        "item_id" => $item_id,
        "content_json_str" => '',
        "addtime" => date("Y-m-d H:i:s"),
        "last_update_time" => date("Y-m-d H:i:s"),
      ));
    }
    $res = D("RunapiGlobalParam")->where(" param_type = 'postScript' and item_id = {$item_id} ")->find();
    if ($res) {
      $return['postScript'] =  htmlspecialchars_decode($res['content_json_str']);
      $return['postScript'] = $return['postScript'] ? $return['postScript'] : '';
    } else {
      D("RunapiGlobalParam")->add(array(
        "param_type" => "postScript",
        "item_id" => $item_id,
        "content_json_str" => '',
        "addtime" => date("Y-m-d H:i:s"),
        "last_update_time" => date("Y-m-d H:i:s"),
      ));
    }
    return $return;
  }
}
