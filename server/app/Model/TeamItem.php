<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class TeamItem
{
    /**
     * 添加团队项目关联
     *
     * @param array $data 关联数据
     * @return int|false 返回插入的ID，失败返回false
     */
    public static function add(array $data)
    {
        try {
            $id = DB::table('team_item')->insertGetId($data);
            return $id ?: false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据项目 ID 获取团队列表（关联 team 表）
     *
     * @param int $itemId 项目 ID
     * @return array 团队列表
     */
    public static function getListByItemId(int $itemId): array
    {
        if ($itemId <= 0) {
            return [];
        }

        $rows = DB::table('team')
            ->leftJoin('team_item', 'team.id', '=', 'team_item.team_id')
            ->where('team_item.item_id', $itemId)
            ->select('team.*', 'team_item.team_id', 'team_item.id as id')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $data = (array) $row;
            $data['addtime'] = date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time()));
            $result[] = $data;
        }

        return $result;
    }

    /**
     * 根据团队 ID 获取项目列表（关联 item 表）
     *
     * @param int $teamId 团队 ID
     * @return array 项目列表
     */
    public static function getListByTeamId(int $teamId): array
    {
        if ($teamId <= 0) {
            return [];
        }

        $rows = DB::table('item')
            ->leftJoin('team_item', 'item.item_id', '=', 'team_item.item_id')
            ->where('team_item.team_id', $teamId)
            ->where('item.is_del', 0)
            ->select('item.*', 'team_item.team_id', 'team_item.id as id')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $data = (array) $row;
            $data['addtime'] = date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time()));
            $result[] = $data;
        }

        return $result;
    }

    /**
     * 根据 ID 查找团队项目关联
     *
     * @param int $id 关联 ID
     * @return object|null 关联记录，不存在返回 null
     */
    public static function findById(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        return DB::table('team_item')
            ->where('id', $id)
            ->first();
    }

    /**
     * 检查团队项目关联是否存在
     *
     * @param int $teamId 团队 ID
     * @param int $itemId 项目 ID
     * @return bool 是否存在
     */
    public static function exists(int $teamId, int $itemId): bool
    {
        if ($teamId <= 0 || $itemId <= 0) {
            return false;
        }

        $row = DB::table('team_item')
            ->where('team_id', $teamId)
            ->where('item_id', $itemId)
            ->first();

        return $row !== null;
    }

    /**
     * 删除团队项目关联
     *
     * @param int $id 关联 ID
     * @return bool 是否成功
     */
    public static function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $affected = DB::table('team_item')
                ->where('id', $id)
                ->delete();
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据团队 ID 获取所有项目 ID 列表
     *
     * @param int $teamId 团队 ID
     * @return array 项目 ID 数组
     */
    public static function getItemIdsByTeamId(int $teamId): array
    {
        if ($teamId <= 0) {
            return [];
        }

        $rows = DB::table('team_item')
            ->where('team_id', $teamId)
            ->select('item_id')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (int) ($row->item_id ?? 0);
        }

        return $result;
    }
}
