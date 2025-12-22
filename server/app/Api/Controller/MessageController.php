<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 消息相关 Api（新架构）。
 */
class MessageController extends BaseController
{
    /**
     * 快速获取未读的消息（兼容旧接口 Api/Message/getUnread）。
     *
     * 功能：
     * - 获取未读的提醒类消息
     * - 获取未读的公告类消息
     */
    public function getUnread(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $regTime = date('Y-m-d H:i:s', (int) ($loginUser['reg_time'] ?? time()));

        $return = [
            'remind'   => [], // 提醒类的消息
            'announce' => [], // 公告类的消息
        ];

        // 先尝试获取提醒类消息（开源版使用单表 message）
        $array = DB::table('message')
            ->where('to_uid', $uid)
            ->where('message_type', 'remind')
            ->where('status', 0)
            ->first();

        if ($array) {
            // 如果有未读的，再组装更多信息
            $list = \App\Model\Message::getRemindList($uid, 1, 1, 0);
            if ($list && !empty($list['list'])) {
                $return['remind'] = $list['list'][0];
            }
        }

        // 尝试获取公告类的未读消息
        // 先把用户已读了的公告id读取出来（开源版使用单表 message）
        $readAnnounces = DB::table('message')
            ->where('to_uid', $uid)
            ->where('message_type', 'announce')
            ->get()
            ->all();

        $messageContentIdArray = [0]; // 初始化
        foreach ($readAnnounces as $value) {
            $messageContentIdArray[] = (int) ($value->message_content_id ?? 0);
        }

        // 检查是否是管理员
        $isAdmin = false;
        $user = \App\Model\User::findById($uid);
        if ($user && (int) ($user->groupid ?? 0) === 1) {
            $isAdmin = true;
        }

        // 构建查询条件（开源版使用单表 message_content）
        $query = DB::table('message_content')
            ->whereIn('message_type', ['announce', 'announce_web', 'announce_runapi', 'announce_all']);

        if (!$isAdmin) {
            $query->where('addtime', '>', $regTime);
        }

        if (!empty($messageContentIdArray)) {
            $query->whereNotIn('id', $messageContentIdArray);
        }

        $announceArray = $query->first();

        if ($announceArray) {
            $data = (array) $announceArray;
            $data['message_content_id'] = $data['id'];
            $return['announce'] = $data;
        }

        return $this->success($response, $return);
    }

    /**
     * 获取公告类型消息列表（兼容旧接口 Api/Message/getAnnouncementList）。
     */
    public function getAnnouncementList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $regTime = date('Y-m-d H:i:s', (int) ($loginUser['reg_time'] ?? time()));

        // 检查是否是管理员
        $isAdmin = false;
        $user = \App\Model\User::findById($uid);
        if ($user && (int) ($user->groupid ?? 0) === 1) {
            $isAdmin = true;
        }

        // 构建查询条件（开源版使用单表 message_content）
        $query = DB::table('message_content')
            ->whereIn('message_type', ['announce', 'announce_web', 'announce_runapi', 'announce_all']);

        if (!$isAdmin) {
            $query->where('addtime', '>', $regTime);
        }

        $messageAnnounce = $query
            ->orderBy('id', 'desc')
            ->get()
            ->all();

        $result = [];
        foreach ($messageAnnounce as $value) {
            $data = (array) $value;

            // 获取已读未读状态（开源版使用单表 message）
            $readRecord = DB::table('message')
                ->where('to_uid', $uid)
                ->where('message_type', 'announce')
                ->where('message_content_id', $data['id'])
                ->first();

            // 存在记录就是已读。不存在就是未读
            $data['status'] = $readRecord ? 1 : 0;
            $data['message_content_id'] = $data['id'];

            $result[] = $data;
        }

        return $this->success($response, $result);
    }

    /**
     * 设置消息已读（兼容旧接口 Api/Message/setRead）。
     */
    public function setRead(Request $request, Response $response): Response
    {
        $messageContentId = $this->getParam($request, 'message_content_id', 0);
        $fromUid = $this->getParam($request, 'from_uid', 0);

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // message_content_id + from_uid 才是联合唯一（开源版使用单表 message）
        $array = DB::table('message')
            ->where('to_uid', $uid)
            ->where('from_uid', $fromUid)
            ->where('message_content_id', $messageContentId)
            ->first();

        if ($array) {
            // 更新为已读
            DB::table('message')
                ->where('to_uid', $uid)
                ->where('message_content_id', $messageContentId)
                ->update(['status' => 1]);
        } else {
            if ($messageContentId > 0) {
                // 如果不存在，则可能是公告类型，创建已读记录
                DB::table('message')->insert([
                    'from_uid'            => 0,
                    'to_uid'              => $uid,
                    'message_type'        => 'announce',
                    'message_content_id'  => $messageContentId,
                    'status'              => 1,
                    'addtime'             => date('Y-m-d H:i:s'),
                    'readtime'            => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return $this->success($response, []);
    }

    /**
     * 删除消息（兼容旧接口 Api/Message/delete）。
     */
    public function delete(Request $request, Response $response): Response
    {
        $messageContentId = $this->getParam($request, 'message_content_id', 0);
        $fromUid = $this->getParam($request, 'from_uid', 0);

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 软删除（标记 status = -1，开源版使用单表 message）
        DB::table('message')
            ->where('to_uid', $uid)
            ->where('from_uid', $fromUid)
            ->where('message_content_id', $messageContentId)
            ->update(['status' => -1]);

        return $this->success($response, []);
    }

    /**
     * 获取提醒型消息列表（兼容旧接口 Api/Message/getRemindList）。
     */
    public function getRemindList(Request $request, Response $response): Response
    {
        $page = $this->getParam($request, 'page', 1);
        $count = $this->getParam($request, 'count', 15);

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 获取提醒消息列表
        $list = \App\Model\Message::getRemindList($uid, $page, $count);

        return $this->success($response, $list ?: ['total' => 0, 'list' => []]);
    }

    /**
     * 旧版快速获取未读提醒类消息的兼容接口（兼容 Api/Message/getUnreadRemind）。
     *
     * 旧实现已弃用并直接返回空数组，新版保持相同行为，建议前端使用 getUnread。
     */
    public function getUnreadRemind(Request $request, Response $response): Response
    {
        // 按旧版逻辑实现：快速获取未读提醒类消息
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 先检查是否存在未读提醒
        $array = DB::table('message')
            ->where('to_uid', $uid)
            ->where('status', 0)
            ->first();

        $return = [];
        if ($array) {
            // 如果有未读的，再组装更多信息（取一条）
            $list = \App\Model\Message::getRemindList($uid, 1, 1, 0);
            if ($list && !empty($list['list'][0])) {
                $return = $list['list'][0];
            }
        }

        return $this->success($response, $return);
    }
}
