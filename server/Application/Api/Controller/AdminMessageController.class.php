<?php

namespace Api\Controller;

use Think\Controller;

class AdminMessageController extends BaseController
{
    // 管理员发布系统公告（开源版无分表）
    public function addAnnouncement()
    {
        $login_user = $this->checkLogin();
        $this->checkAdmin();

        $message_content = I("post.message_content");
        $send_at = I("post.send_at"); // 可选，格式：Y-m-d H:i:s

        if (!$message_content) {
            $this->sendError(10101, '参数错误：message_content 不能为空');
            return;
        }

        // 基础安全过滤：去除危险标签与协议
        $message_content = $this->sanitizeHtml($message_content);
        // 将换行转换为 <br>
        $message_content = nl2br($message_content);

        $addtime = $send_at ? $send_at : date("Y-m-d H:i:s");

        $insert = array(
            "from_uid" => 0,
            "from_name" => '系统公告',
            "message_type" => 'announce',
            "message_content" => $message_content,
            "action_type" => '',
            "object_type" => '',
            "object_id" => 0,
            "addtime" => $addtime,
        );

        $id = D("MessageContent")->add($insert);
        if (!$id) {
            $this->sendError(10101, '保存失败');
            return;
        }

        $this->sendResult(array('id' => intval($id)));
    }

    // 管理员查看公告列表
    public function listAnnouncements()
    {
        $login_user = $this->checkLogin();
        $this->checkAdmin();

        $page = I("page/d") ? I("page/d") : 1;
        $count = I("count/d") ? I("count/d") : 20;

        $list = D("MessageContent")
            ->where(" message_type = 'announce' ")
            ->order(" id desc ")
            ->page(" $page , $count ")
            ->select();

        $this->sendResult((array)$list);
    }

    // 简单HTML过滤（保留少量安全标签，移除脚本协议）
    private function sanitizeHtml($html)
    {
        $allowed = '<p><br><ul><ol><li><strong><b><em><i><code><pre><h1><h2><h3><h4><blockquote><a>';
        $clean = strip_tags($html, $allowed);
        // 去掉 a 标签中的 javascript: 协议
        $clean = preg_replace('/javascript\s*:/i', '', $clean);
        return $clean;
    }
}


