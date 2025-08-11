<?php

namespace Api\Model;

use Api\Model\BaseModel;

class MemberModel extends BaseModel
{
    protected $autoCheckFields = false;  //一定要关闭字段缓存，不然会报找不到表的错误

    // 获取用户在某项目下拥有权限的目录id集合（仅根目录下一层）。优先个人成员，其次团队成员
    public function getCatIds($item_id, $uid)
    {
        $item_id = intval($item_id);
        $uid = intval($uid);
        $ids = array();

        // 优先个人成员
        $row1 = D("ItemMember")->where(" item_id = '%d' and uid = '%d' ", array($item_id, $uid))->field('cat_id,cat_ids')->find();
        if ($row1 && !empty($row1['cat_ids'])) {
            $str = (string)$row1['cat_ids'];
            if (strpos($str, ',') !== false) {
                $ids = preg_split('/\s*,\s*/', trim($str));
            } else if (ctype_digit($str)) {
                $ids = array(intval($str));
            }
        }
        if (empty($ids) && $row1 && intval($row1['cat_id']) > 0) {
            $ids[] = intval($row1['cat_id']);
        }

        // 其次团队成员（当个人没有指定目录时）
        if (empty($ids)) {
            $row2 = D("TeamItemMember")->where(" item_id = '%d' and member_uid = '%d' ", array($item_id, $uid))->field('cat_id,cat_ids')->find();
            if ($row2 && !empty($row2['cat_ids'])) {
                $str = (string)$row2['cat_ids'];
                if (strpos($str, ',') !== false) {
                    $ids = preg_split('/\s*,\s*/', trim($str));
                } else if (ctype_digit($str)) {
                    $ids = array(intval($str));
                }
            }
            if (empty($ids) && $row2 && intval($row2['cat_id']) > 0) {
                $ids[] = intval($row2['cat_id']);
            }
        }

        // 去重并返回
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        return $ids;
    }

    // 兼容旧接口：返回单个目录id（取第一个），无则返回0
    public function getCatId($item_id, $uid)
    {
        $ids = $this->getCatIds($item_id, $uid);
        if (!empty($ids)) {
            return intval($ids[0]);
        }
        return 0;
    }
}
