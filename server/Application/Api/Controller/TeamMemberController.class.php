<?php

namespace Api\Controller;

use Think\Controller;
/*
    团队成员管理
 */

class TeamMemberController extends BaseController
{

    //添加和编辑
    public function save()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];

        $member_username = I("member_username");
        $team_id = I("post.team_id/d");
        $team_member_group_id = I("post.team_member_group_id/d") ? I("post.team_member_group_id/d") : 1;

        if (!$this->checkTeamManage($uid, $team_id)) {
            $this->sendError(10103);
            return;
        }

        $teamInfo = D("Team")->where(" id = '$team_id'  ")->find();

        $member_username_array = explode(",", $member_username);
        foreach ($member_username_array as $key => $value) {
            $memberInfo = D("User")->where(" username = '%s' ", array($value))->find();
            if (!$memberInfo) {
                continue;
            }
            $if_exit = D("TeamMember")->where(" member_uid = '$memberInfo[uid]' and team_id = '$team_id' ")->find();
            if ($if_exit) {
                continue;
            }
            $data = array();
            $data['team_id'] = $team_id;
            $data['member_uid'] = $memberInfo['uid'];
            $data['member_username'] = $memberInfo['username'];
            $data['team_member_group_id'] = $team_member_group_id;
            $data['addtime'] = time();
            $id = D("TeamMember")->add($data);

            //检查该团队已经加入了哪些项目
            $teamItems = D("TeamItem")->where("  team_id = '$team_id' ")->select();
            if ($teamItems) {
                foreach ($teamItems as $key2 => $value2) {
                    $data = array(
                        "team_id" => $team_id,
                        "member_uid" => $memberInfo['uid'],
                        "member_username" => $memberInfo['username'],
                        "item_id" => $value2['item_id'],
                        "member_group_id" => 1, //默认添加的权限为1，即编辑权限
                        "addtime" => time()
                    );
                    D("TeamItemMember")->add($data);
                }
            }
        }

        $return = D("TeamMember")->where(" id = '$id' ")->find();

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
        $team_id = I("team_id/d");

        // 权限判断。团队管理者和团队成员可以看到该列表
        if (!$this->checkTeamManage($uid, $team_id) && !D("TeamMember")->where(" member_uid = '$uid' and team_id = '$team_id' ")->find()) {
            $this->sendError(10103);
            return;
        }

        $teamInfo = D("Team")->where(" id = '$team_id' ")->find();

        if ($login_user['uid'] > 0) {
            $ret = D("TeamMember")->where(" team_id = '$team_id' ")->join(" left join user on user.uid = team_member.member_uid")->field("team_member.* , user.name as name")->order(" addtime desc  ")->select();
        }
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
        $teamMemberInfo = D("TeamMember")->where(" id = '$id'  ")->find();
        $team_id = $teamMemberInfo['team_id'];

        if (!$this->checkTeamManage($uid, $team_id)) {
            $this->sendError(10103);
            return;
        }

        $teamInfo = D("Team")->where(" id = '$team_id' ")->find();
        $ret = D("TeamItemMember")->where(" member_uid = '$teamMemberInfo[member_uid]' and  team_id = '$team_id' ")->delete();
        $ret = D("TeamMember")->where(" id = '$id' ")->delete();


        if ($ret) {
            $this->sendResult($ret);
        } else {
            $return['error_code'] = 10103;
            $return['error_message'] = 'request  fail';
            $this->sendResult($return);
        }
    }
}
