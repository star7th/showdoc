<?php

namespace Api\Controller;

use Think\Controller;
/*
    团队和项目的绑定关系
 */

class TeamItemController extends BaseController
{

    //添加和编辑
    public function save()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];

        $item_id = I("post.item_id");
        $team_id = I("post.team_id/d");
        $item_id =  \SQLite3::escapeString($item_id);

        if (!$this->checkTeamManage($uid, $team_id)) {
            $this->sendError(10103);
            return;
        }

        $teamInfo = D("Team")->where(" id = '$team_id'  ")->find();

        $item_id_array = explode(",", $item_id);
        foreach ($item_id_array as $key => $value) {
            $item_id = intval($value);
            if (!$this->checkItemManage($uid, $item_id)) {
                $this->sendError(10303);
                return;
            }

            if (D("TeamItem")->where("  team_id = '$team_id' and item_id = '$item_id' ")->find()) {
                continue; //如果该项目已经加入团队了，则结束当前一次循环。
            }


            $data = array();
            $data['item_id'] = $item_id;
            $data['team_id'] = $team_id;
            $data['addtime'] = time();
            $id = D("TeamItem")->add($data);

            D("ItemChangeLog")->addLog($login_user['uid'], $item_id, 'binding', 'team', $team_id, $teamInfo['team_name']);

            //获取该团队的所有成员并加入项目
            $teamMembers = D("TeamMember")->where("  team_id = '$team_id' ")->select();
            if ($teamMembers) {
                foreach ($teamMembers as $key => $value) {
                    $data = array(
                        "team_id" => $team_id,
                        "member_uid" => $value['member_uid'],
                        "member_username" => $value['member_username'],
                        "item_id" => $item_id,
                        "member_group_id" => 1, //默认添加的权限为1，即编辑权限
                        "addtime" => time()
                    );
                    D("TeamItemMember")->add($data);
                }
            }
        }


        $return = D("TeamItem")->where(" id = '$id' ")->find();

        if (!$return) {
            $return['error_code'] = 10103;
            $return['error_message'] = 'request  fail';
        }

        $this->sendResult($return);
    }

    //根据项目来获取其绑定的团队列表
    public function getList()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];

        $item_id = I("item_id/d");

        if (!$this->checkItemManage($uid, $item_id)) {
            $this->sendError(10303);
            return;
        }

        $sql  = "select team.*,team_item.team_id , team_item.id as id from team left join team_item on team.id = team_item.team_id where team_item.item_id = '$item_id' ";
        $ret = D("TeamItem")->query($sql);

        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
            }
            $this->sendResult($ret);
        } else {
            $this->sendResult(array());
        }
    }

    //根据团队来获取项目列表
    public function getListByTeam()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];

        $team_id = I("team_id/d");

        // 权限判断。团队管理者和团队成员可以看到该列表
        if (!$this->checkTeamManage($uid, $team_id) && !D("TeamMember")->where(" member_uid = '$uid' and team_id = '$team_id' ")->find()) {
            $this->sendError(10103);
            return;
        }

        $teamInfo = D("Team")->where(" id = '$team_id'  ")->find();


        $sql  = "select item.*,team_item.team_id , team_item.id as id from item left join team_item on item.item_id = team_item.item_id where team_item.team_id = '$team_id' and item.is_del = 0 ";
        $ret = D("Item")->query($sql);

        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
            }
            $this->sendResult($ret);
        } else {
            $this->sendResult(array());
        }
    }


    //删除
    public function delete()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];

        $id = I("post.id/d") ? I("post.id/d") : 0;
        $teamItemInfo = D("TeamItem")->where(" id = '$id'  ")->find();
        $item_id = $teamItemInfo['item_id'];
        $team_id = $teamItemInfo['team_id'];

        if (!$this->checkTeamManage($uid, $team_id)) {
            $this->sendError(10103);
            return;
        }

        $ret = D("TeamItemMember")->where(" item_id = '$item_id' and team_id = '$team_id' ")->delete();
        $ret = D("TeamItem")->where(" id = '$id' ")->delete();

        if ($ret) {
            $teamInfo = D("Team")->where(" id = '$team_id' ")->find();
            D("ItemChangeLog")->addLog($login_user['uid'], $item_id, 'unbound', 'team', $team_id, $teamInfo['team_name']);
            $this->sendResult($ret);
        } else {
            $return['error_code'] = 10103;
            $return['error_message'] = 'request  fail';
            $this->sendResult($return);
        }
    }
}
