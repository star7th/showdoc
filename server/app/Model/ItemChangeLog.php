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
     * @param array $actionTypes 操作动作类型白名单（可选，如 ['create','update']）
     * @param array $objectTypes 操作对象类型白名单（可选，如 ['page','catalog']）
     * @return array 包含 total 和 list 的数组
     */
    public static function getLog(int $itemId, int $page = 1, int $count = 15, array $actionTypes = [], array $objectTypes = []): array
    {
        if ($itemId <= 0) {
            return ['total' => 0, 'list' => []];
        }

        $totalQuery = DB::table('item_change_log')
            ->where('item_id', $itemId);
        $rowsQuery = DB::table('item_change_log')
            ->where('item_id', $itemId);

        // 可选类型过滤（数组白名单，不传时行为不变，兼容现有前端调用）
        if (!empty($actionTypes)) {
            $totalQuery->whereIn('op_action_type', $actionTypes);
            $rowsQuery->whereIn('op_action_type', $actionTypes);
        }
        if (!empty($objectTypes)) {
            $totalQuery->whereIn('op_object_type', $objectTypes);
            $rowsQuery->whereIn('op_object_type', $objectTypes);
        }

        $total = $totalQuery->count();

        $offset = ($page - 1) * $count;
        $rows = $rowsQuery
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
     * 成员可见的变更类型白名单（getRecentChanges 用）
     *
     * key 为 op_object_type，value 为允许的 op_action_type 列表。
     * 出于隐私考虑，成员/团队绑定（binding/unbound）、导出（export × item）、
     * 系统级项目操作（update × item）不在此白名单内，不对普通成员开放。
     */
    public const MEMBER_VISIBLE_COMBOS = [
        'page'    => ['create', 'update', 'delete'],
        'catalog' => ['create', 'update'],
        'tree'    => ['drag'],
    ];

    /**
     * 查询项目最近的文档变更（面向普通成员/只读成员，MCP get_recent_changes 使用）
     *
     * 仅返回 MEMBER_VISIBLE_COMBOS 白名单内的操作类型组合；
     * 数据窗口受每项目最多保留 300 条的限制（见 addLog）。
     *
     * @param int $itemId 项目 ID
     * @param array $options 可选参数：
     *   - since: int 起始时间（Unix 时间戳，毫秒会自动转秒；只返回 optime >= since 的记录）
     *   - limit: int 返回条数（默认 20，最大 100）
     *   - catalog_id: int 目录 ID（只返回该目录及其子目录范围内的变更）
     * @return array ['total' => int, 'list' => array]，total 为过滤后的总条数（窗口内）
     */
    public static function getRecentChanges(int $itemId, array $options = []): array
    {
        if ($itemId <= 0) {
            return ['total' => 0, 'list' => []];
        }

        $query = DB::table('item_change_log')
            ->where('item_id', $itemId)
            ->where(function ($q) {
                foreach (self::MEMBER_VISIBLE_COMBOS as $objectType => $actionTypes) {
                    $q->orWhere(function ($q2) use ($objectType, $actionTypes) {
                        $q2->where('op_object_type', $objectType)
                            ->whereIn('op_action_type', $actionTypes);
                    });
                }
            });

        // since 过滤（容错：毫秒时间戳自动降级为秒）
        $since = (int) ($options['since'] ?? 0);
        if ($since > 0) {
            if ($since > 20000000000) {
                $since = intdiv($since, 1000);
            }
            $query->where('optime', '>=', date('Y-m-d H:i:s', $since));
        }

        // 窗口内全量取出（上限 300 条）再内存过滤，保证 catalog 过滤后 limit 语义正确
        $rows = $query
            ->orderBy('optime', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->all();

        // catalog_id 过滤：仅保留该目录（含子目录）范围内的页面/目录变更
        $catalogId = (int) ($options['catalog_id'] ?? 0);
        if ($catalogId > 0) {
            $rows = self::filterRowsByCatalog($itemId, $catalogId, $rows);
        }

        $total = count($rows);

        $limit = min(100, max(1, (int) ($options['limit'] ?? 20)));
        $rows = array_slice($rows, 0, $limit);

        $list = [];
        $userCache = [];
        foreach ($rows as $row) {
            $one = (array) $row;
            $uid = (int) ($one['uid'] ?? 0);
            if (!isset($userCache[$uid])) {
                $user = User::findById($uid);
                $userCache[$uid] = $user ? (string) ($user->username ?? '') : '';
            }
            $list[] = [
                'op_action_type' => (string) ($one['op_action_type'] ?? ''),
                'op_object_type' => (string) ($one['op_object_type'] ?? ''),
                'op_object_id'   => (int) ($one['op_object_id'] ?? 0),
                'op_object_name' => (string) ($one['op_object_name'] ?? ''),
                'optime'         => (string) ($one['optime'] ?? ''),
                'uid'            => $uid,
                'username'       => $userCache[$uid],
            ];
        }

        return [
            'total' => $total,
            'list'  => $list,
        ];
    }

    /**
     * 按目录范围过滤变更日志行（含子目录）
     *
     * - page 类日志：按页面当前所属 cat_id 归属（软删除行仍保留 cat_id）
     * - catalog 类日志：按 op_object_id（cat_id）归属
     * - tree 拖曳为项目级操作，无法归属单个目录，过滤时不返回
     *
     * @param int $itemId 项目 ID
     * @param int $catalogId 目录 ID
     * @param array $rows 日志行
     * @return array 过滤后的日志行
     */
    private static function filterRowsByCatalog(int $itemId, int $catalogId, array $rows): array
    {
        $expandedCatIds = \App\Model\Catalog::expandCatIdsWithChildren($itemId, [$catalogId]);
        if (empty($expandedCatIds)) {
            return [];
        }
        $expandedCatIds = array_map('intval', $expandedCatIds);

        // 收集页面类日志涉及的 page_id，查表取当前所属目录
        $pageIds = [];
        foreach ($rows as $row) {
            $one = (array) $row;
            if (($one['op_object_type'] ?? '') === 'page') {
                $pageIds[] = (int) ($one['op_object_id'] ?? 0);
            }
        }

        $pageCatMap = [];
        if (!empty($pageIds)) {
            $pageTable = \App\Model\Page::tableForItem($itemId);
            $pageRows = DB::table($pageTable)
                ->whereIn('page_id', $pageIds)
                ->select('page_id', 'cat_id')
                ->get()
                ->all();
            foreach ($pageRows as $pageRow) {
                $pageCatMap[(int) $pageRow->page_id] = (int) $pageRow->cat_id;
            }
        }

        $filtered = [];
        foreach ($rows as $row) {
            $one = (array) $row;
            $objectType = (string) ($one['op_object_type'] ?? '');
            $objectId = (int) ($one['op_object_id'] ?? 0);

            if ($objectType === 'page') {
                $catId = $pageCatMap[$objectId] ?? -1;
                if (in_array($catId, $expandedCatIds, true)) {
                    $filtered[] = $row;
                }
            } elseif ($objectType === 'catalog') {
                if (in_array($objectId, $expandedCatIds, true)) {
                    $filtered[] = $row;
                }
            }
        }

        return $filtered;
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

