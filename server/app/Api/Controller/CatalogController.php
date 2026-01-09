<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * 目录相关 Api（新架构）。
 */
class CatalogController extends BaseController
{
    /**
     * 获取目录列表（兼容旧接口 Api/Catalog/catList）。
     *
     * 功能：
     * - 获取项目的目录列表
     * - 权限检查（checkItemVisit）
     * - 目录权限过滤（filterMemberCat）
     */
    public function catList(Request $request, Response $response): Response
    {
        $itemId = $this->getParam($request, 'item_id', 0);

        if ($itemId <= 0) {
            return $this->success($response, []);
        }

        // 获取登录用户（非严格模式，允许游客访问）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10103, '您没有访问权限');
        }

        // 获取目录列表
        $ret = \App\Model\Catalog::getList($itemId);
        $ret = \App\Model\Catalog::filterMemberCat($uid, $ret);

        return $this->success($response, $ret ?: []);
    }

    /**
     * 获取目录列表（分组，树形结构）（兼容旧接口 Api/Catalog/catListGroup）。
     */
    public function catListGroup(Request $request, Response $response): Response
    {
        $itemId = $this->getParam($request, 'item_id', 0);

        if ($itemId <= 0) {
            return $this->success($response, []);
        }

        // 获取登录用户（非严格模式，允许游客访问）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10103, '您没有访问权限');
        }

        // 获取目录列表（分组）
        $ret = \App\Model\Catalog::getList($itemId, true);
        $ret = \App\Model\Catalog::filterMemberCat($uid, $ret);

        return $this->success($response, $ret ?: []);
    }

    /**
     * 获取目录列表（带层级路径）（兼容旧接口 Api/Catalog/catListName）。
     *
     * 功能：
     * - 目录名按层级描述，例如："我的项目/用户接口/用户登录"
     */
    public function catListName(Request $request, Response $response): Response
    {
        $itemId = $this->getParam($request, 'item_id', 0);

        if ($itemId <= 0) {
            return $this->success($response, []);
        }

        // 获取登录用户（非严格模式，允许游客访问）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10103, '您没有访问权限');
        }

        // 获取目录列表（分组）
        $ret = \App\Model\Catalog::getList($itemId, true);
        $ret = \App\Model\Catalog::filterMemberCat($uid, $ret);

        if (empty($ret)) {
            return $this->success($response, []);
        }

        $return = [];

        // 递归函数，准备递归改名
        $rename = function ($catalog, $pCatName) use (&$return, &$rename) {
            if ($catalog) {
                foreach ($catalog as $value) {
                    $value['cat_name'] = $pCatName . '/' . $value['cat_name'];
                    $sub = $value['sub'] ?? [];
                    unset($value['sub']);
                    $return[] = $value;
                    if (!empty($sub)) {
                        $rename($sub, $value['cat_name']);
                    }
                }
            }
        };

        foreach ($ret as $value) {
            $sub = $value['sub'] ?? [];
            unset($value['sub']);
            $return[] = $value;
            if (!empty($sub)) {
                $rename($sub, $value['cat_name']);
            }
        }

        return $this->success($response, $return);
    }

    /**
     * 获取二级目录列表（兼容旧接口 Api/Catalog/secondCatList）。
     */
    public function secondCatList(Request $request, Response $response): Response
    {
        $itemId = $this->getParam($request, 'item_id', 0);

        if ($itemId <= 0) {
            return $this->success($response, []);
        }

        // 获取登录用户（非严格模式，允许游客访问）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10103, '您没有访问权限');
        }

        // 获取二级目录列表
        $ret = \App\Model\Catalog::getListByLevel($itemId, 2);

        return $this->success($response, $ret ?: []);
    }

    /**
     * 获取子目录列表（兼容旧接口 Api/Catalog/childCatList）。
     */
    public function childCatList(Request $request, Response $response): Response
    {
        $catId = $this->getParam($request, 'cat_id', 0);

        if ($catId <= 0) {
            return $this->success($response, []);
        }

        // 获取目录信息
        $cat = \App\Model\Catalog::findById($catId);
        if (!$cat) {
            return $this->success($response, []);
        }

        $itemId = (int) $cat->item_id;

        // 获取登录用户（非严格模式，允许游客访问）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10103, '您没有访问权限');
        }

        // 获取子目录列表
        $ret = \App\Model\Catalog::getChildrenByCatId($itemId, $catId);

        return $this->success($response, $ret ?: []);
    }

    /**
     * 保存目录（兼容旧接口 Api/Catalog/save）。
     *
     * 功能：
     * - 支持新建和更新目录
     * - 权限检查（checkItemEdit）
     * - 目录数量限制检查（最多 1500 个）
     * - 删除菜单缓存
     */
    public function save(Request $request, Response $response): Response
    {
        $catName    = $this->getParam($request, 'cat_name', '');
        $sNumber    = $this->getParam($request, 's_number', 0);
        $catId      = $this->getParam($request, 'cat_id', 0);
        $parentCatId = $this->getParam($request, 'parent_cat_id', 0);
        $itemId     = $this->getParam($request, 'item_id', 0);

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查编辑权限
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10103, '您没有编辑权限');
        }

        // 禁止空目录的生成
        if (empty($catName)) {
            return $this->error($response, 10101, '目录名称不能为空');
        }

        // 检查上级目录不能选择自身
        if ($parentCatId > 0 && $parentCatId == $catId) {
            return $this->error($response, 10101, '上级目录不能选择自身');
        }

        if ($catId > 0) {
            // 更新目录
            $cat = \App\Model\Catalog::findById($catId);
            if (!$cat) {
                return $this->error($response, 10101, '目录不存在');
            }

            $itemId = (int) $cat->item_id;

            if (!$this->checkItemEdit($uid, $itemId)) {
                return $this->error($response, 10103, '您没有编辑权限');
            }

            $return = \App\Model\Catalog::save($catId, $itemId, $catName, $parentCatId, $sNumber);
            if (!$return) {
                return $this->error($response, 10103, '更新失败');
            }

            // 记录变更日志
            \App\Model\ItemChangeLog::addLog($uid, $itemId, 'update', 'catalog', $catId, $catName);
        } else {
            // 新建目录
            // 检查目录数量限制
            $catalogCount = \App\Model\Catalog::getCount($itemId);
            if ($catalogCount >= 1500) {
                return $this->error($response, 10100, '你创建太多目录啦！如有需求请联系网站管理员');
            }

            $return = \App\Model\Catalog::save(0, $itemId, $catName, $parentCatId, $sNumber);
            if (!$return) {
                return $this->error($response, 10103, '创建失败');
            }

            $catId = (int) ($return['cat_id'] ?? 0);

            // 记录变更日志
            \App\Model\ItemChangeLog::addLog($uid, $itemId, 'create', 'catalog', $catId, $catName);
        }

        // 删除菜单缓存
        \App\Model\Item::deleteCache($itemId);

        // 返回 cat_id（兼容历史格式）
        // 兼容性说明（2025-09-09）：
        // - 旧版返回格式：{"error_code": 0, "data": 123}（data 是整数 cat_id）
        // - 标准返回格式：{"error_code": 0, "data": {"cat_id": 123}}（data 是对象，包含 cat_id 字段）
        // - 当前为了保持与 RunApi 客户端兼容，使用旧版格式（RunApi 客户端依赖此格式）
        // - 计划在 2027-09-09 后改为标准返回格式，届时 RunApi 客户端应已全部更新
        // TODO: 2027-09-09 后改为标准返回：return $this->success($response, ['cat_id' => $catId]);
        return $this->json($response, [
            'error_code' => 0,
            'data'       => $catId,
        ]);
    }

    /**
     * 批量更新目录与页面的结构（兼容旧接口 Api/Catalog/batUpdate）。
     *
     * 功能：
     * - 批量更新目录的名称、父目录、层级与排序；
     * - 批量更新页面所在目录与排序；
     * - 记录拖动操作日志（ItemChangeLog::addLog，op_object_type=tree）。
     */
    public function batUpdate(Request $request, Response $response): Response
    {
        $catsJson    = (string) $this->getParam($request, 'cats', '');
        $itemId      = (int) $this->getParam($request, 'item_id', 0);
        $draggedId   = (int) $this->getParam($request, 'dragged_id', 0);
        $draggedTitle = (string) $this->getParam($request, 'dragged_title', '');
        $draggedType = (string) $this->getParam($request, 'dragged_type', '');

        if ($itemId <= 0 || $catsJson === '') {
            return $this->error($response, 10101, '参数错误');
        }

        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }
        $uid = (int) ($loginUser['uid'] ?? 0);

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10103, '您没有编辑权限');
        }

        $dataArray = json_decode(htmlspecialchars_decode($catsJson, ENT_QUOTES), true);
        if (!is_array($dataArray) || empty($dataArray)) {
            return $this->success($response, []);
        }

        foreach ($dataArray as $value) {
            $catId       = (int) ($value['cat_id'] ?? 0);
            $catName     = isset($value['cat_name']) ? (string) $value['cat_name'] : '';
            $parentCatId = (int) ($value['parent_cat_id'] ?? 0);
            $level       = (int) ($value['level'] ?? 2);
            $sNumber     = (int) ($value['s_number'] ?? 0);
            $pageId      = (int) ($value['page_id'] ?? 0);

            if ($catId > 0 && $catName !== '') {
                // 更新目录
                DB::table('catalog')
                    ->where('cat_id', $catId)
                    ->where('item_id', $itemId)
                    ->update([
                        'cat_name'      => $catName,
                        'parent_cat_id' => $parentCatId,
                        'level'         => $level,
                        's_number'      => $sNumber,
                    ]);
            }

            if ($pageId > 0) {
                // 更新页面目录与排序（开源版仅使用主表 page）
                DB::table('page')
                    ->where('page_id', $pageId)
                    ->where('item_id', $itemId)
                    ->update([
                        'cat_id'   => $parentCatId,
                        's_number' => $sNumber,
                    ]);
                \App\Model\Page::deleteCache($pageId);
            }
        }

        // 组装日志信息
        $logMessage = '目录树';
        if ($draggedId > 0 && $draggedTitle !== '') {
            if ($draggedType === 'page') {
                $logMessage = '拖动页面「' . $draggedTitle . '」';
            } elseif ($draggedType === 'catalog') {
                $logMessage = '拖动目录「' . $draggedTitle . '」';
            }
        }

        \App\Model\ItemChangeLog::addLog($uid, $itemId, 'drag', 'tree', 0, $logMessage);

        // 删除菜单缓存
        \App\Model\Item::deleteCache($itemId);

        return $this->success($response, []);
    }

    /**
     * 获取默认目录（兼容旧接口 Api/Catalog/getDefaultCat）。
     *
     * 规则与旧版保持一致：
     * - 如果带 page_id：使用页面或其历史版本所在目录；
     * - 如果是复制页面：使用被复制页面的目录；
     * - 否则：使用当前用户在该项目下最近一次创建/编辑页面所在目录。
     */
    public function getDefaultCat(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }
        $uid = (int) ($loginUser['uid'] ?? 0);

        $pageId        = (int) $this->getParam($request, 'page_id', 0);
        $itemId        = (int) $this->getParam($request, 'item_id', 0);
        $pageHistoryId = (int) $this->getParam($request, 'page_history_id', 0);
        $copyPageId    = (int) $this->getParam($request, 'copy_page_id', 0);

        $defaultCatId = 0;

        if ($pageId > 0) {
            if ($pageHistoryId > 0) {
                $history = \App\Model\PageHistory::findById($pageId, $pageHistoryId);
                if ($history) {
                    $itemId       = (int) ($history['item_id'] ?? $itemId);
                    $defaultCatId = (int) ($history['cat_id'] ?? 0);
                }
            } else {
                $page = \App\Model\Page::findById($pageId);
                if ($page) {
                    $itemId       = (int) ($page['item_id'] ?? $itemId);
                    $defaultCatId = (int) ($page['cat_id'] ?? 0);
                }
            }
        } elseif ($copyPageId > 0) {
            $page = \App\Model\Page::findById($copyPageId);
            if ($page) {
                $itemId       = (int) ($page['item_id'] ?? $itemId);
                $defaultCatId = (int) ($page['cat_id'] ?? 0);
            }
        } else {
            // 查找用户在该项目下最近一次创建/编辑的页面
            if ($itemId > 0) {
                $lastPage = DB::table('page')
                    ->where('author_uid', $uid)
                    ->where('item_id', $itemId)
                    ->orderBy('addtime', 'desc')
                    ->limit(1)
                    ->first();
                if ($lastPage) {
                    $defaultCatId = (int) ($lastPage->cat_id ?? 0);
                }
            }
        }

        // 使用 page / 历史数据中的 item_id 覆盖，以满足权限校验
        $itemId = $itemId > 0 ? $itemId : 0;

        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目不存在');
        }

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10101, '没有编辑权限');
        }

        return $this->success($response, ['default_cat_id' => $defaultCatId]);
    }

    /**
     * 删除目录（兼容旧接口 Api/Catalog/delete）。
     */
    public function delete(Request $request, Response $response): Response
    {
        $catId = (int) $this->getParam($request, 'cat_id', 0);
        if ($catId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        $cat = \App\Model\Catalog::findById($catId);
        if (!$cat) {
            return $this->error($response, 10101, '目录不存在');
        }

        $itemId = (int) ($cat->item_id ?? 0);

        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }
        $uid = (int) ($loginUser['uid'] ?? 0);

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10103, '您没有编辑权限');
        }

        \App\Model\ItemChangeLog::addLog(
            $uid,
            $itemId,
            'delete',
            'catalog',
            $catId,
            (string) ($cat->cat_name ?? '')
        );

        $ret = \App\Model\Catalog::deleteCat($catId);

        if (!$ret) {
            return $this->error($response, 10101, '删除失败');
        }

        return $this->success($response, ['success' => true]);
    }

    /**
     * 按目录获取页面列表（兼容旧接口 Api/Catalog/getPagesBycat）。
     */
    public function getPagesBycat(Request $request, Response $response): Response
    {
        $catId  = (int) $this->getParam($request, 'cat_id', 0);
        $itemId = (int) $this->getParam($request, 'item_id', 0);

        if ($catId <= 0 || $itemId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }
        $uid = (int) ($loginUser['uid'] ?? 0);

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10103, '您没有编辑权限');
        }

        $rows = DB::table('page')
            ->select(['page_id', 'page_title', 's_number'])
            ->where('cat_id', $catId)
            ->where('item_id', $itemId)
            ->where('is_del', 0)
            ->orderBy('s_number', 'asc')
            ->orderBy('page_id', 'asc')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $this->success($response, $result);
    }

    /**
     * 复制或移动目录（兼容旧接口 Api/Catalog/copy）。
     *
     * 说明：
     * - 支持同项目或跨项目复制目录及其子目录和页面；
     * - 当 is_del=1 且复制成功时，会删除原目录（等价移动操作）。
     */
    public function copy(Request $request, Response $response): Response
    {
        $catId      = (int) $this->getParam($request, 'cat_id', 0);
        $newPcatId  = (int) $this->getParam($request, 'new_p_cat_id', 0);
        $toItemId   = (int) $this->getParam($request, 'to_item_id', 0);
        $isDel      = (int) $this->getParam($request, 'is_del', 0);

        if ($catId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }
        $uid = (int) ($loginUser['uid'] ?? 0);

        $srcCat = \App\Model\Catalog::findById($catId);
        if (!$srcCat) {
            return $this->error($response, 10101, '目录不存在');
        }

        $fromItemId = (int) ($srcCat->item_id ?? 0);
        $destItemId = $toItemId > 0 ? $toItemId : $fromItemId;

        // 目标项目编辑权限
        if (!$this->checkItemEdit($uid, $destItemId)) {
            return $this->error($response, 10103, '您没有目标项目的编辑权限');
        }

        // 源项目编辑权限
        if (!$this->checkItemEdit($uid, $fromItemId)) {
            return $this->error($response, 10103, '您没有源项目的编辑权限');
        }

        // 复制目录树
        $idMap = [];
        $res   = $this->copyCatalogTree($uid, $catId, $newPcatId, $fromItemId, $destItemId, $idMap);

        if ($isDel && $res) {
            \App\Model\Catalog::deleteCat($catId);
        }

        // 删除项目缓存（复制后需要刷新菜单）
        if ($res) {
            \App\Model\Item::deleteCache($destItemId);
            // 如果是跨项目复制，也需要删除源项目的缓存
            if ($fromItemId != $destItemId) {
                \App\Model\Item::deleteCache($fromItemId);
            }
        }

        return $this->success($response, $res ?: []);
    }

    /**
     * 递归复制目录树及其页面。
     *
     * @param int   $uid        操作用户 ID
     * @param int   $srcCatId   源目录 ID
     * @param int   $destParentId 目标父目录 ID（在目标项目下）
     * @param int   $fromItemId 源项目 ID
     * @param int   $toItemId   目标项目 ID
     * @param array $idMap      源目录 ID => 新目录 ID 映射
     * @param array $visited    已访问的源目录 ID 列表（用于检测循环引用）
     * @param int   $depth      当前递归深度
     * @return array            返回新创建的根目录信息
     */
    private function copyCatalogTree(
        int $uid,
        int $srcCatId,
        int $destParentId,
        int $fromItemId,
        int $toItemId,
        array &$idMap,
        array $visited = [],
        int $depth = 0
    ): array {
        // 安全检查：防止无限递归
        $maxDepth = 100; // 最大层级深度
        if ($depth >= $maxDepth) {
            // 超过最大深度，记录日志并停止递归
            \App\Common\Helper\LogHelper::warning(
                "Catalog copy exceeded max depth {$maxDepth} at cat_id {$srcCatId}, item_id: {$fromItemId} -> {$toItemId}",
                'Catalog'
            );
            return [];
        }

        // 循环引用检测：防止复制循环引用的目录结构
        if (in_array($srcCatId, $visited)) {
            // 检测到循环引用，记录日志并跳过
            \App\Common\Helper\LogHelper::warning(
                "Circular reference detected in catalog copy: cat_id {$srcCatId}, item_id: {$fromItemId} -> {$toItemId}, path: " . implode(' -> ', $visited),
                'Catalog'
            );
            return [];
        }
        $visited[] = $srcCatId;
        $srcCat = \App\Model\Catalog::findById($srcCatId);
        if (!$srcCat) {
            return [];
        }

        // 在目标项目下创建新目录
        $newCat = \App\Model\Catalog::save(
            0,
            $toItemId,
            (string) $srcCat->cat_name,
            $destParentId,
            (int) ($srcCat->s_number ?? 0)
        );

        if (!$newCat || empty($newCat['cat_id'])) {
            return [];
        }

        $newCatId         = (int) $newCat['cat_id'];
        $idMap[$srcCatId] = $newCatId;

        // 复制该目录下的页面
        $pages     = DB::table('page')
            ->where('item_id', $fromItemId)
            ->where('cat_id', $srcCatId)
            ->where('is_del', 0)
            ->orderBy('s_number', 'asc')
            ->orderBy('page_id', 'asc')
            ->get()
            ->all();

        $username = '';
        $user     = \App\Model\User::findById($uid);
        if ($user) {
            $username = (string) ($user->username ?? '');
        }

        foreach ($pages as $pageRow) {
            $page = (array) $pageRow;

            // 解压原内容
            $content = (string) ($page['page_content'] ?? '');
            $decoded = \App\Common\Helper\ContentCodec::decompress($content);
            if ($decoded !== '') {
                $content = $decoded;
            }

            $data = [
                'author_uid'      => $uid,
                'author_username' => $username,
                'item_id'         => $toItemId,
                'cat_id'          => $newCatId,
                'page_title'      => $page['page_title'] ?? '',
                'page_content'    => $content,
                'page_comments'   => $page['page_comments'] ?? '',
                's_number'        => (int) ($page['s_number'] ?? 0),
                'ext_info'        => $page['ext_info'] ?? '',
                'addtime'         => time(),
            ];

            \App\Model\Page::addPage($toItemId, $data);
        }

        // 复制子目录
        $subCats = DB::table('catalog')
            ->where('item_id', $fromItemId)
            ->where('parent_cat_id', $srcCatId)
            ->get()
            ->all();

        foreach ($subCats as $sub) {
            $this->copyCatalogTree(
                $uid,
                (int) $sub->cat_id,
                $newCatId,
                $fromItemId,
                $toItemId,
                $idMap
            );
        }

        return $newCat;
    }
}
