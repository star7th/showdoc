<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class RunapiFlow
{
    /**
     * 根据 ID 查找流程
     *
     * @param int $id 流程 ID
     * @return object|null 流程对象，不存在返回 null
     */
    public static function findById(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        return DB::table('runapi_flow')
            ->where('id', $id)
            ->first();
    }

    /**
     * 根据项目 ID 获取流程列表
     *
     * @param int $itemId 项目 ID
     * @return array 流程列表
     */
    public static function getListByItemId(int $itemId): array
    {
        if ($itemId <= 0) {
            return [];
        }

        $rows = DB::table('runapi_flow')
            ->where('item_id', $itemId)
            ->orderBy('id', 'desc')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    /**
     * 添加流程
     *
     * @param array $data 流程数据
     * @return int|false 返回插入的ID，失败返回false
     */
    public static function add(array $data)
    {
        try {
            $id = DB::table('runapi_flow')->insertGetId($data);
            return $id ?: false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 更新流程
     *
     * @param int $id 流程 ID
     * @param array $data 更新数据
     * @return bool 是否成功
     */
    public static function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $affected = DB::table('runapi_flow')
                ->where('id', $id)
                ->update($data);
            return $affected >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 删除流程
     *
     * @param int $id 流程 ID
     * @return bool 是否成功
     */
    public static function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $affected = DB::table('runapi_flow')
                ->where('id', $id)
                ->delete();
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
