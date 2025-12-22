<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class Team
{
    /**
     * 添加团队
     *
     * @param int $uid 用户 ID
     * @param string $username 用户名
     * @param string $teamName 团队名称
     * @return int 新创建的团队 ID，失败返回 0
     */
    public static function add(int $uid, string $username, string $teamName): int
    {
        if ($uid <= 0 || empty($teamName)) {
            return 0;
        }

        try {
            $id = DB::table('team')->insertGetId([
                'username' => $username,
                'uid'      => $uid,
                'team_name' => $teamName,
                'addtime'  => time(),
            ]);
            return (int) $id;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 更新团队
     *
     * @param int $id 团队 ID
     * @param string $teamName 团队名称
     * @return bool 是否成功
     */
    public static function update(int $id, string $teamName): bool
    {
        if ($id <= 0 || empty($teamName)) {
            return false;
        }

        try {
            $affected = DB::table('team')
                ->where('id', $id)
                ->update(['team_name' => $teamName]);
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 保存团队（新建或更新）
     *
     * @param int $id 团队 ID（0 表示新建）
     * @param int $uid 用户 ID
     * @param string $username 用户名
     * @param string $teamName 团队名称
     * @return int 团队 ID，失败返回 0
     */
    public static function save(int $id, int $uid, string $username, string $teamName): int
    {
        if ($id > 0) {
            $success = self::update($id, $teamName);
            return $success ? $id : 0;
        } else {
            return self::add($uid, $username, $teamName);
        }
    }

    /**
     * 获取用户的团队列表（包括创建的和参与的）
     *
     * @param int $uid 用户 ID
     * @return array 团队列表
     */
    public static function getList(int $uid): array
    {
        if ($uid <= 0) {
            return [];
        }

        $rows = DB::table('team')
            ->where(function ($q) use ($uid) {
                $q->where('uid', $uid)
                    ->orWhereIn('id', function ($subQ) use ($uid) {
                        $subQ->select('team_id')
                            ->from('team_member')
                            ->where('member_uid', $uid);
                    });
            })
            ->orderBy('addtime', 'desc')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $data = (array) $row;

            // 获取团队成员数
            $data['memberCount'] = DB::table('team_member')
                ->where('team_id', $data['id'])
                ->count();

            // 获取团队涉及项目数
            $data['itemCount'] = DB::table('team_item')
                ->where('team_id', $data['id'])
                ->count();

            $data['addtime'] = date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time()));

            $result[] = $data;
        }

        return $result;
    }

    /**
     * 删除团队
     *
     * @param int $id 团队 ID
     * @param int $uid 用户 ID（必须是团队创建者）
     * @return bool 是否成功
     */
    public static function delete(int $id, int $uid): bool
    {
        if ($id <= 0 || $uid <= 0) {
            return false;
        }

        try {
            // 只有团队创建者才能删除
            $deleted = DB::table('team')
                ->where('id', $id)
                ->where('uid', $uid)
                ->delete();

            if ($deleted > 0) {
                // 删除关联数据
                DB::table('team_item')
                    ->where('team_id', $id)
                    ->delete();

                DB::table('team_item_member')
                    ->where('team_id', $id)
                    ->delete();

                DB::table('team_member')
                    ->where('team_id', $id)
                    ->delete();
            }

            return $deleted > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据 ID 查找团队
     *
     * @param int $id 团队 ID
     * @param int $uid 用户 ID（可选，用于权限检查）
     * @return object|null
     */
    public static function findById(int $id, int $uid = 0): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $query = DB::table('team')->where('id', $id);

        if ($uid > 0) {
            $query->where('uid', $uid);
        }

        return $query->first();
    }

    /**
     * 转让团队
     *
     * @param int $teamId 团队 ID
     * @param int $fromUid 原创建者用户 ID
     * @param int $toUid 新创建者用户 ID
     * @param string $toUsername 新创建者用户名
     * @return bool 是否成功
     */
    public static function attorn(int $teamId, int $fromUid, int $toUid, string $toUsername): bool
    {
        if ($teamId <= 0 || $fromUid <= 0 || $toUid <= 0) {
            return false;
        }

        try {
            DB::beginTransaction();

            // 更新团队创建者
            DB::table('team')
                ->where('id', $teamId)
                ->where('uid', $fromUid)
                ->update([
                    'username' => $toUsername,
                    'uid'      => $toUid,
                ]);

            // 获取该团队下的所有项目，准备转让
            $items = DB::table('team_item')
                ->where('team_id', $teamId)
                ->get()
                ->all();

            foreach ($items as $item) {
                DB::table('item')
                    ->where('item_id', $item->item_id)
                    ->update([
                        'username' => $toUsername,
                        'uid'      => $toUid,
                    ]);
            }

            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * 用户退出团队
     *
     * @param int $teamId 团队 ID
     * @param int $uid 用户 ID
     * @return bool 是否成功
     */
    public static function exitTeam(int $teamId, int $uid): bool
    {
        if ($teamId <= 0 || $uid <= 0) {
            return false;
        }

        try {
            DB::beginTransaction();

            // 删除团队项目成员关系
            DB::table('team_item_member')
                ->where('team_id', $teamId)
                ->where('member_uid', $uid)
                ->delete();

            // 删除团队成员关系
            DB::table('team_member')
                ->where('team_id', $teamId)
                ->where('member_uid', $uid)
                ->delete();

            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            return false;
        }
    }
}
