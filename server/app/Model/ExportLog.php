<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class ExportLog
{
    /**
     * 添加导出日志
     *
     * @param array $data 日志数据
     * @return int|false 返回插入的ID，失败返回false
     */
    public static function add(array $data)
    {
        $id = DB::table('export_log')->insertGetId($data);
        return $id ?: false;
    }

    /**
     * 获取用户当天的导出次数
     *
     * @param int $uid 用户ID
     * @param string $exportType 导出类型（word/markdown等）
     * @return int 导出次数
     */
    public static function getTodayCount(int $uid, string $exportType): int
    {
        if ($uid <= 0) {
            return 0;
        }

        // addtime 统一存 Unix 时间戳，避免 SQLite/PostgreSQL 的日期函数差异
        return (int) DB::table('export_log')
            ->where('uid', $uid)
            ->where('export_type', $exportType)
            ->where('addtime', '>=', strtotime('today'))
            ->count();
    }

    /**
     * 获取用户最近N天的导出次数
     *
     * @param int $uid 用户ID
     * @param string $exportType 导出类型
     * @param int $days 天数（默认3天）
     * @return int 导出次数
     */
    public static function getRecentDaysCount(int $uid, string $exportType, int $days = 3): int
    {
        if ($uid <= 0) {
            return 0;
        }

        // addtime 统一存 Unix 时间戳，避免 SQLite/PostgreSQL 的日期函数差异
        $start = time() - (($days - 1) * 86400);

        return (int) DB::table('export_log')
            ->where('uid', $uid)
            ->where('export_type', $exportType)
            ->where('addtime', '>=', $start)
            ->count();
    }
}
