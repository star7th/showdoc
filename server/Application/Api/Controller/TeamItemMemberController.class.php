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
        $cat_ids = I("post.cat_ids"); // 逗号分隔的多目录

        $teamItemMemberInfo = D("TeamItemMember")->where(array('id' => $id))->find();
        $item_id = $teamItemMemberInfo['item_id'];
        $team_id = $teamItemMemberInfo['team_id'];

        if (!$this->checkTeamManage($uid, $team_id)) {
            $this->sendError(10103);
            return;
        }

        $teamInfo = D("Team")->where(array('id' => $team_id))->find();

        if (isset($_POST['member_group_id'])) {
            $return = D("TeamItemMember")->where(array('id' => $id))->save(array("member_group_id" => $member_group_id));
        }
        if (isset($_POST['cat_id'])) {
            $return = D("TeamItemMember")->where(array('id' => $id))->save(array("cat_id" => $cat_id));
        }
        if (isset($_POST['cat_ids'])) {
            $ids = array();
            if (is_array($cat_ids)) {
                $ids = $cat_ids;
            } else if (is_string($cat_ids)) {
                if (strpos($cat_ids, ',') !== false) {
                    $ids = preg_split('/\s*,\s*/', trim($cat_ids));
                } else if (ctype_digit($cat_ids)) {
                    $ids = array(intval($cat_ids));
                }
            }
            $ids2 = array();
            if (!empty($ids)) {
                foreach ($ids as $v) {
                    $v = intval($v);
                    if ($v <= 0) continue;
                    $cat = D("Catalog")->where("cat_id = '%d' and item_id = '%d' and level = 2", array($v, $item_id))->find();
                    if ($cat) $ids2[] = $v;
                }
                $ids2 = array_values(array_unique($ids2));
            }
            $return = D("TeamItemMember")->where(array('id' => $id))->save(array("cat_ids" => !empty($ids2) ? implode(',', $ids2) : ''));
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

        $ret = D("TeamItemMember")->where(" item_id = '%d'  and team_id = '%d' ", array($item_id, $team_id))->select();

        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
                $value['cat_name'] = '所有目录';
                // 当存在多目录时，简单展示为“多个目录”；同时为前端多选预填 cat_ids 数组
                if (!empty($value['cat_ids'])) {
                    $value['cat_name'] = '多个目录';
                    $str = (string)$value['cat_ids'];
                    $ids = array();
                    if (strpos($str, ',') !== false) {
                        $ids = preg_split('/\s*,\s*/', trim($str));
                    } else if (ctype_digit($str)) {
                        $ids = array(intval($str));
                    }
                    $value['cat_ids'] = array_values(array_unique(array_map('intval', $ids)));
                } else if ($value['cat_id'] > 0) {
                    $row = D("Catalog")->where(array('cat_id' => $value['cat_id']))->find();
                    if ($row &&  $row['cat_name']) {
                        $value['cat_name'] =  $row['cat_name'];
                    }
                    $value['cat_ids'] = array(intval($value['cat_id']));
                } else {
                    $value['cat_ids'] = array();
                }
                $uid = $value['member_uid'];
                $row = D("User")->where(array('uid' => $uid))->find();
                $value['name'] = $row['name'];
            }
            $this->sendResult($ret);
        } else {
            $this->sendResult(array());
        }
    }
}
