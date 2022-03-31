<?php

namespace Api\Model;

use Api\Model\BaseModel;

class ItemChangeLogModel extends BaseModel
{


    public function addLog($uid, $item_id, $op_action_type, $op_object_type, $op_object_id, $op_object_name = '', $remark = '')
    {
        $data = array(
            "uid" => $uid,
            "item_id" => $item_id,
            "op_action_type" => $op_action_type,
            "op_object_type" => $op_object_type,
            "op_object_id" => $op_object_id,
            "op_object_name" => $op_object_name,
            "optime" => date("Y-m-d H:i:s"),
        );
        $this->add($data);

        //统计有多少条日志记录了
        $count = $this->where(" item_id = '$item_id' ")->count();
        //每个项目只保留最多$keepNum个变更记录
        $keepCount = 300;
        if ($count > $keepCount) {
            $ret = $this->where(" item_id = '$item_id' ")->limit($keepCount)->order("id desc")->select();
            $this->where(" item_id = '$item_id' and id < " . $ret[$keepCount - 1]['id'])->delete();
        }
    }

    public function getLog($item_id, $page = 1, $count = 15)
    {
        $item_id = intval($item_id);
        $list = $this->where(" item_id = '{$item_id}' ")->order(" optime desc ")->page(" $page , $count ")->select();
        $total = $this->where(" item_id = '{$item_id}' ")->count();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key] = $this->renderOneLog($value);
            }
        }

        $return = array(
            "total" => (int)$total,
            "list" => (array)$list,
        );

        return $return;
    }

    // 把变更日志的一行渲染成人类可读的提示
    public function renderOneLog($one)
    {
        $op_action_type = $one['op_action_type'];
        $uid = intval($one['uid']);
        $one['op_object_id'] = intval($one['op_object_id']);
        $user_array = D("User")->where(" uid = {$uid} ")->Field('username,name')->find();
        $one['username'] = $user_array['username'];
        $one['name'] = $user_array['name'];
        $oper = $user_array['username'];
        if ($user_array['name']) {
            $oper = $user_array['username'] . '(' . $user_array['name'] . ')';
        }
        $one['oper'] = $oper;

        switch ($op_action_type) {
            case 'create':
                $one['op_action_type_desc'] = '创建';
                break;
            case 'update':
                $one['op_action_type_desc'] = '修改';
                break;
            case 'delete':
                $one['op_action_type_desc'] = '删除';
                break;
            case 'binding':
                $one['op_action_type_desc'] = '绑定';
                break;
            case 'unbound':
                $one['op_action_type_desc'] = '解绑';
                break;
            default:
                $one['op_action_type_desc'] = '未定义';
                break;
        }

        switch ($one['op_object_type']) {
            case 'page':
                $one['op_object_type_desc'] = '页面(或接口)';
                break;
            case 'catalog':
                $one['op_object_type_desc'] = '目录';
                break;
            case 'team':
                $one['op_object_type_desc'] = '团队';
                break;
            case 'member':
                $one['op_object_type_desc'] = '成员';
                break;
            default:
                $one['op_object_type_desc'] = '未定义';
                break;
        }

        return $one;
    }
}
