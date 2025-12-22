<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * 项目变更日志模型（开源版：使用单表 item_change_log，不支持分表）
 */
class ItemChangeLog
{
    /**
     * 添加变更日志
     *
     * @param int $uid 用户 ID
     * @param int $itemId 项目 ID
     * @param string $opActionType 操作动作类型（create/update/delete 等）
     * @param string $opObjectType 操作对象类型（page/item 等）
     * @param int $opObjectId 操作对象 ID
     * @param string $opObjectName 操作对象名称
     * @param string $remark 备注
     * @return bool 是否成功
     */
    public static function addLog(
        int $uid,
        int $itemId,
        string $opActionType,
        string $opObjectType,
        int $opObjectId,
        string $opObjectName = '',
        string $remark = ''
    ): bool {
        if ($uid <= 0 || $itemId <= 0) {
            return false;
        }

        try {
            DB::table('item_change_log')->insert([
                'uid'            => $uid,
                'item_id'        => $itemId,
                'op_action_type' => $opActionType,
                'op_object_type' => $opObjectType,
                'op_object_id'   => $opObjectId,
                'op_object_name' => $opObjectName,
                'remark'         => $remark,
                'optime'         => date('Y-m-d H:i:s'),
            ]);

            // 统计有多少条日志记录了
            $count = DB::table('item_change_log')
                ->where('item_id', $itemId)
                ->count();

            // 每个项目只保留最多300个变更记录
            $keepCount = 300;
            if ($count > $keepCount) {
                $rows = DB::table('item_change_log')
                    ->where('item_id', $itemId)
                    ->orderBy('id', 'desc')
                    ->limit($keepCount)
                    ->get()
                    ->all();

                if (!empty($rows) && count($rows) >= $keepCount) {
                    $lastId = (int) ($rows[$keepCount - 1]->id ?? 0);
                    if ($lastId > 0) {
                        DB::table('item_change_log')
                            ->where('item_id', $itemId)
                            ->where('id', '<', $lastId)
                            ->delete();
                    }
                }
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取变更日志列表
     *
     * @param int $itemId 项目 ID
     * @param int $page 页码
     * @param int $count 每页数量
     * @return array 包含 total 和 list 的数组
     */
    public static function getLog(int $itemId, int $page = 1, int $count = 15): array
    {
        if ($itemId <= 0) {
            return ['total' => 0, 'list' => []];
        }

        $total = DB::table('item_change_log')
            ->where('item_id', $itemId)
            ->count();

        $offset = ($page - 1) * $count;
        $rows = DB::table('item_change_log')
            ->where('item_id', $itemId)
            ->orderBy('optime', 'desc')
            ->offset($offset)
            ->limit($count)
            ->get()
            ->all();

        $list = [];
        foreach ($rows as $row) {
            $list[] = self::renderOneLog((array) $row);
        }

        return [
            'total' => (int) $total,
            'list'  => $list,
        ];
    }

    /**
     * 渲染单条日志为人类可读的格式
     *
     * @param array $one 日志数据
     * @return array 渲染后的日志数据
     */
    private static function renderOneLog(array $one): array
    {
        $uid = (int) ($one['uid'] ?? 0);
        $user = User::findById($uid);

        $one['username'] = $user ? ($user->username ?? '') : '';
        $one['name'] = $user ? ($user->name ?? '') : '';
        $oper = $one['username'];
        if (!empty($one['name'])) {
            $oper = $one['username'] . '(' . $one['name'] . ')';
        }
        $one['oper'] = $oper;

        // 操作类型描述
        $actionTypeMap = [
            'create' => '创建',
            'update' => '修改',
            'delete' => '删除',
            'export' => '导出',
            'binding' => '绑定',
            'unbound' => '解绑',
            'drag' => '拖曳修改',
        ];
        $one['op_action_type_desc'] = $actionTypeMap[$one['op_action_type'] ?? ''] ?? '未定义';

        // 对象类型描述
        $objectTypeMap = [
            'page'   => '页面(或接口)',
            'catalog' => '目录',
            'item'   => '项目',
            'team'   => '团队',
            'member' => '成员',
            'tree'   => '目录树',
        ];
        $one['op_object_type_desc'] = $objectTypeMap[$one['op_object_type'] ?? ''] ?? '未定义';

        return $one;
    }
}

