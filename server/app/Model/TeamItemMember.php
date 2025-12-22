<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class TeamItemMember
{
    /**
     * 添加团队项目成员
     *
     * @param array $data 成员数据
     * @return int|false 返回插入的ID，失败返回false
     */
    public static function add(array $data)
    {
        try {
            $id = DB::table('team_item_member')->insertGetId($data);
            return $id ?: false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据项目 ID 和团队 ID 获取成员列表
     *
     * @param int $itemId 项目 ID
     * @param int $teamId 团队 ID
     * @return array 成员列表
     */
    public static function getListByItemIdAndTeamId(int $itemId, int $teamId): array
    {
        if ($itemId <= 0 || $teamId <= 0) {
            return [];
        }

        $rows = DB::table('team_item_member')
            ->where('item_id', $itemId)
            ->where('team_id', $teamId)
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $data = (array) $row;
            $data['addtime'] = date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time()));
            // member_group_id 需要返回字符串数字，兼容旧前端
            $data['member_group_id'] = (string) ($data['member_group_id'] ?? '0');
            $data['cat_name'] = '所有目录';

            // 处理 cat_ids（多目录支持）
            if (!empty($data['cat_ids'])) {
                $str = (string) $data['cat_ids'];
                $ids = [];
                if (strpos($str, ',') !== false) {
                    $ids = preg_split('/\s*,\s*/', trim($str));
                } elseif (ctype_digit($str)) {
                    $ids = [(int) $str];
                }
                $data['cat_ids'] = array_values(array_unique(array_map('intval', $ids)));
            } elseif ((int) ($data['cat_id'] ?? 0) > 0) {
                // 兼容：历史只有单目录时，预填为单元素数组
                $data['cat_ids'] = [(int) $data['cat_id']];
            } else {
                $data['cat_ids'] = [];
            }

            // 展示名称：存在多目录时，简单展示为"多个目录"
            if (!empty($data['cat_ids']) && count($data['cat_ids']) > 1) {
                $data['cat_name'] = '多个目录';
            } elseif ((int) ($data['cat_id'] ?? 0) > 0) {
                $cat = DB::table('catalog')
                    ->where('cat_id', $data['cat_id'])
                    ->first();
                if ($cat && !empty($cat->cat_name)) {
                    $data['cat_name'] = $cat->cat_name;
                }
            }

            // 获取用户名称
            $memberUid = (int) ($data['member_uid'] ?? 0);
            if ($memberUid > 0) {
                $user = DB::table('user')
                    ->where('uid', $memberUid)
                    ->first();
                if ($user) {
                    $data['name'] = $user->name ?? '';
                }
            }

            $result[] = $data;
        }

        return $result;
    }

    /**
     * 根据 ID 查找团队项目成员
     *
     * @param int $id 成员 ID
     * @return object|null 成员记录，不存在返回 null
     */
    public static function findById(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        return DB::table('team_item_member')
            ->where('id', $id)
            ->first();
    }

    /**
     * 更新团队项目成员
     *
     * @param int $id 成员 ID
     * @param array $data 更新数据
     * @return bool 是否成功
     */
    public static function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            $affected = DB::table('team_item_member')
                ->where('id', $id)
                ->update($data);
            return $affected >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 删除团队项目成员（根据项目 ID 和团队 ID）
     *
     * @param int $itemId 项目 ID
     * @param int $teamId 团队 ID
     * @return bool 是否成功
     */
    public static function deleteByItemIdAndTeamId(int $itemId, int $teamId): bool
    {
        if ($itemId <= 0 || $teamId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('team_item_member')
                ->where('item_id', $itemId)
                ->where('team_id', $teamId)
                ->delete();
            return $affected >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 删除团队项目成员（根据成员用户 ID 和团队 ID）
     *
     * @param int $memberUid 成员用户 ID
     * @param int $teamId 团队 ID
     * @return bool 是否成功
     */
    public static function deleteByMemberUidAndTeamId(int $memberUid, int $teamId): bool
    {
        if ($memberUid <= 0 || $teamId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('team_item_member')
                ->where('member_uid', $memberUid)
                ->where('team_id', $teamId)
                ->delete();
            return $affected >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
