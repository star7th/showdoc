<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class Template
{
    /**
     * 添加模板
     *
     * @param array $data 模板数据
     * @return int|false 返回插入的ID，失败返回false
     */
    public static function add(array $data)
    {
        try {
            $id = DB::table('template')->insertGetId($data);
            return $id ?: false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据用户 ID 获取模板列表
     *
     * @param int $uid 用户 ID
     * @return array 模板列表
     */
    public static function getListByUid(int $uid): array
    {
        if ($uid <= 0) {
            return [];
        }

        $rows = DB::table('template')
            ->where('uid', $uid)
            ->orderBy('addtime', 'desc')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    /**
     * 根据 ID 查找模板
     *
     * @param int $id 模板 ID
     * @return object|null 模板对象，不存在返回 null
     */
    public static function findById(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        return DB::table('template')
            ->where('id', $id)
            ->first();
    }

    /**
     * 删除模板
     *
     * @param int $id 模板 ID
     * @return bool 是否成功
     */
    public static function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $affected = DB::table('template')
                ->where('id', $id)
                ->delete();
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
