<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 项目相关 Api（新架构，逐步替换旧的 Api\Controller\ItemController）。
 *
 * 目前优先迁移与「项目列表」相关的接口，例如：
 * - myList：我的项目列表（本人创建 + 成员项目 + 团队成员项目）
 */
class ItemController extends BaseController
{
    /**
     * 我的项目列表（兼容旧接口 Api/Item/myList）。
     *
     * 特性：
     * - 聚合本人创建项目、作为成员参与的项目、团队成员项目；
     * - 支持原创筛选 original=1；
     * - 支持项目分组 item_group_id（含 -1 仅星标分组）；
     * - 返回 creator / manage / is_private / is_star / top / s_number 等字段；
     * - 仅基于 user_token 鉴权，不再依赖旧 session。
     */
    public function myList(Request $request, Response $response): Response
    {
        // 等价于旧世界的 checkLogin()
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid          = (int) ($loginUser['uid'] ?? 0);
        $original     = $this->getParam($request, 'original', 0);
        $itemGroupId  = $this->getParam($request, 'item_group_id', 0);

        // 1. 收集"成员项目"和"管理项目"的 item_id 集合
        $memberItemIds       = ['-1']; // 只读 + 编辑成员（使用字符串，与数据库返回类型一致）
        $manageMemberItemIds = ['-1']; // 拥有管理权限的成员（使用字符串，与数据库返回类型一致）

        // ItemMember：成员项目
        $rows = DB::table('item_member')
            ->where('uid', $uid)
            ->where('member_group_id', '!=', 2)
            ->get();
        foreach ($rows as $row) {
            $memberItemIds[] = (string) $row->item_id; // 直接使用数据库返回的值，转为字符串保持一致
        }

        // TeamItemMember：团队成员项目（非管理组）
        $rows = DB::table('team_item_member')
            ->where('member_uid', $uid)
            ->where('member_group_id', '!=', 2)
            ->get();
        foreach ($rows as $row) {
            $memberItemIds[] = (string) $row->item_id;
        }

        // ItemMember：拥有项目管理权限的成员（group_id = 2）
        $rows = DB::table('item_member')
            ->where('uid', $uid)
            ->where('member_group_id', 2)
            ->get();
        foreach ($rows as $row) {
            $manageMemberItemIds[] = (string) $row->item_id;
        }

        // TeamItemMember：团队成员中的管理组
        $rows = DB::table('team_item_member')
            ->where('member_uid', $uid)
            ->where('member_group_id', 2)
            ->get();
        foreach ($rows as $row) {
            $manageMemberItemIds[] = (string) $row->item_id;
        }

        // 2. 星标项目列表（多个地方会用到）
        // 注意：旧后端使用 intval，但为了保持类型一致，这里也使用字符串
        $starItemIds = [];
        $rows        = DB::table('item_star')
            ->where('uid', $uid)
            ->get();
        foreach ($rows as $row) {
            $starItemIds[] = (string) $row->item_id;
        }

        // 3. 构建基础查询：本人 + 成员 + 管理项目
        $query = DB::table('item')
            ->select([
                'item_id',
                'uid',
                'item_name',
                'item_domain',
                'item_type',
                'last_update_time',
                'item_description',
                'is_del',
                'password',
            ])
            ->where(function ($q) use ($uid, $memberItemIds, $manageMemberItemIds) {
                $q->where('uid', $uid)
                    ->orWhereIn('item_id', $memberItemIds)
                    ->orWhereIn('item_id', $manageMemberItemIds);
            });

        // 4. 项目分组筛选（>0：按分组；===-1：仅星标项目）
        if ($itemGroupId > 0) {
            $groupRow = DB::table('item_group')
                ->where('id', $itemGroupId)
                ->where('uid', $uid)
                ->first();

            if ($groupRow && !empty($groupRow->item_ids)) {
                $ids = array_filter(array_map('intval', explode(',', (string) $groupRow->item_ids)));
                if ($ids) {
                    $query->whereIn('item_id', $ids);
                } else {
                    // 分组为空时保持与旧实现一致：查询一个永远不存在的 id
                    $query->where('item_id', 0);
                }
            } else {
                $query->where('item_id', 0);
            }
        }

        // item_group_id === -1：仅返回星标项目
        if ($itemGroupId === -1) {
            if ($starItemIds) {
                $query->whereIn('item_id', $starItemIds);
            } else {
                $query->where('item_id', 0);
            }
        }

        // 5. 拉取项目列表（按 item_id 升序，与旧实现保持一致）
        $rows = $query
            ->orderBy('item_id', 'asc')
            ->get()
            ->all();

        $items = [];
        foreach ($rows as $row) {
            // 使用 json_decode(json_encode()) 将对象转换为数组，保持与 ThinkPHP 返回格式完全一致
            // 这样可以确保所有字段（特别是 item_name）都被正确保留，同时保持原始数据类型
            // json_encode 会将对象转换为 JSON，json_decode(..., true) 会将其转换为关联数组
            $item = json_decode(json_encode($row), true);

            // 确保 item_name 始终是字符串类型（不能为 null），否则 Element UI 的 el-select 会回退显示 value
            if (!isset($item['item_name']) || $item['item_name'] === null) {
                $item['item_name'] = '';
            }

            // 默认排序号
            $item['s_number'] = 0;

            // creator / manage 判断（使用类型安全的比较，兼容数据库返回的字符串或整数）
            if ($item['uid'] == $uid) {
                $item['creator'] = 1;
                $item['manage']  = 1;
            } elseif (in_array((string)$item['item_id'], $manageMemberItemIds)) {
                // 使用宽松比较避免MySQL返回字符串类型时匹配失败
                $item['creator'] = 0;
                $item['manage']  = 1;
            } else {
                $item['creator'] = 0;
                $item['manage']  = 0;
                // 非创建者且无管理权限时，不返回项目密码
                unset($item['password']);
            }

            // 判定私密项目
            $item['is_private'] = empty($item['password'] ?? '') ? 0 : 1;

            // 过滤已标记删除的项目（直接使用数据库返回的值进行比较）
            if (($item['is_del'] ?? 0) == 1) {
                continue;
            }

            // 仅原创项目（直接使用数据库返回的值进行比较）
            if ($original > 0 && ($item['uid'] ?? 0) != $uid) {
                continue;
            }

            // 星标标记（使用宽松比较，兼容数据库返回字符串或整数）
            $item['is_star'] = in_array((string)($item['item_id'] ?? '0'), $starItemIds) ? 1 : 0;

            $items[] = $item;
        }

        // 6. 处理置顶项目（ItemTop）
        // 注意：只有在有置顶项目时才添加 top 字段，与旧后端逻辑保持一致
        if ($items) {
            $topItemIds = [];
            $topRows    = DB::table('item_top')
                ->where('uid', $uid)
                ->get();
            foreach ($topRows as $row) {
                $topItemIds[] = (string) $row->item_id; // 使用字符串，与数据库返回类型一致
            }

            // 只有在有置顶项目时才处理，与旧后端逻辑一致
            if ($topItemIds) {
                $topList    = [];
                $normalList = [];
                foreach ($items as &$item) {
                    // 给所有项目添加 top 字段（与旧后端逻辑一致）
                    $item['top'] = 0;
                    // 使用宽松比较，兼容数据库返回字符串或整数
                    if (in_array((string)$item['item_id'], $topItemIds)) {
                        $item['top'] = 1;
                        $topList[]   = $item;
                    } else {
                        $normalList[] = $item;
                    }
                }
                unset($item);
                $items = array_merge($topList, $normalList);
            }
            // 如果没有置顶项目，不添加 top 字段（与旧后端逻辑一致）
        }

        // 7. 读取项目顺序配置（ItemSort）
        if ($items) {
            $sortRow = DB::table('item_sort')
                ->where('uid', $uid)
                ->where('item_group_id', $itemGroupId)
                ->first();

            if ($sortRow && !empty($sortRow->item_sort_data)) {
                $json = htmlspecialchars_decode((string) $sortRow->item_sort_data, ENT_QUOTES);
                $map  = json_decode($json, true) ?: [];

                if (is_array($map) && $map) {
                    foreach ($items as &$item) {
                        $id = $item['item_id']; // 直接使用数据库返回的值
                        // map 的键可能是字符串或数字，需要兼容两种情况
                        $idInt = (int) $id;
                        if (isset($map[$idInt])) {
                            $item['s_number'] = $map[$idInt];
                        } elseif (isset($map[$id])) {
                            $item['s_number'] = $map[$id];
                        } else {
                            $item['s_number'] = $id;
                        }
                    }
                    unset($item);

                    // 按 s_number 升序排序，保持与旧版 _sort_by_key 行为一致
                    usort($items, function (array $a, array $b): int {
                        $sa = (int) ($a['s_number'] ?? 0);
                        $sb = (int) ($b['s_number'] ?? 0);
                        if ($sa === $sb) {
                            return 0;
                        }
                        return $sa < $sb ? -1 : 1;
                    });
                }
            }
        }

        return $this->success($response, array_values($items));
    }

