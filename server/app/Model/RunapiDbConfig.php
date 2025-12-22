<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class RunapiDbConfig
{
    /**
     * 添加数据库配置
     *
     * @param array $data 配置数据
     * @return int|false 返回配置 ID，失败返回 false
     */
    public static function add(array $data)
    {
        if (empty($data['item_id']) || empty($data['env_id'])) {
            return false;
        }

        try {
            $id = DB::table('runapi_db_config')->insertGetId($data);
            return $id ?: false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 更新数据库配置
     *
     * @param int $configId 配置 ID
     * @param array $data 更新数据
     * @return bool 是否成功
     */
    public static function update(int $configId, array $data): bool
    {
        if ($configId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('runapi_db_config')
                ->where('id', $configId)
                ->update($data);
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据 ID 查找配置
     *
     * @param int $configId 配置 ID
     * @return array|null 配置数据
     */
    public static function findById(int $configId): ?array
    {
        if ($configId <= 0) {
            return null;
        }

        $row = DB::table('runapi_db_config')
            ->where('id', $configId)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * 根据项目 ID 和环境 ID 获取配置列表
     *
     * @param int $itemId 项目 ID
     * @param int $envId 环境 ID
     * @return array 配置列表
     */
    public static function getListByItemIdAndEnvId(int $itemId, int $envId): array
    {
        if ($itemId <= 0 || $envId <= 0) {
            return [];
        }

        $rows = DB::table('runapi_db_config')
            ->where('item_id', $itemId)
            ->where('env_id', $envId)
            ->orderBy('is_default', 'desc')
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
     * 删除配置
     *
     * @param int $configId 配置 ID
     * @return bool 是否成功
     */
    public static function delete(int $configId): bool
    {
        if ($configId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('runapi_db_config')
                ->where('id', $configId)
                ->delete();
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 取消其他配置的默认状态
     *
     * @param int $itemId 项目 ID
     * @param int $envId 环境 ID
     * @param int $excludeConfigId 排除的配置 ID
     * @return bool 是否成功
     */
    public static function unsetDefaultOthers(int $itemId, int $envId, int $excludeConfigId): bool
    {
        if ($itemId <= 0 || $envId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('runapi_db_config')
                ->where('item_id', $itemId)
                ->where('env_id', $envId)
                ->where('id', '!=', $excludeConfigId)
                ->update([
                    'is_default'       => 0,
                    'last_update_time' => date('Y-m-d H:i:s'),
                ]);
            return $affected >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
