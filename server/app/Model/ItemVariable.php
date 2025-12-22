<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * 项目环境变量模型。
 *
 * 表结构：item_variable
 * - id: 主键
 * - item_id: 项目 ID
 * - env_id: 环境 ID
 * - var_name: 变量名
 * - var_value: 变量值
 * - uid: 用户 ID
 * - addtime: 添加时间
 */
class ItemVariable
{
    /**
     * 保存环境变量（新增或更新）
     *
     * @param int $itemId 项目 ID
     * @param int $envId 环境 ID
     * @param string $varName 变量名
     * @param string $varValue 变量值
     * @param int $uid 用户 ID
     * @return int 变量 ID，失败返回 0
     */
    public static function save(int $itemId, int $envId, string $varName, string $varValue, int $uid): int
    {
        if ($itemId <= 0 || empty($varName)) {
            return 0;
        }

        // 查找是否已存在
        $existing = DB::table('item_variable')
            ->where('item_id', $itemId)
            ->where('env_id', $envId)
            ->where('var_name', $varName)
            ->first();

        if ($existing) {
            // 更新
            $affected = DB::table('item_variable')
                ->where('id', $existing->id)
                ->update([
                    'var_value' => $varValue,
                ]);

            return $affected > 0 ? (int) $existing->id : 0;
        } else {
            // 新增
            try {
                $id = DB::table('item_variable')->insertGetId([
                    'item_id' => $itemId,
                    'env_id' => $envId,
                    'var_name' => $varName,
                    'var_value' => $varValue,
                    'uid' => $uid,
                    'addtime' => time(),
                ]);

                return (int) $id;
            } catch (\Throwable $e) {
                return 0;
            }
        }
    }

    /**
     * 获取环境变量列表
     *
     * @param int $itemId 项目 ID
     * @param int $envId 环境 ID（可选，0 表示所有环境）
     * @return array
     */
    public static function getList(int $itemId, int $envId = 0): array
    {
        if ($itemId <= 0) {
            return [];
        }

        $query = DB::table('item_variable')
            ->where('item_id', $itemId);

        if ($envId > 0) {
            $query->where('env_id', $envId);
        }

        $list = $query->orderBy('addtime', 'asc')
            ->get()
            ->all();

        $result = [];
        foreach ($list as $row) {
            $data = (array) $row;
            $data['addtime'] = date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time()));
            $result[] = $data;
        }

        return $result;
    }

    /**
     * 删除环境变量
     *
     * @param int $itemId 项目 ID
     * @param int $id 变量 ID
     * @return bool 是否成功
     */
    public static function delete(int $itemId, int $id): bool
    {
        if ($itemId <= 0 || $id <= 0) {
            return false;
        }

        try {
            $deleted = DB::table('item_variable')
                ->where('item_id', $itemId)
                ->where('id', $id)
                ->delete();

            return $deleted > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据变量名删除环境变量
     *
     * @param int $itemId 项目 ID
     * @param int $envId 环境 ID
     * @param string $varName 变量名
     * @return bool 是否成功
     */
    public static function deleteByName(int $itemId, int $envId, string $varName): bool
    {
        if ($itemId <= 0 || empty($varName)) {
            return false;
        }

        try {
            $query = DB::table('item_variable')
                ->where('item_id', $itemId)
                ->where('var_name', $varName);

            if ($envId > 0) {
                $query->where('env_id', $envId);
            }

            $deleted = $query->delete();

            return $deleted > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 更新项目变量的环境 ID
     *
     * @param int $itemId 项目 ID
     * @param int $envId 环境 ID
     * @return bool 是否成功
     */
    public static function updateEnvIdForItem(int $itemId, int $envId): bool
    {
        if ($itemId <= 0 || $envId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('item_variable')
                ->where('item_id', $itemId)
                ->where('env_id', 0) // 只更新 env_id 为 0 的变量
                ->update(['env_id' => $envId]);
            return $affected >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据环境 ID 删除变量
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
            $deleted = DB::table('item_variable')
                ->where('env_id', $envId)
                ->delete();
            return $deleted >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
