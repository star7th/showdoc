<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class ItemSort
{
    /**
     * 根据项目组 ID 删除排序记录
     *
     * @param int $itemGroupId 项目组 ID
     * @return bool 是否成功
     */
    public static function deleteByItemGroupId(int $itemGroupId): bool
    {
        if ($itemGroupId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('item_sort')
                ->where('item_group_id', $itemGroupId)
                ->delete();
            return $affected >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
