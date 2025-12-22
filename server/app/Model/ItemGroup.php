<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class ItemGroup
{
    /**
     * 添加项目分组
     *
     * @param int $uid 用户 ID
     * @param string $groupName 分组名称
     * @param string $itemIds 项目 ID 列表（逗号分隔）
     * @return int 新创建的分组 ID，失败返回 0
     */
    public static function add(int $uid, string $groupName, string $itemIds = ''): int
    {
        if ($uid <= 0 || empty($groupName)) {
            return 0;
        }

        try {
            $id = DB::table('item_group')->insertGetId([
                'uid'        => $uid,
                'group_name' => $groupName,
                'item_ids'   => $itemIds,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return (int) $id;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 更新项目分组
     *
     * @param int $id 分组 ID
     * @param int $uid 用户 ID
     * @param string $groupName 分组名称
     * @param string $itemIds 项目 ID 列表（逗号分隔）
     * @return bool 是否成功
     */
    public static function update(int $id, int $uid, string $groupName, string $itemIds = ''): bool
    {
        if ($id <= 0 || $uid <= 0 || empty($groupName)) {
            return false;
        }

        try {
            $affected = DB::table('item_group')
                ->where('id', $id)
                ->where('uid', $uid)
                ->update([
                    'group_name' => $groupName,
                    'item_ids'   => $itemIds,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 保存分组（新建或更新）
     *
     * @param int $id 分组 ID（0 表示新建）
     * @param int $uid 用户 ID
     * @param string $groupName 分组名称
     * @param string $itemIds 项目 ID 列表（逗号分隔）
     * @return int 分组 ID，失败返回 0
     */
    public static function save(int $id, int $uid, string $groupName, string $itemIds = ''): int
    {
        if ($id > 0) {
            $success = self::update($id, $uid, $groupName, $itemIds);
            return $success ? $id : 0;
        } else {
            return self::add($uid, $groupName, $itemIds);
        }
    }

    /**
     * 获取用户的项目分组列表
     *
     * @param int $uid 用户 ID
     * @return array 分组列表
     */
    public static function getList(int $uid): array
    {
        if ($uid <= 0) {
            return [];
        }

        $rows = DB::table('item_group')
            ->where('uid', $uid)
            ->orderBy('s_number', 'asc')
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
     * 删除项目分组
     *
     * @param int $id 分组 ID
     * @param int $uid 用户 ID
     * @return bool 是否成功
     */
    public static function delete(int $id, int $uid): bool
    {
        if ($id <= 0 || $uid <= 0) {
            return false;
        }

        try {
            // 删除分组
            $deleted = DB::table('item_group')
                ->where('id', $id)
                ->where('uid', $uid)
                ->delete();

            if ($deleted > 0) {
                // 删除关联的项目排序数据
                DB::table('item_sort')
                    ->where('item_group_id', $id)
                    ->delete();
            }

            return $deleted > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据 ID 查找分组
     *
     * @param int $id 分组 ID
     * @param int $uid 用户 ID（可选，用于权限检查）
     * @return object|null
     */
    public static function findById(int $id, int $uid = 0): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $query = DB::table('item_group')->where('id', $id);

        if ($uid > 0) {
            $query->where('uid', $uid);
        }

        return $query->first();
    }

    /**
     * 批量更新分组排序
     *
     * @param int $uid 用户 ID
     * @param array $groups 分组数据数组，每个元素包含 id 和 s_number
     * @return bool 是否成功
     */
    public static function saveSort(int $uid, array $groups): bool
    {
        if ($uid <= 0 || empty($groups)) {
            return false;
        }

        try {
            DB::beginTransaction();

            foreach ($groups as $group) {
                $id = (int) ($group['id'] ?? 0);
                $sNumber = (int) ($group['s_number'] ?? 0);

                if ($id > 0) {
                    DB::table('item_group')
                        ->where('id', $id)
                        ->where('uid', $uid)
                        ->update(['s_number' => $sNumber]);
                }
            }

            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            return false;
        }
    }
}
