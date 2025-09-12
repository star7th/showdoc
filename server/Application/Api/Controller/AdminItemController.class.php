<?php

namespace Api\Controller;

use Think\Controller;

class AdminItemController extends BaseController
{


    //获取所有项目列表
    public function getList()
    {
        $login_user = $this->checkLogin();
        $this->checkAdmin();
        $item_name = I("item_name");
        $page = I("page/d");
        $count = I("count/d");
        $username = I("username");
        $is_del = I("is_del/d") ? I("is_del/d") : 0; // 0: 正常；1: 已删除
        $where = $is_del == 1 ? " is_del = 1 " : " is_del = 0 ";
        $params = array();
        if ($item_name) {
            $like_item = safe_like($item_name);
            $where .= " and item_name like '%s' ";
            $params[] = $like_item;
        }
        if ($username) {
            $like_user = safe_like($username);
            $where .= " and username like '%s' ";
            $params[] = $like_user;
        }
        // 已删除项目按删除时间倒序（使用 last_update_time 作为删除时间），正常项目按创建时间倒序
        $order = ($is_del == 1) ? " last_update_time desc " : " addtime desc ";
        $items = $params ? D("Item")->where($where, $params)->order($order)->page($page, $count)->select() : D("Item")->where($where)->order($order)->page($page, $count)->select();
        $total = $params ? D("Item")->where($where, $params)->count() : D("Item")->where($where)->count();
        $return = array();
        $return['total'] = (int)$total;
        if ($items) {
            foreach ($items as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
                if ($is_del == 1) {
                    $value['del_time'] = date("Y-m-d H:i:s", intval($value['last_update_time']));
                }
                $value['member_num'] = D("ItemMember")->where(" item_id = '%d' ", array($value['item_id']))->count()  + D("TeamItemMember")->where(" item_id = '%d' ", array($value['item_id']))->count();
            }
            $return['items'] = $items;
            $this->sendResult($return);
        } else {
            $this->sendResult(array());
        }
    }


    //删除项目
    public function deleteItem()
    {
        $login_user = $this->checkLogin();
        $this->checkAdmin();
        $item_id = I("post.item_id/d");
        $return = D("Item")->soft_delete_item($item_id);
        if (!$return) {
            $this->sendError(10101);
        } else {
            $this->sendResult($return);
        }
    }

    // 恢复已删除项目
    public function recoverItem()
    {
        $login_user = $this->checkLogin();
        $this->checkAdmin();
        $item_id = I("post.item_id/d");
        if ($item_id <= 0) {
            $this->sendError(10101, '参数错误');
            return;
        }
        $item = D("Item")->where(" item_id = '%d' ", array($item_id))->find();
        if (!$item || intval($item['is_del']) !== 1) {
            $this->sendError(10101, '项目不存在或未被删除');
            return;
        }
        // 恢复项目与页面
        D("Page")->where(" item_id = '%d' ", array($item_id))->save(array("is_del" => 0));
        $ret = D("Item")->where(" item_id = '%d' ", array($item_id))->save(array("is_del" => 0, "last_update_time" => time()));
        if (!$ret) {
            $this->sendError(10101, '恢复失败');
            return;
        }
        $this->sendResult(array());
    }

    // 永久删除项目（硬删除，不可恢复）
    public function hardDeleteItem()
    {
        $login_user = $this->checkLogin();
        $this->checkAdmin();
        $item_id = I("post.item_id/d");
        if ($item_id <= 0) {
            $this->sendError(10101, '参数错误');
            return;
        }
        $item = D("Item")->where(" item_id = '%d' ", array($item_id))->find();
        if (!$item || intval($item['is_del']) !== 1) {
            $this->sendError(10101, '仅允许对已删除项目执行永久删除');
            return;
        }
        $ret = D("Item")->delete_item($item_id);
        if (!$ret) {
            $this->sendError(10101, '删除失败');
            return;
        }
        $this->sendResult(array());
    }

    //转让项目
    public function attorn()
    {
        $login_user = $this->checkLogin();
        $this->checkAdmin();
        $username = I("post.username");
        $item_id = I("post.item_id/d");

        $item  = D("Item")->where(array('item_id' => $item_id))->find();


        $member = D("User")->where(" username = '%s' ", array($username))->find();

        if (!$member) {
            $this->sendError(10209);
            return;
        }

        $data['username'] = $member['username'];
        $data['uid'] = $member['uid'];


        $id = D("Item")->where(array('item_id' => $item_id))->save($data);

        $return = D("Item")->where(array('item_id' => $item_id))->find();

        if (!$return) {
            $this->sendError(10101);
            return;
        }

        $this->sendResult($return);
    }
}
