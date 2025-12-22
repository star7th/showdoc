<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class ItemMember
{
    /**
     * 添加项目成员
     *
     * @param array $data 成员数据
     * @return int 新创建的成员 ID，失败返回 0
     */
    public static function add(array $data): int
    {
        if (empty($data['uid']) || empty($data['item_id'])) {
            return 0;
        }

        try {
            $id = DB::table('item_member')->insertGetId($data);
            return (int) $id;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 根据条件查找成员
     *
     * @param int $uid 用户 ID
     * @param int $itemId 项目 ID
     * @return object|null
     */
    public static function findByUidAndItemId(int $uid, int $itemId): ?object
    {
        if ($uid <= 0 || $itemId <= 0) {
            return null;
        }

        return DB::table('item_member')
            ->where('uid', $uid)
            ->where('item_id', $itemId)
            ->first();
    }

    /**
     * 获取项目成员列表
     *
     * @param int $itemId 项目 ID
     * @return array 成员列表
     */
    public static function getList(int $itemId): array
    {
        if ($itemId <= 0) {
            return [];
        }

        $rows = DB::table('item_member')
            ->leftJoin('user', 'user.uid', '=', 'item_member.uid')
            ->select([
                'item_member.*',
                'user.name as name',
            ])
            ->where('item_member.item_id', $itemId)
            ->orderBy('item_member.addtime', 'asc')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $data = (array) $row;
            $data['addtime'] = date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time()));
            // member_group_id 需要返回字符串数字，兼容旧前端
            $data['member_group_id'] = (string) ($data['member_group_id'] ?? '0');

            // 处理目录名称
            $data['cat_name'] = '所有目录';
            if (!empty($data['cat_ids'])) {
                $data['cat_name'] = '多个目录';
            } elseif (!empty($data['cat_id']) && (int) $data['cat_id'] > 0) {
                $cat = Catalog::findById((int) $data['cat_id']);
                if ($cat) {
                    $data['cat_name'] = $cat->cat_name ?? '所有目录';
                }
            }

            $memberGroupId = (int) ($data['member_group_id'] ?? 0);
            $groupName = $memberGroupId === 1 ? '编辑' : '只读';
            $data['member_group'] = "{$groupName}/目录：{$data['cat_name']}";

            $result[] = $data;
        }

        return $result;
    }

    /**
     * 删除项目成员
     *
     * @param int $itemId 项目 ID
     * @param int $itemMemberId 成员 ID
     * @return array|null 被删除的成员信息
     */
    public static function delete(int $itemId, int $itemMemberId): ?array
    {
        if ($itemId <= 0 || $itemMemberId <= 0) {
            return null;
        }

        // 先获取成员信息
        $member = DB::table('item_member')
            ->where('item_id', $itemId)
            ->where('item_member_id', $itemMemberId)
            ->first();

        if (!$member) {
            return null;
        }

        // 删除成员
        $deleted = DB::table('item_member')
            ->where('item_id', $itemId)
            ->where('item_member_id', $itemMemberId)
            ->delete();

        return $deleted > 0 ? (array) $member : null;
    }

    /**
     * 获取项目的所有成员（包括单独成员和团队成员）
     *
     * @param int $itemId 项目 ID
     * @return array 成员列表
     */
    public static function getAllList(int $itemId): array
    {
        if ($itemId <= 0) {
            return [];
        }

        // 获取项目的单独成员
        $members = DB::table('item_member')
            ->leftJoin('user', 'user.uid', '=', 'item_member.uid')
            ->select([
                'item_member.uid',
                'item_member.username',
                'item_member.member_group_id',
                'item_member.item_id',
                'user.name as name',
            ])
            ->where('item_member.item_id', $itemId)
            ->orderBy('item_member.addtime', 'asc')
            ->get()
            ->all();

        // 获取项目绑定的团队成员
        $teamMembers = DB::table('team_item_member')
            ->leftJoin('user', 'user.uid', '=', 'team_item_member.member_uid')
            ->select([
                'team_item_member.member_uid as uid',
                'team_item_member.member_username as username',
                'team_item_member.member_group_id',
                'team_item_member.item_id',
                'user.name as name',
            ])
            ->where('team_item_member.item_id', $itemId)
            ->orderBy('team_item_member.addtime', 'asc')
            ->get()
            ->all();

        $return = [];
        $uidArray = []; // 用于去重

        // 添加项目创建者
        $item = Item::findById($itemId);
        if ($item) {
            $return[] = [
                'item_id'        => $itemId,
                'uid'            => (int) $item->uid,
                'username'       => $item->username ?? '',
                'username_name'  => $item->username ?? '',
                // member_group_id 需要返回字符串数字，兼容旧前端
                'member_group_id' => '1',
            ];
            $uidArray[] = (int) $item->uid;
        }

        // 添加单独成员
        foreach ($members as $member) {
            $uid = (int) $member->uid;
            if (!in_array($uid, $uidArray, true)) {
                $return[] = [
                    'item_id'         => $itemId,
                    'uid'             => $uid,
                    'username'        => $member->username ?? '',
                    'username_name'   => $member->name ?? $member->username ?? '',
                    // member_group_id 需要返回字符串数字，兼容旧前端
                    'member_group_id' => (string) ($member->member_group_id ?? '0'),
                ];
                $uidArray[] = $uid;
            }
        }

        // 添加团队成员
        foreach ($teamMembers as $member) {
            $uid = (int) $member->uid;
            if (!in_array($uid, $uidArray, true)) {
                $return[] = [
                    'item_id'         => $itemId,
                    'uid'             => $uid,
                    'username'        => $member->username ?? '',
                    'username_name'   => $member->name ?? $member->username ?? '',
                    // member_group_id 需要返回字符串数字，兼容旧前端
                    'member_group_id' => (string) ($member->member_group_id ?? '0'),
                ];
                $uidArray[] = $uid;
            }
        }

        return $return;
    }
}

