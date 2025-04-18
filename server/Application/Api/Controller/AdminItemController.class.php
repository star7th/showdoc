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
        $where = " is_del = 0 ";
        if ($item_name) {
            $item_name = \SQLite3::escapeString($item_name);
            $where .= " and item_name like '%{$item_name}%' ";
        }
        if ($username) {
            $username = \SQLite3::escapeString($username);
            $where .= " and username like '%{$username}%' ";
        }
        $items = D("Item")->where($where)->order(" addtime desc  ")->page($page, $count)->select();
        $total = D("Item")->where($where)->count();
        $return = array();
        $return['total'] = (int)$total;
        if ($items) {
            foreach ($items as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
                $value['member_num'] = D("ItemMember")->where(" item_id = '$value[item_id]' ")->count()  + D("TeamItemMember")->where(" item_id = '$value[item_id]' ")->count();
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

    //转让项目
    public function attorn()
    {
        $login_user = $this->checkLogin();
        $this->checkAdmin();
        $username = I("post.username");
        $item_id = I("post.item_id/d");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();


        $member = D("User")->where(" username = '%s' ", array($username))->find();

        if (!$member) {
            $this->sendError(10209);
            return;
        }

        $data['username'] = $member['username'];
        $data['uid'] = $member['uid'];


        $id = D("Item")->where(" item_id = '$item_id' ")->save($data);

        $return = D("Item")->where("item_id = '$item_id' ")->find();

        if (!$return) {
            $this->sendError(10101);
            return;
        }

        $this->sendResult($return);
    }
}
