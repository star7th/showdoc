<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;
use App\Model\MessageContent;

/**
 * 消息模型（开源版：使用单表 message，不支持分表）
 */
class Message
{
    /**
     * 获取提醒消息列表
     *
     * @param int $toUid 接收者用户 ID
     * @param int $page 页码
     * @param int $count 每页数量
     * @param int $status 状态（-2 表示不筛选，0 未读，1 已读）
     * @return array 包含 total 和 list
     */
    public static function getRemindList(int $toUid, int $page = 1, int $count = 15, int $status = -2): array
    {
        if ($toUid <= 0) {
            return ['total' => 0, 'list' => []];
        }

        $query = DB::table('message')
            ->where('to_uid', $toUid)
            ->where('message_type', 'remind');

        if ($status > -2) {
            $query->where('status', $status);
        }

        // 获取总数
        $total = $query->count();

        // 获取列表
        $list = (clone $query)
            ->orderBy('id', 'desc')
            ->offset(($page - 1) * $count)
            ->limit($count)
            ->get()
            ->all();

        // 渲染每条消息
        $result = [];
        foreach ($list as $row) {
            $result[] = self::renderOne((array) $row);
        }

        return [
            'total' => (int) $total,
            'list'  => $result,
        ];
    }

    /**
     * 渲染单条消息（补充完整信息）
     *
     * @param array $one 消息数据
     * @return array 渲染后的消息数据
     */
    public static function renderOne(array $one): array
    {
        $messageContentId = (int) ($one['message_content_id'] ?? 0);
        $fromUid = (int) ($one['from_uid'] ?? 0);

        if ($messageContentId > 0) {
            // 获取消息内容（开源版使用单表）
            $messageContent = DB::table('message_content')
                ->where('id', $messageContentId)
                ->first();

            if ($messageContent) {
                $one['object_type'] = $messageContent->object_type ?? '';
                $one['object_id'] = (int) ($messageContent->object_id ?? 0);
                $one['action_type'] = $messageContent->action_type ?? '';
                $one['message_content'] = $messageContent->message_content ?? '';
                $one['from_name'] = $messageContent->from_name ?? '';

                // 如果是页面类型，补充页面信息
                if (($one['object_type'] ?? '') === 'page') {
                    $pageId = (int) ($one['object_id'] ?? 0);
                    if ($pageId > 0) {
                        $page = DB::table('page')
                            ->where('page_id', $pageId)
                            ->first();

                        if ($page) {
                            $itemId = (int) $page->item_id;
                            $pageData = \App\Model\Page::findById($pageId);
                            if ($pageData) {
                                $pageArray = (array) $pageData;
                                unset($pageArray['page_content']); // 不返回内容
                                $one['page_data'] = $pageArray;
                            }
                        }
                    }
                }
            }
        }

        return $one;
    }

    /**
     * 添加消息（兼容旧接口）
     *
     * @param int $fromUid 发送者用户 ID
     * @param string $fromName 发送者名称
     * @param int $toUid 接收者用户 ID
     * @param string $messageType 消息类型
     * @param string $messageContent 消息内容
     * @param string $actionType 动作类型
     * @param string $objectType 对象类型
     * @param int $objectId 对象 ID
     * @return int 消息 ID，失败返回 0
     */
    public static function addMsg(
        int $fromUid,
        string $fromName,
        int $toUid,
        string $messageType,
        string $messageContent,
        string $actionType,
        string $objectType,
        int $objectId
    ): int {
        if ($fromUid <= 0 || $toUid <= 0) {
            return 0;
        }

        try {
            // 先添加消息内容（开源版使用单表）
            $messageContentId = DB::table('message_content')->insertGetId([
                'from_uid'         => $fromUid,
                'from_name'        => $fromName,
                'message_type'     => $messageType,
                'message_content'  => $messageContent,
                'action_type'      => $actionType,
                'object_type'      => $objectType,
                'object_id'        => $objectId,
                'addtime'          => date('Y-m-d H:i:s'),
            ]);

            // 再添加消息记录（开源版使用单表）
            $msgId = DB::table('message')->insertGetId([
                'from_uid'            => $fromUid,
                'to_uid'              => $toUid,
                'message_type'        => $messageType,
                'message_content_id'  => $messageContentId,
                'status'              => 0,
                'addtime'             => date('Y-m-d H:i:s'),
                'readtime'            => date('Y-m-d H:i:s'),
            ]);

            return (int) $msgId;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
