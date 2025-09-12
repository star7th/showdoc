<?php

namespace Api\Controller;

use Think\Controller;

class PublicSquareController extends BaseController
{
  // 检查公开广场功能是否启用
  public function checkEnabled()
  {
    $enable_public_square = D("Options")->get("enable_public_square");
    $this->sendResult(array(
      "enable" => $enable_public_square ? 1 : 0
    ));
  }

  //获取公开项目列表
  public function getPublicItems()
  {
    // 检查是否启用了公开广场功能
    $enable_public_square = D("Options")->get("enable_public_square");
    if (!$enable_public_square) {
      $this->sendError(10501, '公开广场功能未启用');
      return;
    }

    // 检查是否需要强制登录
    $force_login = D("Options")->get("force_login");
    if ($force_login) {
      $login_user = $this->checkLogin();
    }

    $page = I("page/d") ? I("page/d") : 1;
    $count = I("count/d") ? I("count/d") : 20;
    $keyword = I("keyword");
    
    // 参数化 + safe_like 处理关键词
    $keyword = $keyword ? safe_like($keyword) : '';
    
    $search_type = I("search_type"); // title 或 content

    // 基础查询条件
    $where_conditions = array(
      'password' => '',
      'is_del' => 0
    );

    if ($keyword) {
      if ($search_type == 'content') {
        // 搜索项目内容，开源版中page表没有分表
        $page_items = M("Page")->field("item_id")
          ->where("page_content LIKE '%s'", array($keyword))
          ->group("item_id")
          ->select();
        if ($page_items) {
          $item_ids = array();
          foreach ($page_items as $value) {
            $item_ids[] = $value['item_id'];
          }
          // 使用数组条件而不是字符串拼接
          $where_conditions['item_id'] = array('in', $item_ids);
        } else {
          // 如果没有找到匹配的内容，返回空结果
          $this->sendResult(array(
            "total" => 0,
            "items" => array()
          ));
          return;
        }
      } else {
        // 默认搜索项目标题和描述
        $items = D("Item")->field("item_id, item_name, item_description, item_type, addtime, last_update_time, item_domain")
          ->where("password = '' AND is_del = 0 AND (item_name LIKE '%s' OR item_description LIKE '%s')", array($keyword, $keyword))
          ->order("last_update_time DESC")
          ->page($page, $count)
          ->select();
        $total = D("Item")->where("password = '' AND is_del = 0 AND (item_name LIKE '%s' OR item_description LIKE '%s')", array($keyword, $keyword))->count();

        if ($items) {
          foreach ($items as $key => &$value) {
            $value['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
            $value['last_update_time'] = date("Y-m-d H:i:s", $value['last_update_time']);
            $value['item_domain'] = $value['item_domain'] ? $value['item_domain'] : $value['item_id'];
          }
        }

        $return = array(
          "total" => (int)$total,
          "items" => $items ? $items : array()
        );

        $this->sendResult($return);
        return;
      }
    }

    $items = D("Item")->field("item_id, item_name, item_description, item_type, addtime, last_update_time, item_domain")
      ->where($where_conditions)
      ->order("last_update_time DESC")
      ->page($page, $count)
      ->select();
    $total = D("Item")->where($where_conditions)->count();

    if ($items) {
      foreach ($items as $key => &$value) {
        $value['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
        $value['last_update_time'] = date("Y-m-d H:i:s", $value['last_update_time']);
        $value['item_domain'] = $value['item_domain'] ? $value['item_domain'] : $value['item_id'];
      }
    }

    $return = array(
      "total" => (int)$total,
      "items" => $items ? $items : array()
    );

    $this->sendResult($return);
  }
} 