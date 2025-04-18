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
    
    // 使用 SQLite3::escapeString 过滤关键词，避免SQL注入
    $keyword = \SQLite3::escapeString($keyword);
    
    $search_type = I("search_type"); // title 或 content

    $where = "password = '' AND is_del = 0"; // 只获取公开项目

    if ($keyword) {
      if ($search_type == 'content') {
        // 搜索项目内容，开源版中page表没有分表
        $like_keyword = '%' . $keyword . '%';
        $page_items = M("Page")->field("item_id")
          ->where("page_content LIKE '{$like_keyword}'")
          ->group("item_id")
          ->select();
        if ($page_items) {
          $item_ids = array();
          foreach ($page_items as $value) {
            $item_ids[] = $value['item_id'];
          }
          $item_ids_str = implode(",", $item_ids);
          $where .= " AND item_id IN ({$item_ids_str})";
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
        $like_keyword = '%' . $keyword . '%';
        $where .= " AND (item_name LIKE '{$like_keyword}' OR item_description LIKE '{$like_keyword}')";
        $items = D("Item")->field("item_id, item_name, item_description, item_type, addtime, last_update_time, item_domain")
          ->where($where)
          ->order("last_update_time DESC")
          ->page($page, $count)
          ->select();

        $total = D("Item")->where($where)->count();

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
      ->where($where)
      ->order("last_update_time DESC")
      ->page($page, $count)
      ->select();

    $total = D("Item")->where($where)->count();

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