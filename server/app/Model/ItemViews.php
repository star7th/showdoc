<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class ItemViews
{
    /**
     * 增加项目访问量（项目创建者或成员访问）
     * 
     * 逻辑：按日期记录访问量，如果当天已有记录则更新，否则插入新记录
     * - all_views：总访问量（自增1）
     * - vistor_views：游客访问量（不变）
     * - view_date：访问日期（YYYY-MM-DD）
     *
     * @param int $itemId 项目 ID
     * @return bool
     */
    public static function setIncByOwn(int $itemId): bool
    {
        if ($itemId <= 0) {
            return false;
        }

        try {
            $today = date("Y-m-d");

            // 使用 ON DUPLICATE KEY UPDATE 逻辑
            // 如果当天已有记录则更新 all_views，否则插入新记录
            DB::statement("
                INSERT INTO item_views (item_id, all_views, vistor_views, view_date)
                VALUES (?, 1, 0, ?)
                ON DUPLICATE KEY UPDATE
                all_views = all_views + 1
            ", [$itemId, $today]);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 增加项目访问量（游客访问）
     * 
     * 逻辑：按日期记录访问量，如果当天已有记录则更新，否则插入新记录
     * - all_views：总访问量（自增1）
     * - vistor_views：游客访问量（自增1）
     * - view_date：访问日期（YYYY-MM-DD）
     *
     * @param int $itemId 项目 ID
     * @return bool
     */
    public static function setIncByVistor(int $itemId): bool
    {
        if ($itemId <= 0) {
            return false;
        }

        try {
            $today = date("Y-m-d");

            // 使用 ON DUPLICATE KEY UPDATE 逻辑
            // 如果当天已有记录则更新 all_views 和 vistor_views，否则插入新记录
            DB::statement("
                INSERT INTO item_views (item_id, all_views, vistor_views, view_date)
                VALUES (?, 1, 1, ?)
                ON DUPLICATE KEY UPDATE
                all_views = all_views + 1,
                vistor_views = vistor_views + 1
            ", [$itemId, $today]);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
