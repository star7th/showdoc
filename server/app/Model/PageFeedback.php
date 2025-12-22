<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class PageFeedback
{
    /**
     * 根据页面 ID 和反馈类型统计数量
     *
     * @param int $pageId 页面 ID
     * @param int $feedbackType 反馈类型（1=有帮助，2=无帮助）
     * @return int 数量
     */
    public static function countByType(int $pageId, int $feedbackType): int
    {
        if ($pageId <= 0 || ($feedbackType !== 1 && $feedbackType !== 2)) {
            return 0;
        }

        return DB::table('page_feedback')
            ->where('page_id', $pageId)
            ->where('feedback_type', $feedbackType)
            ->count();
    }

    /**
     * 根据用户 ID 查找反馈
     *
     * @param int $pageId 页面 ID
     * @param int $uid 用户 ID
     * @return object|null 反馈记录，不存在返回 null
     */
    public static function findByUid(int $pageId, int $uid): ?object
    {
        if ($pageId <= 0 || $uid <= 0) {
            return null;
        }

        return DB::table('page_feedback')
            ->where('page_id', $pageId)
            ->where('uid', $uid)
            ->first();
    }

    /**
     * 根据客户端 ID 查找反馈
     *
     * @param int $pageId 页面 ID
     * @param string $clientId 客户端 ID
     * @return object|null 反馈记录，不存在返回 null
     */
    public static function findByClientId(int $pageId, string $clientId): ?object
    {
        if ($pageId <= 0 || empty($clientId)) {
            return null;
        }

        return DB::table('page_feedback')
            ->where('page_id', $pageId)
            ->where('client_id', $clientId)
            ->first();
    }

    /**
     * 添加反馈
     *
     * @param array $data 反馈数据
     * @return int|false 返回插入的ID，失败返回false
     */
    public static function add(array $data)
    {
        try {
            $id = DB::table('page_feedback')->insertGetId($data);
            return $id ?: false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 更新反馈
     *
     * @param int $feedbackId 反馈 ID
     * @param array $data 更新数据
     * @return bool 是否成功
     */
    public static function update(int $feedbackId, array $data): bool
    {
        if ($feedbackId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('page_feedback')
                ->where('feedback_id', $feedbackId)
                ->update($data);
            return $affected >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 删除反馈
     *
     * @param int $feedbackId 反馈 ID
     * @return bool 是否成功
     */
    public static function delete(int $feedbackId): bool
    {
        if ($feedbackId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('page_feedback')
                ->where('feedback_id', $feedbackId)
                ->delete();
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
