<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class Recycle
{
    /**
     * 添加回收站记录
     *
     * @param array $data 回收站数据
     * @return bool 是否成功
     */
    public static function add(array $data): bool
    {
        try {
            $insertData = [
                'item_id' => (int) ($data['item_id'] ?? 0),
                'page_id' => (int) ($data['page_id'] ?? 0),
                'page_title' => (string) ($data['page_title'] ?? ''),
                'del_by_uid' => (int) ($data['del_by_uid'] ?? 0),
                'del_by_username' => (string) ($data['del_by_username'] ?? ''),
                'del_time' => (int) ($data['del_time'] ?? time()),
            ];

            // 检查是否已存在（避免重复插入）
            $exists = DB::table('recycle')
                ->where('item_id', $insertData['item_id'])
                ->where('page_id', $insertData['page_id'])
                ->first();

            if ($exists) {
                // 如果已存在，更新记录
                DB::table('recycle')
                    ->where('item_id', $insertData['item_id'])
                    ->where('page_id', $insertData['page_id'])
                    ->update($insertData);
                return true;
            }

            // 插入新记录
            DB::table('recycle')->insert($insertData);
            return true;
        } catch (\Throwable $e) {
            error_log("Recycle::add error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取回收站列表
     *
     * @param int $itemId 项目 ID
     * @return array 回收站列表
     */
    public static function getList(int $itemId): array
    {
        if ($itemId <= 0) {
            return [];
        }

        $rows = DB::table('recycle')
            ->where('item_id', $itemId)
            ->orderBy('del_time', 'desc')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $data = (array) $row;
            $data['del_time'] = date('Y-m-d H:i:s', (int) ($data['del_time'] ?? time()));
            $result[] = $data;
        }

        return $result;
    }

    /**
     * 删除回收站记录
     *
     * @param int $itemId 项目 ID
     * @param int $pageId 页面 ID
     * @return bool 是否成功
     */
    public static function delete(int $itemId, int $pageId): bool
    {
        if ($itemId <= 0 || $pageId <= 0) {
            return false;
        }

        try {
            $deleted = DB::table('recycle')
                ->where('item_id', $itemId)
                ->where('page_id', $pageId)
                ->delete();
            return $deleted > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取旧的已删除页面（超过指定天数）
     *
     * @param int $days 天数
     * @return array 页面列表
     */
    public static function getOldDeletedPages(int $days): array
    {
        if ($days <= 0) {
            return [];
        }

        $time = time() - ($days * 24 * 60 * 60);
        $rows = DB::table('recycle')
            ->where('del_time', '<', $time)
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    /**
     * 根据 ID 删除回收站记录
     *
     * @param int $id 记录 ID
     * @return bool 是否成功
     */
    public static function deleteById(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $deleted = DB::table('recycle')
                ->where('id', $id)
                ->delete();
            return $deleted > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
