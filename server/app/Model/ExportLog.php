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

        // 根据数据库类型使用不同的 SQL 语法
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            // SQLite 语法：addtime 是时间戳，需要转换为日期进行比较
            $whereRaw = "strftime('%Y-%m-%d', addtime, 'unixepoch') = date('now')";
        } else {
            // MySQL 语法：addtime 是时间戳，使用 FROM_UNIXTIME 转换
            $whereRaw = "to_days(FROM_UNIXTIME(addtime)) = to_days(now())";
        }

        return (int) DB::table('export_log')
            ->where('uid', $uid)
            ->where('export_type', $exportType)
            ->whereRaw($whereRaw)
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

        // 根据数据库类型使用不同的 SQL 语法
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            // SQLite 语法：addtime 是时间戳，计算 N 天前的时间戳
            $intervalDays = $days - 1;
            $daysAgoTimestamp = time() - ($intervalDays * 24 * 60 * 60);
            $whereRaw = "addtime >= ?";
            $bindings = [$daysAgoTimestamp];
        } else {
            // MySQL 语法：addtime 是时间戳，使用 UNIX_TIMESTAMP 转换
            $whereRaw = "addtime >= UNIX_TIMESTAMP(DATE_SUB(CURDATE(),INTERVAL ? DAY))";
            $bindings = [$days - 1];
        }

        return (int) DB::table('export_log')
            ->where('uid', $uid)
            ->where('export_type', $exportType)
            ->whereRaw($whereRaw, $bindings)
            ->count();
    }
}
