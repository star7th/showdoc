<?php

namespace Api\Controller;

use Think\Controller;

class MemberController extends BaseController
{


    //保存
    public function save()
    {
        $member_group_id =  I("member_group_id/d");
        $item_id = I("post.item_id/d");
        $cat_id = I("cat_id/d") ?  I("cat_id/d") : 0;
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        if (!$this->checkItemManage($uid, $item_id)) {
            $this->sendError(10303);
            return;
        }

        $username = I("username");
        $username_array = explode(",", $username);
        foreach ($username_array as $key => $value) {
            $member = D("User")->where(" username = '%s' ", array($value))->find();
            if (!$member) {
                continue;
            }
            $if_exit = D("ItemMember")->where(" uid = '$member[uid]' and item_id = '$item_id' ")->find();
            if ($if_exit) {
                continue;
            }
            $data = array();
            $data['username'] = $member['username'];
            $data['uid'] = $member['uid'];
            $data['item_id'] = $item_id;
            $data['member_group_id'] = $member_group_id;
            $data['cat_id'] = $cat_id;
            $data['addtime'] = time();
            $id = D("ItemMember")->add($data);
        }
        $return = D("ItemMember")->where(" item_member_id = '$id' ")->find();
        if (!$return) {
            $this->sendError(10101);
        } else {
            D("ItemChangeLog")->addLog($login_user['uid'], $item_id, 'binding', 'member', $member['uid'], $member['username']);
            $this->sendResult($return);
        }
    }

    //获取成员列表
    public function getList()
    {
        $item_id = I("item_id/d");
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        if (!$this->checkItemManage($uid, $item_id)) {
            $this->sendError(10303);
            return;
        }
        if ($item_id > 0) {
            $ret = D("ItemMember")->where(" item_id = '$item_id' ")->join(" left join user on user.uid = item_member.uid")->field("item_member.* , user.name as name")->order(" addtime asc  ")->select();
        }
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
                $value['member_group'] = $value['member_group_id'] == 1 ? "编辑" : "只读";
                $value['cat_name'] = '所有目录';
                if ($value['cat_id'] > 0) {
                    $row = D("Catalog")->where(" cat_id = '$value[cat_id]' ")->find();
                    if ($row &&  $row['cat_name']) {
                        $value['cat_name'] =  $row['cat_name'];
                    }
                }
                $value['member_group'] = $value['member_group_id'] == 1 ? "编辑/目录：{$value['cat_name']}" : "只读/目录：{$value['cat_name']}";
            }
        }
        $this->sendResult($ret);
    }

    //删除成员
    public function delete()
    {
        $item_id = I("post.item_id/d");
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        if (!$this->checkItemManage($uid, $item_id)) {
            $this->sendError(10303);
            return;
        }
        $item_member_id = I("item_member_id/d");

        if ($item_member_id) {
            $member_array = D("ItemMember")->where(" item_id = '%d' and item_member_id = '%d'  ", array($item_id, $item_member_id))->find();
            $ret = D("ItemMember")->where(" item_id = '%d' and item_member_id = '%d'  ", array($item_id, $item_member_id))->delete();
        }
        if ($ret) {
            D("ItemChangeLog")->addLog($login_user['uid'], $item_id, 'unbound', 'member', $member_array['uid'], $member_array['username']);
            $this->sendResult($ret);
        } else {
            $this->sendError(10101);
        }
    }

    // 获取一个项目的所有成员列表。包括单独成员和绑定的团队成员
    public function getAllList()
    {
        $item_id = I("item_id/d");
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        if (!$this->checkItemEdit($uid, $item_id) && !$this->checkAdmin(false)) {
            $this->sendError(10301);
            return;
        }

        // 先获取项目的单独成员
        $members_array = D("ItemMember")->where(" item_id = '$item_id' ")->join(" left join user on user.uid = item_member.uid")->field("item_member.uid,item_member.username ,item_member.member_group_id ,item_member.item_id , user.name as name")->order(" addtime asc  ")->select();

        // 获取项目绑定的团队的成员
        $team_members_array = D("TeamItemMember")->where("item_id = '$item_id' ")->join(" left join user on user.uid = team_item_member.member_uid")->field("team_item_member.member_uid as uid ,team_item_member.member_username as username ,team_item_member.member_group_id,team_item_member.item_id , user.name as name")->order(" addtime asc  ")->select();

        $return_array = array();
        $uid_array = array();  // 利用这个uid数组来去重

        $item = D("Item")->where("item_id = '%d'", array($item_id))->find();
        // 把项目创建者加入成员里
        $return_array[] = array(
            "item_id" => $item_id,
            "uid" => $item['uid'],
            "username" => $item['username'],
            "username_name" => $item['username'],
            "member_group_id" => 1,
        );
        $uid_array[] = $item['uid'];

        if ($members_array) {
            foreach ($members_array as $key => $value) {
                if (!in_array($value['uid'], $uid_array)) {
                    $value['username_name'] = $value['username'];
                    if ($value['name']) {
                        $value['username_name'] .= "({$value['name']})";
                    }
                    $uid_array[] = $value['uid'];
                    $return_array[] = $value;
                }
            }
        }
        if ($team_members_array) {
            foreach ($team_members_array as $key => $value) {
                if (!in_array($value['uid'], $uid_array)) {
                    $value['username_name'] = $value['username'];
                    if ($value['name']) {
                        $value['username_name'] .= "({$value['name']})";
                    }
                    $uid_array[] = $value['uid'];
                    $return_array[] = $value;
                }
            }
        }



        $this->sendResult($return_array);
    }

    // 获取当前登录用户的所有项目和团队的成员列表（通过用于让用户快速选择历史用户进行输入）
    public function getMyAllList()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];

        // 下面sql的意思是，先查询当前用户的所有项目成员，然后查询当前用户的所有团队成员
        // 然后UNION ALL 成一个临时表table1，然后username去重，addtime排序
        $sql = "
            select uid,username,addtime from (
                select item_member.uid ,item_member.username,item_member.addtime from item_member left join item on item_member.item_id = item.item_id where item.uid = '$uid' 
            UNION ALL
                select  team_member.member_uid as uid  ,team_member.member_username as username ,team_member.addtime from team_member left join team on team_member.team_id = team.id where team.uid = '$uid' 
            ) as table1
            group by uid,username,addtime order by addtime desc  
        ";
        $res = D("Item")->query($sql);
        $this->sendResult($res);
    }
}
