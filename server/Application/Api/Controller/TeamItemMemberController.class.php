<?php

namespace Api\Controller;

use Think\Controller;
/*
    成员组和项目绑定后，每个人的绑定情况
 */

class TeamItemMemberController extends BaseController
{

    //添加和编辑
    //由于初始添加成员的时候就已经有了记录，所以本方法是编辑
    public function save()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];

        $id = I("post.id/d");
        $member_group_id = I("post.member_group_id/d");
        $cat_id = I("post.cat_id/d");

        $teamItemMemberInfo = D("TeamItemMember")->where(" id = '$id'  ")->find();
        $item_id = $teamItemMemberInfo['item_id'];
        $team_id = $teamItemMemberInfo['team_id'];

        if (!$this->checkTeamManage($uid, $team_id)) {
            $this->sendError(10103);
            return;
        }

        $teamInfo = D("Team")->where(" id = '$team_id'  ")->find();

        if (isset($_POST['member_group_id'])) {
            $return = D("TeamItemMember")->where(" id = '$id' ")->save(array("member_group_id" => $member_group_id));
        }
        if (isset($_POST['cat_id'])) {
            $return = D("TeamItemMember")->where(" id = '$id' ")->save(array("cat_id" => $cat_id));
        }
        $this->sendResult($return);
    }

    //获取列表
    public function getList()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];

        $item_id = I("item_id/d");
        $team_id = I("team_id/d");


        if (!$this->checkTeamManage($uid, $team_id)) {
            $this->sendError(10103);
            return;
        }

        $ret = D("TeamItemMember")->where(" item_id = '$item_id'  and team_id = '$team_id' ")->select();

        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
                $value['cat_name'] = '所有目录';
                if ($value['cat_id'] > 0) {
                    $row = D("Catalog")->where(" cat_id = '$value[cat_id]' ")->find();
                    if ($row &&  $row['cat_name']) {
                        $value['cat_name'] =  $row['cat_name'];
                    }
                }
            }
            $this->sendResult($ret);
        } else {
            $this->sendResult(array());
        }
    }
}
