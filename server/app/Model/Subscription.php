<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * 订阅模型（开源版：使用单表 subscription，不支持分表）
 */
class Subscription
{
    /**
     * 添加订阅
     *
     * @param int $uid 用户 ID
     * @param int $objectId 对象 ID
     * @param string $objectType 对象类型（page 等）
     * @param string $actionType 操作类型（update 等）
     * @return int|false 订阅 ID，失败返回 false
     */
    public static function addSub(int $uid, int $objectId, string $objectType, string $actionType)
    {
        if ($uid <= 0 || $objectId <= 0) {
            return false;
        }

        // 检测是否已经存在订阅
        $existing = DB::table('subscription')
            ->where('uid', $uid)
            ->where('object_id', $objectId)
            ->where('object_type', $objectType)
            ->where('action_type', $actionType)
            ->first();

        if ($existing) {
            // 已存在，返回现有 ID
            return (int) $existing->id;
        }

        // 添加新订阅
        try {
            $id = DB::table('subscription')->insertGetId([
                'uid' => $uid,
                'object_id' => $objectId,
                'object_type' => $objectType,
                'action_type' => $actionType,
                'sub_time' => date('Y-m-d H:i:s'),
            ]);

            return (int) $id;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 删除订阅
     *
     * @param int $uid 用户 ID
     * @param int $objectId 对象 ID
     * @param string $objectType 对象类型
     * @param string $actionType 操作类型
     * @return bool 是否成功
     */
    public static function deleteSub(int $uid, int $objectId, string $objectType, string $actionType): bool
    {
        if ($uid <= 0 || $objectId <= 0) {
            return false;
        }

        try {
            $deleted = DB::table('subscription')
                ->where('uid', $uid)
                ->where('object_id', $objectId)
                ->where('object_type', $objectType)
                ->where('action_type', $actionType)
                ->delete();

            return $deleted > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据对象 ID 获取订阅列表
     *
     * @param int $objectId 对象 ID
     * @param string $objectType 对象类型
     * @param string $actionType 操作类型
     * @return array
     */
    public static function getListByObjectId(int $objectId, string $objectType, string $actionType): array
    {
        if ($objectId <= 0) {
            return [];
        }

        $list = DB::table('subscription')
            ->where('object_id', $objectId)
            ->where('object_type', $objectType)
            ->where('action_type', $actionType)
            ->get()
            ->all();

        $result = [];
        foreach ($list as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }
}

