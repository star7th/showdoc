<?php

namespace Api\Model;

use Api\Model\BaseModel;

class MemberModel extends BaseModel
{
    protected $autoCheckFields = false;  //一定要关闭字段缓存，不然会报找不到表的错误

    // 如果用户被分配了 目录权限 ，则获取他在该项目下拥有权限的目录id 
    public function getCatId($item_id, $uid)
    {
        $cat_id1 = D("ItemMember")->where(" item_id = '%d' and uid = '%d' ", array($item_id, $uid))->getField('cat_id');
        $cat_id2 = D("TeamItemMember")->where(" item_id = '%d' and member_uid = '%d' ", array($item_id, $uid))->getField('cat_id');
        // 尝试给 $cat_id 赋值 $cat_id1，如果 $cat_id1 未空，则尝试 $cat_id2，如果 $cat_id2 也未空，则最终给 $cat_id 赋值 0。
        if (!empty($cat_id1)) {
            $cat_id = $cat_id1;
        } elseif (!empty($cat_id2)) {
            $cat_id = $cat_id2;
        } else {
            $cat_id = 0;
        }
        return $cat_id;
    }
}
