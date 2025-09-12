<?php

namespace Api\Controller;

use Think\Controller;
/*
    团队管理
 */

class TeamController extends BaseController
{

    //添加和编辑
    public function save()
    {
        $login_user = $this->checkLogin();

        $team_name = I("post.team_name");
        $id = I("post.id/d");
        $uid = $login_user['uid'];

        if ($id) {
            if (!$this->checkTeamManage($uid, $id)) {
                $this->sendError(10103);
                return;
            }
            D("Team")->where(array('id' => $id))->save(array("team_name" => $team_name));
        } else {
            $data['username'] = $login_user['username'];
            $data['uid'] = $login_user['uid'];
            $data['team_name'] = $team_name;
            $data['addtime'] = time();
            $id = D("Team")->add($data);
        }

        $return = D("Team")->where(array('id' => $id))->find();

        if (!$return) {
            $return['error_code'] = 10103;
            $return['error_message'] = 'request  fail';
        }

        $this->sendResult($return);
    }

    //获取列表
    public function getList()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        if ($uid > 0) {
            $ret = D("Team")->where(" uid = '%d' or id in ( select team_id from team_member where member_uid = '%d'   )  ", array($uid, $uid))->order(" addtime desc  ")->select();
        }
        if ($ret) {
            foreach ($ret as $key => &$value) {
                // 检测管理权限
                if ($this->checkTeamManage($uid, $value['id'])) {
                    $value['team_manage'] = 1;
                } else {
                    $value['team_manage'] = 0;
                }
                //获取该团队成员数
                $value['memberCount'] = D("TeamMember")->where(array('team_id' => $value['id']))->count();

                //获取该团队涉及项目数
                $value['itemCount'] = D("TeamItem")->where(" team_id = '%d' and item.is_del = 0 ", array($value['id']))->join("left join item on item.item_id = team_item.item_id")->count();

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
        $id = I("post.id/d") ? I("post.id/d") : 0;
        $login_user = $this->checkLogin();
        if ($id && $login_user['uid']) {
            $ret = D("Team")->where(" id = '%d' and uid = '%d'", array($id, $login_user['uid']))->delete();
        }
        if ($ret) {
            D("TeamItem")->where(array('team_id' => $id))->delete();
            D("TeamItemMember")->where(array('team_id' => $id))->delete();
            D("TeamMember")->where(array('team_id' => $id))->delete();
            $this->sendResult($ret);
        } else {
            $return['error_code'] = 10103;
            $return['error_message'] = 'request  fail';
            $this->sendResult($return);
        }
    }

    //转让团队
    public function attorn()
    {
        $login_user = $this->checkLogin();

        $username = I("post.username");
        $team_id = I("post.team_id/d");
        $password = I("post.password");

        $team  = D("Team")->where("id = '%d' and uid = '%d' ", array($team_id, $login_user['uid']))->find();

        if (!$team) {
            $this->sendError(10101);
            return;
        }

        if (!D("User")->checkLogin($login_user['username'], $password)) {
            $this->sendError(10208);
            return;
        }

        $member = D("User")->where(array('username' => $username))->find();

        if (!$member) {
            $this->sendError(10209);
            return;
        }
        $data = array();
        $data['username'] = $member['username'];
        $data['uid'] = $member['uid'];
        D("Team")->where(array('id' => $team_id))->save($data);

        //读取出该团队下的所有项目，准备转让
        $items = D("TeamItem")->where(array('team_id' => $team_id))->select();
        foreach ($items as $key => $value) {
            D("Item")->where(array('item_id' => $value['item_id']))->save($data);
        }

        $this->sendResult(array());
    }


    //由当前登录用户主动选择退出团队
    public function exitTeam()
    {
        $id = I("post.id/d") ? I("post.id/d") : 0;
        $login_user = $this->checkLogin();

        $teamInfo = D("Team")->where(array('id' => $id))->find();
        $ret = D("TeamItemMember")->where(" member_uid = '%d' and  team_id = '%d' ", array($login_user['uid'], $id))->delete();
        $ret = D("TeamMember")->where("  member_uid = '%d' and  team_id = '%d' ", array($login_user['uid'], $id))->delete();
        $this->sendResult(array());
    }
}
