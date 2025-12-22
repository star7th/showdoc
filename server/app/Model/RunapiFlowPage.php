<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class RunapiFlowPage
{
    /**
     * 根据流程 ID 获取页面列表
     *
     * @param int $flowId 流程 ID
     * @return array 页面列表
     */
    public static function getListByFlowId(int $flowId): array
    {
        if ($flowId <= 0) {
            return [];
        }

        $rows = DB::table('runapi_flow_page')
            ->where('flow_id', $flowId)
            ->orderBy('s_number', 'asc')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    /**
     * 获取流程中最后一个页面的顺序号
     *
     * @param int $flowId 流程 ID
     * @return int 顺序号，如果没有则返回 0
     */
    public static function getLastSNumber(int $flowId): int
    {
        if ($flowId <= 0) {
            return 0;
        }

        $row = DB::table('runapi_flow_page')
            ->where('flow_id', $flowId)
            ->orderBy('s_number', 'desc')
            ->first();

        return $row ? (int) ($row->s_number ?? 0) : 0;
    }

    /**
     * 根据 ID 查找流程页面
     *
     * @param int $id 流程页面 ID
     * @return object|null 流程页面对象，不存在返回 null
     */
    public static function findById(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        return DB::table('runapi_flow_page')
            ->where('id', $id)
            ->first();
    }

    /**
     * 添加流程页面
     *
     * @param array $data 流程页面数据
     * @return int|false 返回插入的ID，失败返回false
     */
    public static function add(array $data)
    {
        try {
            $id = DB::table('runapi_flow_page')->insertGetId($data);
            return $id ?: false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 更新流程页面
     *
     * @param int $flowId 流程 ID
     * @param int $id 流程页面 ID
     * @param array $data 更新数据
     * @return bool 是否成功
     */
    public static function update(int $flowId, int $id, array $data): bool
    {
        if ($flowId <= 0 || $id <= 0) {
            return false;
        }

        try {
            $affected = DB::table('runapi_flow_page')
                ->where('flow_id', $flowId)
                ->where('id', $id)
                ->update($data);
            return $affected >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 批量更新流程页面的顺序号
     *
     * @param int $flowId 流程 ID
     * @param array $orders 顺序数组 [['id' => 1, 's_number' => 1], ...]
     * @return bool 是否成功
     */
    public static function updateSort(int $flowId, array $orders): bool
    {
        if ($flowId <= 0 || empty($orders)) {
            return false;
        }

        try {
            foreach ($orders as $order) {
                $id = (int) ($order['id'] ?? 0);
                $sNumber = (int) ($order['s_number'] ?? 0);
                if ($id > 0) {
                    DB::table('runapi_flow_page')
                        ->where('flow_id', $flowId)
                        ->where('id', $id)
                        ->update(['s_number' => $sNumber]);
                }
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 批量设置流程页面的启用状态
     *
     * @param int $flowId 流程 ID
     * @param array $ids 启用的页面 ID 数组
     * @return bool 是否成功
     */
    public static function setEnabled(int $flowId, array $ids): bool
    {
        if ($flowId <= 0) {
            return false;
        }

        try {
            // 先全部禁用
            DB::table('runapi_flow_page')
                ->where('flow_id', $flowId)
                ->update(['enabled' => 0]);

            // 然后启用指定的页面
            if (!empty($ids)) {
                foreach ($ids as $id) {
                    $id = (int) $id;
                    if ($id > 0) {
                        DB::table('runapi_flow_page')
                            ->where('flow_id', $flowId)
                            ->where('id', $id)
                            ->update(['enabled' => 1]);
                    }
                }
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 删除流程页面
     *
     * @param int $id 流程页面 ID
     * @return bool 是否成功
     */
    public static function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $affected = DB::table('runapi_flow_page')
                ->where('id', $id)
                ->delete();
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
