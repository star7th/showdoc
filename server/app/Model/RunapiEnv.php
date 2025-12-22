<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class RunapiEnv
{
    /**
     * 添加环境
     *
     * @param array $data 环境数据
     * @return int|false 返回环境 ID，失败返回 false
     */
    public static function add(array $data)
    {
        if (empty($data['item_id']) || empty($data['env_name'])) {
            return false;
        }

        try {
            $id = DB::table('runapi_env')->insertGetId($data);
            return $id ?: false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 更新环境
     *
     * @param int $envId 环境 ID
     * @param int $itemId 项目 ID
     * @param array $data 更新数据
     * @return bool 是否成功
     */
    public static function update(int $envId, int $itemId, array $data): bool
    {
        if ($envId <= 0 || $itemId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('runapi_env')
                ->where('id', $envId)
                ->where('item_id', $itemId)
                ->update($data);
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据 ID 查找环境
     *
     * @param int $envId 环境 ID
     * @return array|null 环境数据
     */
    public static function findById(int $envId): ?array
    {
        if ($envId <= 0) {
            return null;
        }

        $row = DB::table('runapi_env')
            ->where('id', $envId)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * 根据项目 ID 获取环境列表
     *
     * @param int $itemId 项目 ID
     * @return array 环境列表
     */
    public static function getListByItemId(int $itemId): array
    {
        if ($itemId <= 0) {
            return [];
        }

        $rows = DB::table('runapi_env')
            ->where('item_id', $itemId)
            ->orderBy('id', 'asc')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    /**
     * 删除环境
     *
     * @param int $envId 环境 ID
     * @return bool 是否成功
     */
    public static function delete(int $envId): bool
    {
        if ($envId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('runapi_env')
                ->where('id', $envId)
                ->delete();
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
