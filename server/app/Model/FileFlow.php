<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * 开源版文件流量统计模型（兼容主版 FileFlow，用于记录附件访问流量）。
 *
 * 说明：
 * - 如果数据库中不存在 file_flow 表，调用这些方法也只会返回 false / 0，不会抛异常；
 * - 这样即使开源版用户没有建表，也不会影响附件访问功能本身。
 */
class FileFlow
{
    /**
     * 获取用户的本月已使用流量
     *
     * @param int $uid 用户 ID
     * @return int 已使用流量（字节）
     */
    public static function getUserFlow(int $uid): int
    {
        if ($uid <= 0) {
            return 0;
        }

        $month = date('Y-m');

        try {
            $fileFlow = DB::table('file_flow')
                ->where('uid', $uid)
                ->where('date_month', $month)
                ->first();
        } catch (\Throwable $e) {
            // 如果表不存在或查询失败，直接返回 0，避免影响主流程
            return 0;
        }

        if ($fileFlow) {
            return (int) ($fileFlow->used ?? 0);
        }

        // 如果不存在，尝试创建记录（忽略并发下的重复键错误）
        try {
            DB::table('file_flow')->insert([
                'uid'        => $uid,
                'used'       => 0,
                'date_month' => $month,
            ]);
        } catch (\Throwable $e) {
            // 忽略错误
        }

        return 0;
    }

    /**
     * 记录用户流量
     *
     * @param int $uid 用户 ID
     * @param int $fileSize 文件大小（字节）
     * @return bool 是否成功
     */
    public static function recordUserFlow(int $uid, int $fileSize): bool
    {
        if ($uid <= 0 || $fileSize <= 0) {
            return false;
        }

        $month = date('Y-m');
        $used  = self::getUserFlow($uid);

        try {
            $affected = DB::table('file_flow')
                ->where('uid', $uid)
                ->where('date_month', $month)
                ->update(['used' => $used + $fileSize]);

            return $affected > 0;
        } catch (\Throwable $e) {
            // 表不存在或更新失败时，不影响主流程
            return false;
        }
    }
}


