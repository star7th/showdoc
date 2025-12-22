<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class TeamMember
{
    /**
     * 添加团队成员
     *
     * @param array $data 成员数据
     * @return int|false 返回插入的ID，失败返回false
     */
    public static function add(array $data)
    {
        try {
            $id = DB::table('team_member')->insertGetId($data);
            return $id ?: false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据团队 ID 获取成员列表（关联 user 表）
     *
     * @param int $teamId 团队 ID
     * @return array 成员列表
     */
    public static function getListByTeamId(int $teamId): array
    {
        if ($teamId <= 0) {
            return [];
        }

        $rows = DB::table('team_member')
            ->leftJoin('user', 'user.uid', '=', 'team_member.member_uid')
            ->where('team_member.team_id', $teamId)
            ->select('team_member.*', 'user.name as name')
            ->orderBy('team_member.addtime', 'desc')
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
     * 根据 ID 查找团队成员
     *
     * @param int $id 成员 ID
     * @return object|null 成员记录，不存在返回 null
     */
    public static function findById(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        return DB::table('team_member')
            ->where('id', $id)
            ->first();
    }

    /**
     * 检查团队成员是否存在
     *
     * @param int $teamId 团队 ID
     * @param int $memberUid 成员用户 ID
     * @return bool 是否存在
     */
    public static function exists(int $teamId, int $memberUid): bool
    {
        if ($teamId <= 0 || $memberUid <= 0) {
            return false;
        }

        $row = DB::table('team_member')
            ->where('team_id', $teamId)
            ->where('member_uid', $memberUid)
            ->first();

        return $row !== null;
    }

    /**
     * 根据团队 ID 获取所有成员用户 ID 列表
     *
     * @param int $teamId 团队 ID
     * @return array 成员用户 ID 数组
     */
    public static function getMemberUidsByTeamId(int $teamId): array
    {
        if ($teamId <= 0) {
            return [];
        }

        $rows = DB::table('team_member')
            ->where('team_id', $teamId)
            ->select('member_uid', 'member_username', 'team_member_group_id')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    /**
     * 删除团队成员
     *
     * @param int $id 成员 ID
     * @return bool 是否成功
     */
    public static function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $affected = DB::table('team_member')
                ->where('id', $id)
                ->delete();
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据团队 ID 和成员用户 ID 删除成员
     *
     * @param int $teamId 团队 ID
     * @param int $memberUid 成员用户 ID
     * @return bool 是否成功
     */
    public static function deleteByTeamIdAndUid(int $teamId, int $memberUid): bool
    {
        if ($teamId <= 0 || $memberUid <= 0) {
            return false;
        }

        try {
            $affected = DB::table('team_member')
                ->where('team_id', $teamId)
                ->where('member_uid', $memberUid)
                ->delete();
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
