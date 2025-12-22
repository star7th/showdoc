<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 管理后台消息相关 Api（开源版，无分表）
 */
class AdminMessageController extends BaseController
{
    /**
     * 管理员发布系统公告（兼容旧接口 Api/AdminMessage/addAnnouncement）
     */
    public function addAnnouncement(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $messageContent = $this->getParam($request, 'message_content', '');
        $messageType = trim($this->getParam($request, 'message_type', ''));
        $sendAt = $this->getParam($request, 'send_at', '');

        if (empty($messageContent)) {
            return $this->error($response, 10101, '参数错误：message_content 不能为空');
        }

        // 公告类型白名单与默认值
        $allowTypes = ['announce_web', 'announce_runapi', 'announce_all'];
        if (empty($messageType)) {
            // 兼容旧版：未传类型时默认为网页端公告
            $messageType = 'announce_web';
        }
        if (!in_array($messageType, $allowTypes, true)) {
            $messageType = 'announce_web';
        }

        // 基础安全过滤：去除危险标签与协议
        $messageContent = $this->sanitizeHtml($messageContent);
        // 将换行转换为 <br>
        $messageContent = nl2br($messageContent);

        $addtime = !empty($sendAt) ? $sendAt : date("Y-m-d H:i:s");

        // 开源版：使用单表 message_content，无分表
        $id = DB::table('message_content')->insertGetId([
            'from_uid' => 0,
            'from_name' => '系统公告',
            'message_type' => $messageType,
            'message_content' => $messageContent,
            'action_type' => '',
            'object_type' => '',
            'object_id' => 0,
            'addtime' => $addtime,
        ]);

        if (!$id) {
            return $this->error($response, 10101, '保存失败');
        }

        return $this->success($response, ['id' => (int) $id]);
    }

    /**
     * 管理员查看公告列表（兼容旧接口 Api/AdminMessage/listAnnouncements）
     */
    public function listAnnouncements(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $page = $this->getParam($request, 'page', 1);
        $count = $this->getParam($request, 'count', 20);

        // 开源版：使用单表 message_content，无分表
        $list = DB::table('message_content')
            ->whereIn('message_type', ['announce', 'announce_web', 'announce_runapi', 'announce_all'])
            ->orderBy('id', 'desc')
            ->offset(($page - 1) * $count)
            ->limit($count)
            ->get();

        $result = [];
        foreach ($list as $row) {
            $result[] = (array) $row;
        }

        return $this->success($response, $result);
    }

    /**
     * 简单HTML过滤（保留少量安全标签，移除脚本协议）
     */
    private function sanitizeHtml($html)
    {
        $allowed = '<p><br><ul><ol><li><strong><b><em><i><code><pre><h1><h2><h3><h4><blockquote><a>';
        $clean = strip_tags($html, $allowed);
        // 去掉 a 标签中的 javascript: 协议
        $clean = preg_replace('/javascript\s*:/i', '', $clean);
        return $clean;
    }
}

