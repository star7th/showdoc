<?php

namespace Api\Controller;

use Think\Controller;

class MessageController extends BaseController
{


    // 快速获取未读的消息
    public function getUnread()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        $return = array(
            'remind' => array(), // 提醒类的消息
            'announce' => array(),  // 公告类的消息
        );

        // 先尝试获取提醒类消息
        // 因为提醒类消息是会在Message表里写上未读标志的，所以可以先尝试读Message有没有未读消息。这样就能过滤掉大量无提醒的用户的请求
        $array = D("Message")->where(array(
            'to_uid' => $uid,
            'message_type' => 'remind',
            'status' => 0
        ))->find();
        if ($array) {
            // 如果有未读的，再组装更多信息
            $list = D("Message")->getRemindList($uid, 1, 1, 0);
            if ($list && $list['list']) {
                $return['remind'] = $list['list'][0];
            }
        }

        // 尝试获取公告类的未读消息
        // 先把用户已读了的公告id读取出来，再和公告id整体比较，那么除开已读的，剩下的就是未读的了
        $array = D("Message")->where(" to_uid = '%d' and message_type='%s' ", array($uid, 'announce'))->select();
        $message_content_id_array = array(0); // 初始化
        if ($array) {
            // 把id组成条件，方便后面的sql查询
            foreach ($array as $key => $value) {
                $message_content_id_array[] = $value['message_content_id'];
            }
        }

        $reg_time = date("Y-m-d H:i:s", $login_user['reg_time']);
        // 管理员无视注册时间限制；普通用户仅读取注册时间之后的公告
        $isAdmin = $this->checkAdmin(false);
        // 支持多种公告类型：
        // announce          旧版公告（兼容保留）
        // announce_web      ShowDoc 网页端公告
        // announce_runapi   RunApi 客户端公告
        // announce_all      两端通用公告
        $where = " message_type in ('announce','announce_web','announce_runapi','announce_all') ";
        if (!$isAdmin) {
            $where .= " and addtime > '$reg_time' ";
        }
        if (!empty($message_content_id_array)) {
            $where .= " and id not in (" . implode(',', $message_content_id_array) . ") ";
        }
        $announce_array = D("MessageContent")->where($where)->find();
        if ($announce_array) {
            $announce_array['message_content_id'] = $announce_array['id'];
            $return['announce'] = $announce_array;
        }

        $this->sendResult($return);
    }

    //获取公告类型消息列表
    public function getAnnouncementList()
    {
        $login_user = $this->checkLogin();
        $uid =  $login_user['uid'];

        $reg_time = date("Y-m-d H:i:s", $login_user['reg_time']);
        // 管理员无视注册时间限制；普通用户仅读取注册时间之后的公告
        $isAdmin = $this->checkAdmin(false);
        $where = " message_type in ('announce','announce_web','announce_runapi','announce_all') ";
        if (!$isAdmin) {
            $where .= " and addtime > '$reg_time' ";
        }
        $message_announce = D("MessageContent")->where($where)->order(" id desc ")->select();

        if ($message_announce) {
            // 获取已读未读状态
            foreach ($message_announce as $key => $value) {
                $array = D("Message")->where(array('to_uid' => $uid, 'message_type' => 'announce', 'message_content_id' => $value['id']))->find();
                // 存在记录就是已读。不存在就是未读
                if ($array) {
                    $message_announce[$key]['status'] = 1;
                } else {
                    $message_announce[$key]['status'] = 0;
                }
                $message_announce[$key]['message_content_id'] = $message_announce[$key]['id'];
            }
        }

        //由于公告不会很多，所以不分页了。全部返回给前端

        $this->sendResult((array)$message_announce);
    }



    //设置消息已读
    public function setRead()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        $message_content_id = I("message_content_id/d") ? I("message_content_id/d") : 0;
        $from_uid = I("from_uid/d") ? I("from_uid/d") : 0; // 开源版其实不需要用到此字段
        $array = D("Message")->where("to_uid = '%d' and message_content_id = '%d' ", array($uid, $message_content_id))->find();
        if ($array) {
            D("Message")->where("to_uid = '%d' and message_content_id = '%d' ", array($uid, $message_content_id))->save(array("status" => 1));
        } else {
            if ($message_content_id) {
                // 如果不存在，则可能是公告类型。
                D("Message")->add(array(
                    "from_uid" => 0,
                    "to_uid" => $uid,
                    "message_type" => 'announce',
                    "message_content_id" => $message_content_id,
                    "status" => 1,
                    "addtime" => date("Y-m-d H:i:s"),
                    "readtime" => date("Y-m-d H:i:s")

                ));
            }
        }

        $this->sendResult(array());
    }

    public function delete()
    {
        $login_user = $this->checkLogin();
        $uid =  $login_user['uid'];
        $message_content_id = I("message_content_id/d") ? I("message_content_id/d") : 0;
        D("Message")->where(" to_uid = '%d' and message_content_id = '%d' ", array($uid, $message_content_id))->save(array(
            "status" => -1,
        ));
        $this->sendResult(array());
    }

    //获取提醒型消息列表
    public function getRemindList()
    {
        $page = I("page/d") ? I("page/d") : 1;
        $count = I("count/d") ? I("count/d") : 15;
        $login_user = $this->checkLogin();

        $list = D("Message")->getRemindList($login_user['uid'], $page, $count);
        $list = $list ? $list : array();
        $this->sendResult($list);
    }

    // 快速获取未读的提醒类消息
    public function getUnreadRemind()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        $return = array();
        $array = D("Message")->where(" to_uid = '%d' and status = %d ", array($uid, 0))->find();
        if ($array) {
            // 如果有未读的，再组装更多信息
            $list = D("Message")->getRemindList($uid, 1, 1, 0);
            if ($list && $list['list']) {
                $return = $list['list'][0];
            }
        }
        $this->sendResult($return);
    }
}
