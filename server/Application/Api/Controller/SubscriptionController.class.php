<?php

namespace Api\Controller;

use Think\Controller;

class SubscriptionController extends BaseController
{


    // 获取页面的订阅人员列表
    public function getPageList()
    {
        $login_user = $this->checkLogin();
        $page_id = I("post.page_id/d");
        $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if (!$this->checkItemEdit($login_user['uid'], $page['item_id'])) {
            $this->sendError(10103);
            return;
        }

        $subscription_array = D("Subscription")->getListByObjectId($page_id, 'page', 'update');
        $subscription_array = $subscription_array ? $subscription_array : array();
        foreach ($subscription_array as $key => $value) {
            $user_array = D("User")->where(" uid = '$value[uid]'  ")->find();
            $subscription_array[$key]['username'] = $user_array['username'];
            $subscription_array[$key]['name'] = $user_array['name'];
        }
        $this->sendResult($subscription_array);
    }


    // 保存页面（或者接口）的订阅信息
    public function savePage()
    {
        $login_user = $this->checkLogin();
        $uids = I("uids");
        $page_id = I("post.page_id/d");
        $page = M("Page")->where(" page_id = '$page_id' ")->find();

        if (!$this->checkItemEdit($login_user['uid'], $page['item_id'])) {
            $this->sendError(10103);
            return;
        }

        $uids_array = explode(',', $uids);
        if ($uids_array) {
            foreach ($uids_array as $key => $value) {
                $s_uid = intval($value);

                $res = D("Subscription")->addSub($s_uid, $page_id, 'page', 'update');
            }
        }

        $this->sendResult(array());
    }

    // 删除页面（或者接口）的订阅信息
    public function deletePage()
    {
        $login_user = $this->checkLogin();

        $uids = I("uids");
        $page_id = I("post.page_id/d");
        $page = M("Page")->where(" page_id = '$page_id' ")->find();

        if (!$this->checkItemEdit($login_user['uid'], $page['item_id'])) {
            $this->sendError(10103);
            return;
        }

        $uids_array = explode(',', $uids);
        if ($uids_array) {
            foreach ($uids_array as $key => $value) {
                $s_uid = intval($value);
                D("Subscription")->deleteSub($s_uid, $page_id, 'page', 'update');
            }
        }

        $this->sendResult(array());
    }
}