    /**
     * 项目详情接口（兼容旧接口 Api/Item/info）。
     *
     * 功能：
     * - 支持 item_id 或 item_domain 参数
     * - 检查访问权限
     * - 获取菜单（支持搜索、筛选）
     * - 返回完整的项目信息（包括 AI 配置、全局参数等）
     */
    public function info(Request $request, Response $response): Response
    {
        // 获取参数（兼容 item_id 和 item_domain）
        $itemId    = $this->getParam($request, 'item_id', "");
        $itemDomain = $this->getParam($request, 'item_domain', '');
        $currentPageId = $this->getParam($request, 'page_id', 0);
        $defaultPageId = $this->getParam($request, 'default_page_id', 0);
        $keyword = $this->getParam($request, 'keyword', '');
        $filterStatus = $this->getParam($request, 'filter_status', '');
        $showMD = $this->getParam($request, 'show_md', 1);

        // 如果 item_id 不是数字，则视为 item_domain
        if (!is_numeric($itemId)) {
            $itemDomain = (string) $itemId;
            $itemId    = 0;
        } else {
            $itemId = (int) $itemId;
        }

        // 通过 item_domain 查找 item_id
        if ($itemDomain !== '') {
            $item = \App\Model\Item::findByDomain($itemDomain);
            if ($item && !empty($item->item_id)) {
                $itemId = (int) $item->item_id;
            }
        }

        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目不存在或者已删除');
        }

