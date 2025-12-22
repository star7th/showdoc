<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class Mock
{
    /**
     * 根据页面ID查找Mock数据
     *
     * @param int $pageId 页面ID
     * @return array|null
     */
    public static function findByPageId(int $pageId): ?array
    {
        if ($pageId <= 0) {
            return null;
        }

        $row = DB::table('mock')
            ->where('page_id', $pageId)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * 根据唯一key查找Mock数据
     *
     * @param string $uniqueKey 唯一key
     * @return array|null
     */
    public static function findByUniqueKey(string $uniqueKey): ?array
    {
        if (empty($uniqueKey)) {
            return null;
        }

        $row = DB::table('mock')
            ->where('unique_key', $uniqueKey)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * 根据项目ID和路径查找Mock数据
     *
     * @param int $itemId 项目ID
     * @param string $path 路径
     * @return array|null
     */
    public static function findByItemIdAndPath(int $itemId, string $path): ?array
    {
        if ($itemId <= 0 || empty($path)) {
            return null;
        }

        $row = DB::table('mock')
            ->where('item_id', $itemId)
            ->where('path', $path)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * 保存Mock数据（新建或更新）
     *
     * @param int $pageId 页面ID
     * @param array $data Mock数据
     * @return bool
     */
    public static function saveByPageId(int $pageId, array $data): bool
    {
        if ($pageId <= 0) {
            return false;
        }

        $affected = DB::table('mock')
            ->where('page_id', $pageId)
            ->update($data);

        return $affected >= 0; // 即使没有更新（affected=0）也算成功
    }

    /**
     * 添加Mock数据
     *
     * @param array $data Mock数据
     * @return int|false 返回插入的ID，失败返回false
     */
    public static function add(array $data)
    {
        $id = DB::table('mock')->insertGetId($data);
        return $id ?: false;
    }

    /**
     * 增加查看次数
     *
     * @param int $id Mock记录ID
     * @return bool
     */
    public static function incrementViewTimes(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $affected = DB::table('mock')
            ->where('id', $id)
            ->increment('view_times', 1);

        return $affected > 0;
    }
}
