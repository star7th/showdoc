<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class RunapiEnvSelectd
{
    /**
     * 添加选中的环境
     *
     * @param array $data 数据
     * @return int|false 返回 ID，失败返回 false
     */
    public static function add(array $data)
    {
        if (empty($data['item_id']) || empty($data['uid']) || empty($data['env_id'])) {
            return false;
        }

        try {
            $id = DB::table('runapi_env_selectd')->insertGetId($data);
            return $id ?: false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据项目 ID 和用户 ID 查找选中的环境
     *
     * @param int $itemId 项目 ID
     * @param int $uid 用户 ID
     * @return array|null 环境数据
     */
    public static function findByItemIdAndUid(int $itemId, int $uid): ?array
    {
        if ($itemId <= 0 || $uid <= 0) {
            return null;
        }

        $row = DB::table('runapi_env_selectd')
            ->where('item_id', $itemId)
            ->where('uid', $uid)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * 删除选中的环境
     *
     * @param int $itemId 项目 ID
     * @param int $uid 用户 ID
     * @return bool 是否成功
     */
    public static function deleteByItemIdAndUid(int $itemId, int $uid): bool
    {
        if ($itemId <= 0 || $uid <= 0) {
            return false;
        }

        try {
            $affected = DB::table('runapi_env_selectd')
                ->where('item_id', $itemId)
                ->where('uid', $uid)
                ->delete();
            return $affected >= 0; // 即使没有删除（affected=0）也算成功
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据环境 ID 删除
     *
     * @param int $envId 环境 ID
     * @return bool 是否成功
     */
    public static function deleteByEnvId(int $envId): bool
    {
        if ($envId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('runapi_env_selectd')
                // 注意：这里应根据 env_id 删除，而不是主键 id
                ->where('env_id', $envId)
                ->delete();
            return $affected >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