        // 获取登录用户（非严格模式，允许游客访问）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有访问权限');
        }

        // 获取项目信息
        $item = \App\Model\Item::findById($itemId);
        if (!$item || (int) $item->is_del === 1) {
            // 防止暴力扫描，延迟返回
            sleep(1);
            return $this->error($response, 10101, '项目不存在或者已删除');
        }

        // 获取菜单
        $menu = [];
        if (!empty($keyword)) {
            // 搜索模式：获取用户有权限的目录 ID
            $catIds = \App\Model\Member::getCatIds($itemId, $uid);
            $menu['pages']    = \App\Model\Page::search($itemId, $catIds, $keyword);
            $menu['catalogs'] = [];
        } else {
            // 正常模式：获取完整菜单
            $menu = \App\Model\Item::getMenuByCache($itemId);
            if ($uid > 0) {
                $menu = \App\Model\Item::filterMemberItem($uid, $itemId, $menu);
            }
        }

        // 应用筛选条件
        if ($filterStatus || !$showMD) {
            $menu = $this->applyFilters($menu, $filterStatus, $showMD, $itemId);
        }

        // 获取权限信息
        $itemEdit   = $this->checkItemEdit($uid, $itemId);
        $itemManage = $this->checkItemManage($uid, $itemId);

        // 获取未读消息数（使用 Message 系统，替代旧的 Notice 统计）
        $unreadCount = 0;
        if ($uid > 0) {
            try {
                $unreadCount = \App\Model\Message::getRemindList($uid, 1, 1, 0)['total'] ?? 0;
            } catch (\Throwable $e) {
                $unreadCount = 0;
            }
        }

        // 获取默认展开的目录信息
        $defaultCatId2 = $defaultCatId3 = $defaultCatId4 = 0;
        if ($defaultPageId > 0) {
            $page = \App\Model\Page::findByIdWithContent($itemId, $defaultPageId);
            if ($page && !empty($page['cat_id'])) {
                $defaultCatId4 = (int) $page['cat_id'];
                $cat1          = \App\Model\Catalog::findById($defaultCatId4);
                if ($cat1 && (int) $cat1->parent_cat_id > 0) {
                    $defaultCatId3 = (int) $cat1->parent_cat_id;
                    $cat2          = \App\Model\Catalog::findById($defaultCatId3);
                    if ($cat2 && (int) $cat2->parent_cat_id > 0) {
                        $defaultCatId2 = (int) $cat2->parent_cat_id;
                    } else {
                        $defaultCatId2 = $defaultCatId3;
                        $defaultCatId3 = 0;
                    }
                } else {
                    $defaultCatId3 = $defaultCatId4;
                    $defaultCatId4 = 0;
                }
            }
        }

        // 归档项目去掉编辑权限
        if (!empty($item->is_archived)) {
            $itemEdit   = false;
            $itemManage = false;
        }

        // 获取全局参数（RunApi 项目）
        $globalParam = [];
        if ((int) $item->item_type === 3) {
            $globalParam = \App\Model\Runapi::getGlobalParam($itemId);
        }

        // 获取 AI 配置
        $aiConfig = \App\Model\ItemAiConfig::getConfig($itemId);

        // 检查强制登录设置
        $forceLogin = (int) \App\Model\Options::get('force_login', 0);
        if ($forceLogin > 0 && $uid <= 0) {
            return $this->error($response, 10312, '需要登录');
        }

        // 构建返回数据
        // 直接使用数据库返回的原始 item_id，保持数据库配置的类型（字符串），不进行类型转换
        $domain = !empty($item->item_domain) ? $item->item_domain : (string) $item->item_id;

        $return = [
            'item_id'                    => $item->item_id, // 直接使用数据库返回的原始值
            'item_domain'                => $item->item_domain ?? '',
            'is_archived'                => $item->is_archived ?? 0, // 直接使用数据库返回的原始值
            'item_name'                  => $item->item_name ?? '',
            'default_page_id'            => (string) $defaultPageId, // 这个是参数，保持字符串类型
            'default_cat_id2'            => $defaultCatId2,
            'default_cat_id3'            => $defaultCatId3,
            'default_cat_id4'            => $defaultCatId4,
            'unread_count'               => $unreadCount,
            'item_type'                  => $item->item_type ?? 0, // 直接使用数据库返回的原始值
            'menu'                       => $menu,
            'is_login'                   => $uid > 0,
            'item_edit'                  => $itemEdit,
            'item_manage'                => $itemManage,
            'ItemPermn'                  => $itemEdit, // 兼容字段
            'ItemCreator'                => $itemManage, // 兼容字段
            'current_page_id'            => $currentPageId,
            'global_param'               => $globalParam,
            'show_watermark'             => '0',
            'allow_comment'              => $item->allow_comment ?? 0, // 直接使用数据库返回的原始值
            'allow_feedback'             => $item->allow_feedback ?? 0, // 直接使用数据库返回的原始值
            'ai_knowledge_base_enabled'  => $aiConfig['enabled'] ?? 0, // 直接使用配置返回的原始值
            'ai_config'                 => $aiConfig,
        ];

        return $this->success($response, $return);
    }

    /**
     * 应用筛选条件（状态筛选、Markdown 文档筛选）
     *
     * 精确对齐旧版 ThinkPHP 行为：
     * - 是否为 Markdown 文档：通过真实的 page_content 判断，而不是仅依赖 ext_info；
     * - 状态筛选：基于 RunApi JSON 中的 info.apiStatus，并映射为中文状态文案。
     *
     * @param array  $menu         菜单结构
     * @param string $filterStatus 状态筛选（格式：开发中,测试中,已完成）
     * @param int    $showMD       是否显示 Markdown 文档（1显示，0不显示）
     * @param int    $itemId       项目 ID
     * @return array 筛选后的菜单结构
     */
    private function applyFilters(array $menu, string $filterStatus, int $showMD, int $itemId): array
    {
        if (empty($menu)) {
            return $menu;
        }

        // 处理根目录的页面
        if (!empty($menu['pages']) && is_array($menu['pages'])) {
            $filteredPages = [];
            foreach ($menu['pages'] as $page) {
                if ($this->shouldShowPage($page, $filterStatus, $showMD, $itemId)) {
                    $filteredPages[] = $page;
                }
            }
            $menu['pages'] = $filteredPages;
        }

        // 递归处理目录
        if (!empty($menu['catalogs']) && is_array($menu['catalogs'])) {
            $this->filterCatalogsRecursive($menu['catalogs'], $filterStatus, $showMD, $itemId);
        }

        return $menu;
    }

    /**
     * 递归过滤目录（精确还原旧版逻辑）
     *
     * @param array  $catalogs     目录列表（引用传递）
     * @param string $statusFilter 状态筛选字符串
     * @param int    $showMD       是否显示 Markdown
     * @param int    $itemId       项目 ID
     */
    private function filterCatalogsRecursive(array &$catalogs, string $statusFilter, int $showMD, int $itemId): void
    {
        if (empty($catalogs)) {
            return;
        }

        foreach ($catalogs as &$catalog) {
            // 处理目录下的页面
            if (!empty($catalog['pages']) && is_array($catalog['pages'])) {
                $filteredPages = [];
                foreach ($catalog['pages'] as $page) {
                    if ($this->shouldShowPage($page, $statusFilter, $showMD, $itemId)) {
                        $filteredPages[] = $page;
                    }
                }
                $catalog['pages'] = $filteredPages;
            }

            // 递归处理子目录
            if (!empty($catalog['catalogs']) && is_array($catalog['catalogs'])) {
                $this->filterCatalogsRecursive($catalog['catalogs'], $statusFilter, $showMD, $itemId);
            }
        }
        unset($catalog);
    }

    /**
     * 判断单个页面是否应该展示（精确对齐旧版 shouldShowPage 行为）
     *
     * 规则：
     * - 通过读取 page_content，尝试按 RunApi JSON 解析；
     * - 若无法解析为带 info.url 的 JSON，则视为 Markdown 文档；
     * - showMD=0 时隐藏 Markdown 文档；
     * - 有状态筛选时，根据 info.apiStatus → 中文文案做匹配。
     *
     * @param array  $page         页面数据（菜单中的节点）
     * @param string $statusFilter 状态筛选字符串（如 "开发中,测试中"）
     * @param int    $showMD       是否显示 Markdown（1 显示，0 不显示）
     * @param int    $itemId       项目 ID
     * @return bool
     */
    private function shouldShowPage(array $page, string $statusFilter, int $showMD, int $itemId): bool
    {
        $pageId = (int) ($page['page_id'] ?? 0);
        if ($pageId <= 0) {
            // 没有 page_id 的节点，当作普通文档处理
            return $showMD === 1;
        }

        // 从数据库获取页面内容（兼容旧数据的压缩/解压逻辑由 Page 模型处理）
        $pageRow = \App\Model\Page::findByIdWithContent($itemId, $pageId);
        if (!$pageRow) {
            // 取不到内容时，遵循旧逻辑：仅受 showMD 控制
            return $showMD === 1;
        }

        $pageContent = (string) ($pageRow['page_content'] ?? '');

        // 先进行 HTML 转义还原，然后尝试按 JSON 解析
        $decodedContent = htmlspecialchars_decode($pageContent);
        $obj            = json_decode($decodedContent, true);

        // 如果解析失败或者没有 info.url 字段，说明是 markdown 文档
        if (!$obj || !isset($obj['info']) || !isset($obj['info']['url'])) {
            return $showMD === 1;
        }

        // 到这里说明是接口文档。如果没有状态筛选，则直接展示
        if ($statusFilter === '') {
            return true;
        }

        // 状态筛选
        $statusArray = array_filter(array_map('trim', explode(',', $statusFilter)));
        if (empty($statusArray)) {
            return true;
        }

        $apiStatus = $obj['info']['apiStatus'] ?? null;
        $pageStatusText = $this->getStatusTextFromApiStatus($apiStatus);
        if ($pageStatusText === '') {
            $pageStatusText = '未操作';
        }

        return in_array($pageStatusText, $statusArray, true);
    }

    /**
     * 将 RunApi 的 apiStatus 数值映射为中文状态文案（保持与旧版 getStatusText 一致）
     *
     * @param mixed $status
     * @return string
     */
    private function getStatusTextFromApiStatus($status): string
    {
        $statusMap = [
            '0' => '未操作',
            '1' => '开发中',
            '2' => '测试中',
            '3' => '已完成',
            '4' => '需修改',
            '5' => '已废弃',
        ];

        $key = (string) $status;
        return $statusMap[$key] ?? '未操作';
    }

    /**
     * 项目详细信息（管理用）（兼容旧接口 Api/Item/detail）。
     *
     * 功能：
     * - 获取项目的详细信息
     * - 权限检查（checkItemManage）
     * - 返回项目所属分组（多选）
     */
    public function detail(Request $request, Response $response): Response
    {
        $itemId = $this->getParam($request, 'item_id', 0);

        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目不存在');
        }

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查管理权限
        if (!$this->checkItemManage($uid, $itemId)) {
            return $this->error($response, 10303, '您没有管理权限');
        }

        // 获取项目信息
        $item = \App\Model\Item::findById($itemId);
        if (!$item) {
            return $this->error($response, 10101, '项目不存在');
        }

        $data = (array) $item;

        // 返回项目所属分组（多选）。基于当前登录用户的分组
        $groupIds = [];
        $groups = DB::table('item_group')
            ->where('uid', $uid)
            ->get()
            ->all();

        foreach ($groups as $g) {
            $itemIds = (string) ($g->item_ids ?? '');
            if (!empty($itemIds)) {
                $ids = explode(',', $itemIds);
                // 使用宽松比较，自动处理字符串和整数的匹配
                if (in_array($itemId, $ids)) {
                    $groupIds[] = (int) $g->id;
                }
            }
        }

        $data['group_ids'] = $groupIds;

        return $this->success($response, $data);
    }

    /**
     * 获取项目的 AI 知识库配置（兼容旧接口 Api/Item/getAiKnowledgeBaseConfig）。
     */
    public function getAiKnowledgeBaseConfig(Request $request, Response $response): Response
    {
        // 登录用户（非严格模式，允许游客访问，只用于权限判断）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        $itemId = (int) $this->getParam($request, 'item_id', 0);
        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目ID不能为空');
        }

        // 检查项目访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有访问该项目的权限');
        }

        // 获取配置（可能为 null，这里统一转换为数组，避免 success() 类型错误）
        $config = \App\Model\ItemAiConfig::getConfig($itemId) ?? [];

        // 如果项目已启用 AI 知识库，检查索引状态，如果索引不存在则自动重建
        if (!empty($config['enabled'])) {
            $aiServiceUrl   = \App\Model\Options::get('ai_service_url', '');
            $aiServiceToken = \App\Model\Options::get('ai_service_token', '');

            if ($aiServiceUrl && $aiServiceToken) {
                // 检查索引状态
                $statusUrl   = rtrim($aiServiceUrl, '/') . '/api/index/status?item_id=' . $itemId;
                $indexStatus = \App\Common\Helper\AiHelper::callService($statusUrl, null, $aiServiceToken, 'GET', 10);

                // 如果索引不存在（被删除或从未创建），则自动触发重建（异步，不阻塞当前请求）
                if ($indexStatus === false || (isset($indexStatus['indexed']) && !$indexStatus['indexed'])) {
                    register_shutdown_function(function () use ($itemId, $aiServiceUrl, $aiServiceToken) {
                        \App\Common\Helper\AiHelper::rebuild($itemId, $aiServiceUrl, $aiServiceToken);
                    });
                }

                // 可选：预热模型（不影响返回结果，失败静默）
                try {
                    $warmupUrl   = rtrim($aiServiceUrl, '/') . '/api/warmup';
                    \App\Common\Helper\AiHelper::callService($warmupUrl, null, $aiServiceToken, 'POST', 3);
                } catch (\Throwable $e) {
                    // 忽略预热异常
                }
            }
        }

        return $this->success($response, $config);
    }

    /**
     * 更新项目信息（兼容旧接口 Api/Item/update）。
     *
     * 功能：
     * - 更新项目基本信息
     * - 权限检查（checkItemManage）
     * - 个性域名唯一性检查
     * - 处理项目分组（多选）
     * - 处理评论和反馈功能开关
     */
    public function update(Request $request, Response $response): Response
    {
        $itemId          = $this->getParam($request, 'item_id', 0);
        $itemName        = $this->getParam($request, 'item_name', '');
        $itemDescription = $this->getParam($request, 'item_description', '');
        $itemDomain      = $this->getParam($request, 'item_domain', '');
        $password        = $this->getParam($request, 'password', '');
        $itemGroupId     = $this->getParam($request, 'item_group_id', 0);
        $itemGroupIdsRaw = $this->getParam($request, 'item_group_ids', '');
        $allowComment    = $this->getParam($request, 'allow_comment', null);
        $allowFeedback   = $this->getParam($request, 'allow_feedback', null);

        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目不存在');
        }

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查管理权限
        if (!$this->checkItemManage($uid, $itemId)) {
            return $this->error($response, 10303, '您没有管理权限');
        }


        // 准备更新数据
        $saveData = [
            'item_name'        => $itemName,
            'item_description' => $itemDescription,
            'password'         => $password,
        ];

        // 处理评论和反馈功能开关（仅常规项目）
        $item = \App\Model\Item::findById($itemId);
        if ($item && (int) $item->item_type === 1) {
            if ($allowComment !== null) {
                $saveData['allow_comment'] = $allowComment ? 1 : 0;
            }
            if ($allowFeedback !== null) {
                $saveData['allow_feedback'] = $allowFeedback ? 1 : 0;
            }
        }

        // 更新项目信息
        $affected = DB::table('item')
            ->where('item_id', $itemId)
            ->update($saveData);

        if ($affected <= 0) {
            return $this->error($response, 10103, '更新失败');
        }

        // 处理项目分组（多选）
        $selectedGroupIds = [];
        if (!empty($itemGroupIdsRaw)) {
            if (is_array($itemGroupIdsRaw)) {
                $selectedGroupIds = $itemGroupIdsRaw;
            } else {
                // 兼容前端传字符串（JSON 或逗号分隔）
                $tmp = json_decode(htmlspecialchars_decode($itemGroupIdsRaw), true);
                if (is_array($tmp)) {
                    $selectedGroupIds = $tmp;
                } else {
                    $selectedGroupIds = explode(',', (string) $itemGroupIdsRaw);
                }
            }
        } elseif ($itemGroupId > 0) {
            $selectedGroupIds = [$itemGroupId];
        }

        // 过滤有效数字 id
        $selectedGroupIds = array_values(array_unique(array_filter(array_map('intval', $selectedGroupIds))));

        if (!empty($selectedGroupIds)) {
            // 拉取当前用户的全部分组，进行增删
            $groups = DB::table('item_group')
                ->where('uid', $uid)
                ->get()
                ->all();

            foreach ($groups as $g) {
                $gId = (int) $g->id;
                $itemIds = (string) ($g->item_ids ?? '');
                $ids = [];

                if (!empty($itemIds)) {
                    $ids = array_filter(array_map('intval', explode(',', $itemIds)));
                }

                if (in_array($gId, $selectedGroupIds, true)) {
                    // 需要包含此项目
                    if (!in_array($itemId, $ids, true)) {
                        $ids[] = $itemId;
                        $ids = array_values(array_unique($ids));
                        DB::table('item_group')
                            ->where('id', $gId)
                            ->update(['item_ids' => implode(',', $ids)]);
                    }
                } else {
                    // 需要移除此项目
                    $ids = array_values(array_filter($ids, function ($id) use ($itemId) {
                        return $id !== $itemId;
                    }));
                    DB::table('item_group')
                        ->where('id', $gId)
                        ->update(['item_ids' => implode(',', $ids)]);
                }
            }
        }

        return $this->success($response, ['success' => true]);
    }

    /**
     * 创建项目（兼容旧接口 Api/Item/add）
     */
    public function add(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $itemName = trim($this->getParam($request, 'item_name', ''));
        $itemDomain = trim($this->getParam($request, 'item_domain', ''));
        $copyItemId = $this->getParam($request, 'copy_item_id', 0);
        $password = $this->getParam($request, 'password', '');
        $itemDescription = $this->getParam($request, 'item_description', '');
        $itemType = $this->getParam($request, 'item_type', 1);
        $itemGroupId = $this->getParam($request, 'item_group_id', 0);
        $itemGroupIdsRaw = $this->getParam($request, 'item_group_ids', '');
        $allowComment = $this->getParam($request, 'allow_comment', null);
        $allowFeedback = $this->getParam($request, 'allow_feedback', null);

        if ($itemName === '') {
            return $this->error($response, 10100, '项目名不能为空');
        }

        // 检查个性域名
        if ($itemDomain !== '') {
            if (!ctype_alnum($itemDomain) || is_numeric($itemDomain)) {
                return $this->error($response, 10305);
            }

            $existing = \App\Model\Item::findByDomain($itemDomain);
            if ($existing) {
                return $this->error($response, 10304);
            }
        }

        // 如果是复制项目
        if ($copyItemId > 0) {
            if (!$this->checkItemEdit($uid, $copyItemId)) {
                return $this->error($response, 10103);
            }

            $newItemId = \App\Model\Item::copy($copyItemId, $uid, $itemName, $itemDescription, $password, $itemDomain);
            if ($newItemId) {
                return $this->success($response, ['item_id' => $newItemId]);
            } else {
                return $this->error($response, 10101);
            }
        }

        // 创建新项目
        $newItemId = \App\Model\Item::getANewItemId();
        $user = \App\Model\User::findById($uid);
        $username = $user ? ($user->username ?? '') : '';

        $insertData = [
            'item_id'          => $newItemId,
            'uid'              => $uid,
            'username'         => $username,
            'item_name'        => $itemName,
            'password'         => $password,
            'item_description' => $itemDescription,
            'item_domain'      => $itemDomain,
            'item_type'        => (int) $itemType,
            'addtime'          => time(),
        ];

        // 处理评论和反馈功能开关（仅常规项目）
        if ((int) $itemType === 1) {
            if ($allowComment !== null) {
                $insertData['allow_comment'] = $allowComment ? 1 : 0;
            }
            if ($allowFeedback !== null) {
                $insertData['allow_feedback'] = $allowFeedback ? 1 : 0;
            }
        }

        $itemId = \App\Model\Item::add($insertData);
        if (!$itemId) {
            return $this->error($response, 10101);
        }

        // 如果是单页应用，创建默认页
        if ($itemType == 2) {
            $pageData = [
                'author_uid'      => $uid,
                'author_username' => $username,
                'page_title'      => $itemName,
                'item_id'         => $itemId,
                'cat_id'          => 0,
                'page_content'    => '欢迎使用showdoc。点击右上方的编辑按钮进行编辑吧！',
                'addtime'         => time(),
            ];
            \App\Model\Page::addPage($itemId, $pageData);
        }

        // 如果是表格应用，创建默认页
        if ($itemType == 4) {
            $pageData = [
                'author_uid'      => $uid,
                'author_username' => $username,
                'page_title'      => $itemName,
                'item_id'         => $itemId,
                'cat_id'          => 0,
                'page_content'    => '',
                'addtime'         => time(),
            ];
            \App\Model\Page::addPage($itemId, $pageData);
        }

        // 如果是白板项目，创建默认页
        if ($itemType == 5) {
            $pageData = [
                'author_uid'      => $uid,
                'author_username' => $username,
                'page_title'      => $itemName,
                'item_id'         => $itemId,
                'cat_id'          => 0,
                'page_content'    => '',
                'addtime'         => time(),
            ];
            \App\Model\Page::addPage($itemId, $pageData);
        }

        // 处理项目分组（多分组支持）
        $selectedGroupIds = [];
        if (!empty($itemGroupIdsRaw)) {
            if (is_array($itemGroupIdsRaw)) {
                $selectedGroupIds = $itemGroupIdsRaw;
            } else {
                $tmp = json_decode(htmlspecialchars_decode($itemGroupIdsRaw), true);
                if (is_array($tmp)) {
                    $selectedGroupIds = $tmp;
                } else {
                    $selectedGroupIds = explode(',', strval($itemGroupIdsRaw));
                }
            }
        } elseif ($itemGroupId > 0) {
            $selectedGroupIds = [$itemGroupId];
        }

        $selectedGroupIds = array_values(array_unique(array_filter(array_map('intval', $selectedGroupIds))));

        if (!empty($selectedGroupIds)) {
            foreach ($selectedGroupIds as $gId) {
                $group = DB::table('item_group')
                    ->where('id', $gId)
                    ->where('uid', $uid)
                    ->first();

                if ($group) {
                    $itemIds = [];
                    if (!empty($group->item_ids)) {
                        $itemIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $group->item_ids)))));
                    }
                    if (!in_array($itemId, $itemIds)) {
                        $itemIds[] = $itemId;
                    }
                    DB::table('item_group')
                        ->where('id', $gId)
                        ->where('uid', $uid)
                        ->update(['item_ids' => implode(',', $itemIds)]);
                } else {
                    // 分组不存在，创建新分组
                    DB::table('item_group')
                        ->where('id', $gId)
                        ->where('uid', $uid)
                        ->update(['item_ids' => (string) $itemId]);
                }
            }
        }

        return $this->success($response, ['item_id' => $itemId]);
    }

    /**
     * 项目转让（兼容旧接口 Api/Item/attorn）。
     */
    public function attorn(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemId   = $this->getParam($request, 'item_id', 0);
        $username = $this->getParam($request, 'username', '');
        $password = $this->getParam($request, 'password', '');

        if ($itemId <= 0 || $username === '' || $password === '') {
            return $this->error($response, 10101, '参数错误');
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        $item = \App\Model\Item::findById($itemId);
        if (!$item) {
            return $this->error($response, 10101, '项目不存在');
        }

        // 权限：需要项目管理权限
        if (!$this->checkItemManage($uid, $itemId)) {
            return $this->error($response, 10303, '您没有管理权限');
        }

        // 使用项目当前拥有者账号校验密码
        $ownerUsername = (string) ($item->username ?? '');
        $owner         = \App\Model\User::checkLogin($ownerUsername, $password);
        if (!$owner) {
            return $this->error($response, 10208, '密码不正确');
        }

        // 查找目标用户（支持用户名或邮箱）
        $targetUserObj = \App\Model\User::findByUsernameOrEmail($username);
        if (!$targetUserObj) {
            return $this->error($response, 10209, '用户不存在');
        }

        $targetUser = (array) $targetUserObj;

        $affected = DB::table('item')
            ->where('item_id', $itemId)
            ->update([
                'uid'      => (int) ($targetUser['uid'] ?? 0),
                'username' => (string) ($targetUser['username'] ?? ''),
            ]);

        if ($affected <= 0) {
            return $this->error($response, 10101, '转让失败');
        }

        $updatedItem = \App\Model\Item::findById($itemId);

        // 记录转让日志（兼容旧 itemAttornLog 表）
        try {
            DB::table('item_attorn_log')->insert([
                'item_id'       => $itemId,
                'from_uid'      => $uid,
                'from_username' => (string) ($loginUser['username'] ?? ''),
                'to_uid'        => (int) ($targetUser['uid'] ?? 0),
                'to_username'   => (string) ($targetUser['username'] ?? ''),
                'addtime'       => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // 日志失败不影响主流程
        }

        return $this->success($response, $updatedItem ? (array) $updatedItem : []);
    }

    /**
     * 删除项目（软删）（兼容旧接口 Api/Item/delete）。
     */
    public function delete(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemId   = $this->getParam($request, 'item_id', 0);
        $password = $this->getParam($request, 'password', '');

        if ($itemId <= 0 || $password === '') {
            return $this->error($response, 10101, '参数错误');
        }

        $uid  = (int) ($loginUser['uid'] ?? 0);
        $item = \App\Model\Item::findById($itemId);
        if (!$item) {
            return $this->error($response, 10101, '项目不存在');
        }

        if (!$this->checkItemManage($uid, $itemId)) {
            return $this->error($response, 10303, '您没有管理权限');
        }

        // 校验项目拥有者密码
        $ownerUsername = (string) ($item->username ?? '');
        $owner         = \App\Model\User::checkLogin($ownerUsername, $password);
        if (!$owner) {
            return $this->error($response, 10208, '密码不正确');
        }

        // 软删除项目
        $ret = \App\Model\Item::softDeleteItem($itemId);

        if ($ret) {
            // 删除项目相关评论和反馈
            DB::table('page_comment')->where('item_id', $itemId)->delete();
            DB::table('page_feedback')->where('item_id', $itemId)->delete();
        }

        if (!$ret) {
            return $this->error($response, 10101, '删除失败');
        }

        return $this->success($response, ['success' => true]);
    }

    /**
     * 归档项目（兼容旧接口 Api/Item/archive）。
     */
    public function archive(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemId   = $this->getParam($request, 'item_id', 0);
        $password = $this->getParam($request, 'password', '');

        if ($itemId <= 0 || $password === '') {
            return $this->error($response, 10101, '参数错误');
        }

        $uid  = (int) ($loginUser['uid'] ?? 0);
        $item = \App\Model\Item::findById($itemId);
        if (!$item) {
            return $this->error($response, 10101, '项目不存在');
        }

        if (!$this->checkItemManage($uid, $itemId)) {
            return $this->error($response, 10303, '您没有管理权限');
        }

        $ownerUsername = (string) ($item->username ?? '');
        $owner         = \App\Model\User::checkLogin($ownerUsername, $password);
        if (!$owner) {
            return $this->error($response, 10208, '密码不正确');
        }

        $affected = DB::table('item')
            ->where('item_id', $itemId)
            ->update(['is_archived' => 1]);

        if ($affected <= 0) {
            return $this->error($response, 10101, '归档失败');
        }

        return $this->success($response, ['success' => true]);
    }

    /**
     * 获取项目访问密钥（兼容旧接口 Api/Item/getKey）。
     */
    public function getKey(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemId = $this->getParam($request, 'item_id', 0);
        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目不存在');
        }

        $uid  = (int) ($loginUser['uid'] ?? 0);
        $item = \App\Model\Item::findById($itemId);
        if (!$item) {
            return $this->error($response, 10101, '项目不存在');
        }

        if (!$this->checkItemManage($uid, $itemId)) {
            return $this->error($response, 10303, '您没有管理权限');
        }

        $token = \App\Model\ItemToken::getTokenByItemId($itemId);
        if (!$token) {
            return $this->error($response, 10101, '获取密钥失败');
        }

        return $this->success($response, $token);
    }

    /**
     * 重置项目访问密钥（兼容旧接口 Api/Item/resetKey）。
     */
    public function resetKey(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemId = $this->getParam($request, 'item_id', 0);
        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目不存在');
        }

        $uid  = (int) ($loginUser['uid'] ?? 0);
        $item = \App\Model\Item::findById($itemId);
        if (!$item) {
            return $this->error($response, 10101, '项目不存在');
        }

        if (!$this->checkItemManage($uid, $itemId)) {
            return $this->error($response, 10303, '您没有管理权限');
        }

        $token = \App\Model\ItemToken::resetToken($itemId);
        if (!$token) {
            return $this->error($response, 10101, '重置失败');
        }

        return $this->success($response, $token);
    }

    /**
     * 设置项目的 AI 知识库配置（兼容旧接口 Api/Item/setAiKnowledgeBaseConfig）。
     */
    public function setAiKnowledgeBaseConfig(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemId = $this->getParam($request, 'item_id', 0);
        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目ID不能为空');
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        if (!$this->checkItemManage($uid, $itemId)) {
            return $this->error($response, 10303, '您没有管理该项目的权限');
        }

        $data = [];

        $enabled = $this->getParam($request, 'enabled', null);
        if ($enabled !== null) {
            $data['enabled'] = (int) $enabled;
        }

        $dialogCollapsed = $this->getParam($request, 'dialog_collapsed', null);
        if ($dialogCollapsed !== null) {
            $data['dialog_collapsed'] = (int) $dialogCollapsed;
        }

        $welcomeMessage = $this->getParam($request, 'welcome_message', null);
        if ($welcomeMessage !== null && $welcomeMessage !== '') {
            $data['welcome_message'] = (string) $welcomeMessage;
        }

        if (empty($data)) {
            return $this->error($response, 10101, '没有需要更新的配置');
        }

        $ret = \App\Model\ItemAiConfig::saveConfig($itemId, $data);
        if (!$ret) {
            return $this->error($response, 10101, '更新失败');
        }

        return $this->success($response, ['success' => true]);
    }

    /**
     * 通过开放 API 更新项目信息（兼容旧接口 Api/Item/updateByApi）。
     *
     * 说明：旧实现内部转发到 OpenController::updateItem，这里保持行为一致。
     */
    public function updateByApi(Request $request, Response $response): Response
    {
        $open = new \App\Api\Controller\OpenController(
            $this->container ?? null,
            $this->logger ?? null
        );

        return $open->updateItem($request, $response);
    }

    /**
     * 项目置顶 / 取消置顶（兼容旧接口 Api/Item/top）。
     */
    public function top(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemId = $this->getParam($request, 'item_id', 0);
        $action = $this->getParam($request, 'action', '');
        if ($itemId <= 0 || !in_array($action, ['top', 'cancel'], true)) {
            return $this->error($response, 10101, '参数错误');
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        try {
            if ($action === 'top') {
                // 先删除旧记录再添加，避免重复
                DB::table('item_top')
                    ->where('uid', $uid)
                    ->where('item_id', $itemId)
                    ->delete();

                $ret = DB::table('item_top')->insert([
                    'item_id' => $itemId,
                    'uid'     => $uid,
                    'addtime' => time(),
                ]);
            } else {
                $ret = DB::table('item_top')
                    ->where('uid', $uid)
                    ->where('item_id', $itemId)
                    ->delete() > 0;
            }
        } catch (\Throwable $e) {
            $ret = false;
        }

        if (!$ret) {
            return $this->error($response, 10101, '操作失败');
        }

        return $this->success($response, []);
    }

    /**
     * 校验项目访问密码 / 设置访问密码（兼容旧接口 Api/Item/pwd）。
     *
     * 说明：
     * - 新实现仅负责校验密码是否正确并返回跳转地址；
     * - 实际访问授权仍依赖后续对 checkItemVisit 的增强（当前不再基于 PHP session）。
     */
    public function pwd(Request $request, Response $response): Response
    {
        $itemId    = $this->getParam($request, 'item_id', '');
        $pageId    = $this->getParam($request, 'page_id', 0);
        $password  = $this->getParam($request, 'password', '');
        $referUrl  = $this->getParam($request, 'refer_url', '');
        $captchaId = $this->getParam($request, 'captcha_id', '');
        $captcha   = $this->getParam($request, 'captcha', '');

        // 验证图形验证码
        if (!\App\Model\Captcha::check($captchaId, $captcha)) {
            return $this->error($response, 10206);
        }

        $itemDomain = '';
        if (!is_numeric($itemId)) {
            $itemDomain = (string) $itemId;
        } else {
            $itemId = (int) $itemId;
        }

        // 个性域名解析
        if ($itemDomain !== '') {
            $item = \App\Model\Item::findByDomain($itemDomain);
            if ($item && !empty($item->item_id)) {
                $itemId = (int) $item->item_id;
            }
        }

        // 如果传入 page_id，则通过页面反查 item_id
        if ($pageId > 0) {
            $page = \App\Model\Page::findById($pageId);
            if ($page && !empty($page['item_id'])) {
                $itemId = (int) $page['item_id'];
            }
        }

        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目不存在');
        }

        $item = \App\Model\Item::findById($itemId);
        if (!$item) {
            return $this->error($response, 10101, '项目不存在');
        }

        if ($password !== '' && (string) ($item->password ?? '') === $password) {
            // 旧版会在 session 中设置 visit_item_xx 标记；新版本交由前端自行持久化。
            $decodedRefer = base64_decode((string) $referUrl, true) ?: '';
            return $this->success($response, ['refer_url' => $decodedRefer]);
        }

        return $this->error($response, 10010);
    }

    /**
     * 项目列表（兼容旧接口 Api/Item/itemList）。
     */
    public function itemList(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        if ($uid <= 0) {
            return $this->error($response, 10102, '用户未登录');
        }

        $items = DB::table('item')
            ->where('uid', $uid)
            ->get()
            ->all();

        $result = [];
        foreach ($items as $row) {
            $result[] = (array) $row;
        }

        return $this->success($response, $result);
    }

    /**
     * 保存项目排序（兼容旧接口 Api/Item/sort）。
     */
    public function sort(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid         = (int) ($loginUser['uid'] ?? 0);
        $data        = $this->getParam($request, 'data', '');
        $itemGroupId = $this->getParam($request, 'item_group_id', 0);

        if ($data === '') {
            return $this->error($response, 10101, '排序数据不能为空');
        }

        try {
            $existing = DB::table('item_sort')
                ->where('uid', $uid)
                ->where('item_group_id', $itemGroupId)
                ->first();

            $payload = [
                'item_sort_data' => (string) $data,
                'item_group_id'  => $itemGroupId,
                'uid'            => $uid,
                'addtime'        => time(),
            ];

            if ($existing) {
                $ret = DB::table('item_sort')
                    ->where('uid', $uid)
                    ->where('item_group_id', $itemGroupId)
                    ->update($payload);
            } else {
                $ret = DB::table('item_sort')->insert($payload);
            }
        } catch (\Throwable $e) {
            $ret = false;
        }

        if (!$ret) {
            return $this->error($response, 10101, '保存失败');
        }

        return $this->success($response, []);
    }

    /**
     * 退出项目（兼容旧接口 Api/Item/exitItem）。
     */
    public function exitItem(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid    = (int) ($loginUser['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);
        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目不存在');
        }

        // 删除项目成员关系
        $ret = DB::table('item_member')
            ->where('item_id', $itemId)
            ->where('uid', $uid)
            ->delete();

        // 删除团队中的对应成员关系
        $row = DB::table('team_item_member')
            ->leftJoin('team', 'team.id', '=', 'team_item_member.team_id')
            ->select(['team_item_member.team_id'])
            ->where('team_item_member.item_id', $itemId)
            ->where('team_item_member.member_uid', $uid)
            ->first();

        if ($row && !empty($row->team_id)) {
            $teamId = (int) $row->team_id;
            DB::table('team_item_member')
                ->where('member_uid', $uid)
                ->where('team_id', $teamId)
                ->delete();
            DB::table('team_member')
                ->where('member_uid', $uid)
                ->where('team_id', $teamId)
                ->delete();
        }

        if ($ret <= 0 && !$row) {
            return $this->error($response, 10101, '退出失败');
        }

        return $this->success($response, []);
    }

    /**
     * 在某个项目中根据内容搜索（兼容旧接口 Api/Item/search）。
     */
    public function search(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $keyword = $this->getParam($request, 'keyword', '');
        $itemId  = $this->getParam($request, 'item_id', 0);

        if ($itemId <= 0 || $keyword === '') {
            return $this->error($response, 10101, '参数错误');
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        $item = \App\Model\Item::findById($itemId);
        if (!$item || (int) ($item->is_del ?? 0) === 1) {
            return $this->error($response, 10101, '项目不存在');
        }

        // 获取目录权限
        $catIds = \App\Model\Member::getCatIds($itemId, $uid);
        $pages  = \App\Model\Page::search($itemId, $catIds, $keyword);

        if ($pages) {
            foreach ($pages as &$page) {
                $content = (string) ($page['page_content'] ?? '');
                $pos     = mb_stripos($content, $keyword);
                $len     = mb_strlen($keyword);
                $start   = $pos !== false && $pos > 100 ? $pos - 100 : 0;
                $page['search_content'] = '...' . mb_substr($content, $start, $len + 200) . '...';
                unset($page['page_content']);
                $page['item_id']   = (int) ($item->item_id ?? $itemId);
                $page['item_name'] = (string) ($item->item_name ?? '');
            }
            unset($page);
        }

        $result = [
            'item_id'   => (int) ($item->item_id ?? $itemId),
            'item_name' => (string) ($item->item_name ?? ''),
            'pages'     => $pages ?: [],
        ];

        return $this->success($response, $result);
    }

    /**
     * 获取项目变更日志（兼容旧接口 Api/Item/getChangeLog）。
     */
    public function getChangeLog(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $page   = $this->getParam($request, 'page', 1);
        $count  = $this->getParam($request, 'count', 15);
        $itemId = $this->getParam($request, 'item_id', 0);

        $page   = max(1, (int) $page);
        $count  = max(1, min(100, (int) $count));

        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目不存在');
        }

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10103, '没有编辑权限');
        }

        $ret = \App\Model\ItemChangeLog::getLog($itemId, $page, $count);

        return $this->success($response, $ret);
    }

    /**
     * 标星项目（兼容旧接口 Api/Item/star）。
     */
    public function star(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemId = $this->getParam($request, 'item_id', 0);
        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目不存在');
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10103, '没有权限');
        }

        $now = date('Y-m-d H:i:s');

        try {
            // 避免重复标星
            DB::table('item_star')
                ->where('uid', $uid)
                ->where('item_id', $itemId)
                ->delete();

            $id = DB::table('item_star')->insertGetId([
                'uid'        => $uid,
                'item_id'    => $itemId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
            $id = 0;
        }

        if ($id <= 0) {
            return $this->error($response, 10101, '标星失败');
        }

        return $this->success($response, ['id' => $id]);
    }

    /**
     * 取消标星项目（兼容旧接口 Api/Item/unstar）。
     */
    public function unstar(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemId = $this->getParam($request, 'item_id', 0);
        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目不存在');
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        DB::table('item_star')
            ->where('uid', $uid)
            ->where('item_id', $itemId)
            ->delete();

        return $this->success($response, []);
    }
}
