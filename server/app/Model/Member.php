<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class Member
{
    /**
     * 获取用户在项目下拥有权限的目录 ID 集合
     *
     * @param int $itemId 项目 ID
     * @param int $uid 用户 ID
     * @return array 目录 ID 数组
     */
    public static function getCatIds(int $itemId, int $uid): array
    {
        if ($itemId <= 0 || $uid <= 0) {
            return [];
        }

        // 兼容旧版逻辑：
        // - 个人成员目录权限：item_member.cat_id / item_member.cat_ids
        // - 团队成员目录权限：team_item_member.cat_id / team_item_member.cat_ids
        // 参考：server/Application/Api/Model/MemberModel.class.php::getCatIds

        $ids = [];

        // 1. 优先个人成员
        $row1 = DB::table('item_member')
            ->where('item_id', $itemId)
            ->where('uid', $uid)
            ->select(['cat_id', 'cat_ids'])
            ->first();

        if ($row1) {
            $row1Arr = (array) $row1;
            $str = (string) ($row1Arr['cat_ids'] ?? '');
            if ($str !== '') {
                $tmp = [];
                if (strpos($str, ',') !== false) {
                    $tmp = preg_split('/\s*,\s*/', trim($str));
                } elseif (ctype_digit($str)) {
                    $tmp = [intval($str)];
                }
                foreach ($tmp as $id) {
                    $id = intval($id);
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
            }
            if (empty($ids) && !empty($row1Arr['cat_id']) && intval($row1Arr['cat_id']) > 0) {
                $ids[] = intval($row1Arr['cat_id']);
            }
        }

        // 2. 如果个人没有目录限制，再看团队成员
        if (empty($ids)) {
            $row2 = DB::table('team_item_member')
                ->where('item_id', $itemId)
                ->where('member_uid', $uid)
                ->select(['cat_id', 'cat_ids'])
                ->first();

            if ($row2) {
                $row2Arr = (array) $row2;
                $str = (string) ($row2Arr['cat_ids'] ?? '');
                if ($str !== '') {
                    $tmp = [];
                    if (strpos($str, ',') !== false) {
                        $tmp = preg_split('/\s*,\s*/', trim($str));
                    } elseif (ctype_digit($str)) {
                        $tmp = [intval($str)];
                    }
                    foreach ($tmp as $id) {
                        $id = intval($id);
                        if ($id > 0) {
                            $ids[] = $id;
                        }
                    }
                }
                if (empty($ids) && !empty($row2Arr['cat_id']) && intval($row2Arr['cat_id']) > 0) {
                    $ids[] = intval($row2Arr['cat_id']);
        }
            }
        }

        // 去重并返回
        $ids = array_values(array_unique(array_map('intval', $ids)));
        return $ids;
    }
}
