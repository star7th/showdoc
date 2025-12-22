<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;
use App\Common\Cache\CacheManager;
use App\Model\Page;

class Item
{
    public static function findById(int $itemId): ?object
    {
        if ($itemId <= 0) {
            return null;
        }

        return DB::table('item')
            ->where('item_id', $itemId)
            ->where('is_del', 0)
            ->first();
    }

    public static function findByDomain(string $domain): ?object
    {
        $domain = trim($domain);
        if ($domain === '') {
            return null;
        }

        return DB::table('item')
            ->where('item_domain', $domain)
            ->where('is_del', 0)
            ->first();
    }

    public static function isWhitelisted(int $itemId): bool
    {
        if ($itemId <= 0) {
            return false;
        }

        $row = DB::table('item_whitelist')
            ->where('item_id', $itemId)
            ->first();

        return $row !== null;
    }

    /**
     * 获取菜单结构（不含缓存）
     *
     * @param int $itemId 项目 ID
     * @return array 菜单结构：['pages' => [...], 'catalogs' => [...]]
     */
    public static function getMenu(int $itemId): array
    {
        if ($itemId <= 0) {
            return ['pages' => [], 'catalogs' => []];
        }

        // 获取所有页面（cat_id=0 的为根目录页面）
        // 开源版：使用单表 page，不支持分表
        $table = 'page';
        $allPages = DB::table($table)
            ->select(['page_id', 'author_uid', 'cat_id', 'page_title', 'addtime', 'ext_info'])
            ->where('item_id', $itemId)
            ->where('is_del', 0)
            ->orderBy('s_number', 'asc')
            ->orderBy('page_id', 'asc')
            ->get()
            ->all();

        $pages = [];
        foreach ($allPages as $page) {
            if (empty($page->cat_id)) {
                $pages[] = (array) $page;
            }
        }

        // 获取所有目录
        $allCatalogs = DB::table('catalog')
            ->where('item_id', $itemId)
            ->orderBy('s_number', 'asc')
            ->orderBy('cat_id', 'asc')
            ->get()
            ->all();

        // 获取所有二级目录
        $catalogs = [];
        foreach ($allCatalogs as $catalog) {
            if ((int) $catalog->level === 2) {
                $catalogs[] = self::buildCatalogTree((array) $catalog, $allPages, $allCatalogs);
            }
        }

        return [
            'pages'    => $pages,
            'catalogs' => $catalogs,
        ];
    }

    /**
     * 递归构建目录树
     *
     * @param array $catalogData 目录数据
     * @param array $allPages 所有页面
     * @param array $allCatalogs 所有目录
     * @return array 构建好的目录树
     */
    private static function buildCatalogTree(array $catalogData, array $allPages, array $allCatalogs): array
    {
        $catId = (int) $catalogData['cat_id'];

        // 获取该目录下的页面
        $catalogData['pages'] = [];
        foreach ($allPages as $page) {
            if ((int) $page->cat_id === $catId) {
                $catalogData['pages'][] = (array) $page;
            }
        }

        // 获取该目录下的子目录
        $catalogData['catalogs'] = [];
        foreach ($allCatalogs as $catalog) {
            if ((int) $catalog->parent_cat_id === $catId) {
                $catalogData['catalogs'][] = self::buildCatalogTree((array) $catalog, $allPages, $allCatalogs);
            }
        }

        return $catalogData;
    }

    /**
     * 获取带缓存的菜单结构
     *
     * @param int $itemId 项目 ID
     * @return array 菜单结构
     */
    public static function getMenuByCache(int $itemId): array
    {
        if ($itemId <= 0) {
            return ['pages' => [], 'catalogs' => []];
        }

        $cacheKey = 'showdoc_menu_cache_item_id_' . $itemId;
        $cache    = CacheManager::getInstance();
        $menu     = $cache->get($cacheKey);

        if ($menu === null) {
            // 缓存不存在，从数据库读取
            $menu = self::getMenu($itemId);
            // 写入缓存，24小时过期
            $cache->set($cacheKey, $menu, 60 * 60 * 24);
        }

        return $menu;
    }

    /**
     * 根据用户目录权限过滤菜单
     *
     * @param int $uid 用户 ID
     * @param int $itemId 项目 ID
     * @param array $menu 菜单结构
     * @return array 过滤后的菜单结构
     */
    public static function filterMemberItem(int $uid, int $itemId, array $menu): array
    {
        if ($uid <= 0 || $itemId <= 0 || empty($menu)) {
            return $menu;
        }

        // 获取用户在该项目下拥有权限的目录 ID 集合
        $catIds = self::getUserCatIds($itemId, $uid);
        if (empty($catIds)) {
            // 没有目录权限限制，返回原菜单
            return $menu;
        }

        $allowedCatIds = array_flip(array_map('intval', $catIds));

        // 过滤二级目录
        if (!empty($menu['catalogs'])) {
            $filteredCatalogs = [];
            foreach ($menu['catalogs'] as $catalog) {
                $catId = (int) ($catalog['cat_id'] ?? 0);
                if (isset($allowedCatIds[$catId])) {
                    $filteredCatalogs[] = $catalog;
                }
            }
            $menu['catalogs'] = $filteredCatalogs;
        }

        return $menu;
    }

    /**
     * 获取用户在项目下拥有权限的目录 ID 集合
     *
     * @param int $itemId 项目 ID
     * @param int $uid 用户 ID
     * @return array 目录 ID 数组
     */
    private static function getUserCatIds(int $itemId, int $uid): array
    {
        // 直接复用 Member::getCatIds 的旧版逻辑实现
        return Member::getCatIds($itemId, $uid);
    }

    /**
     * 删除菜单缓存
     *
     * @param int $itemId 项目 ID
     * @return bool 是否删除成功
     */
    public static function deleteCache(int $itemId): bool
    {
        if ($itemId <= 0) {
            return false;
        }

        $cacheKey = 'showdoc_menu_cache_item_id_' . $itemId;
        $cache    = CacheManager::getInstance();
        return $cache->delete($cacheKey);
    }

    /**
     * 更新菜单缓存中的页面标题
     *
     * @param int $itemId 项目 ID
     * @param int $pageId 页面 ID
     * @param string $pageTitle 新标题
     * @return bool 是否更新成功
     */
    public static function updateMenuCachePage(int $itemId, int $pageId, string $pageTitle): bool
    {
        if ($itemId <= 0 || $pageId <= 0) {
            return false;
        }

        $cacheKey = 'showdoc_menu_cache_item_id_' . $itemId;
        $cache    = CacheManager::getInstance();
        $menu     = $cache->get($cacheKey);

        if ($menu === null) {
            // 缓存不存在，无需更新
            return true;
        }

        // 递归更新页面标题
        $updated = self::updatePageTitleInMenu($menu, $pageId, $pageTitle);

        if ($updated) {
            // 重新写入缓存
            $cache->set($cacheKey, $menu, 60 * 60 * 24);
        }

        return $updated;
    }

    /**
     * 在菜单结构中递归更新页面标题
     *
     * @param array &$menu 菜单结构（引用传递）
     * @param int $pageId 页面 ID
     * @param string $pageTitle 新标题
     * @return bool 是否找到并更新
     */
    private static function updatePageTitleInMenu(array &$menu, int $pageId, string $pageTitle): bool
    {
        $found = false;

        // 更新根目录下的页面
        if (!empty($menu['pages'])) {
            foreach ($menu['pages'] as &$page) {
                if ((int) ($page['page_id'] ?? 0) === $pageId) {
                    $page['page_title'] = $pageTitle;
                    $found              = true;
                }
            }
            unset($page);
        }

        // 递归更新目录下的页面
        if (!empty($menu['catalogs'])) {
            foreach ($menu['catalogs'] as &$catalog) {
                if (self::updatePageTitleInCatalog($catalog, $pageId, $pageTitle)) {
                    $found = true;
                }
            }
            unset($catalog);
        }

        return $found;
    }

    /**
     * 在目录中递归更新页面标题
     *
     * @param array &$catalog 目录数据（引用传递）
     * @param int $pageId 页面 ID
     * @param string $pageTitle 新标题
     * @return bool 是否找到并更新
     */
    private static function updatePageTitleInCatalog(array &$catalog, int $pageId, string $pageTitle): bool
    {
        $found = false;

        // 更新当前目录下的页面
        if (!empty($catalog['pages'])) {
            foreach ($catalog['pages'] as &$page) {
                if ((int) ($page['page_id'] ?? 0) === $pageId) {
                    $page['page_title'] = $pageTitle;
                    $found              = true;
                }
            }
            unset($page);
        }

        // 递归更新子目录
        if (!empty($catalog['catalogs'])) {
            foreach ($catalog['catalogs'] as &$subCatalog) {
                if (self::updatePageTitleInCatalog($subCatalog, $pageId, $pageTitle)) {
                    $found = true;
                }
            }
            unset($subCatalog);
        }

        return $found;
    }

    /**
     * 导出项目数据为 JSON
     *
     * @param int $itemId 项目 ID
     * @param bool $uncompress 是否解压内容
     * @return string JSON 字符串
     */
    public static function export(int $itemId, bool $uncompress = false): string
    {
        if ($itemId <= 0) {
            return json_encode([]);
        }

        $item = self::findById($itemId);
        if (!$item) {
            return json_encode([]);
        }

        $itemData = [
            'item_type'        => $item->item_type ?? 1,
            'item_name'        => $item->item_name ?? '',
            'item_description' => $item->item_description ?? '',
            'password'         => $item->password ?? '',
        ];

        // 获取菜单结构（包含页面内容）
        $menu = self::getContent($itemId, $uncompress);
        $itemData['pages'] = $menu;

        // 获取项目成员
        $members = ItemMember::getList($itemId);
        $itemData['members'] = array_map(function ($member) {
            return [
                // member_group_id 需要返回字符串数字，兼容旧前端
                'member_group_id' => (string) ($member['member_group_id'] ?? '0'),
                'uid'             => (int) ($member['uid'] ?? 0),
                'username'        => $member['username'] ?? '',
            ];
        }, $members);

        return json_encode($itemData, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取项目内容（菜单结构）
     *
     * @param int $itemId 项目 ID
     * @param bool $uncompress 是否解压内容
     * @return array 菜单结构
     */
    public static function getContent(int $itemId, bool $uncompress = false): array
    {
        if ($itemId <= 0) {
            return ['pages' => [], 'catalogs' => []];
        }

        // 开源版：使用单表 page，不支持分表
        $table = 'page';
        $pageField = 'page_id,page_title,cat_id,page_content,s_number,page_comments';
        $catalogField = 'cat_id,cat_name,parent_cat_id,level,s_number';

        // 获取所有页面
        $allPages = DB::table($table)
            ->select(explode(',', $pageField))
            ->where('item_id', $itemId)
            ->where('is_del', 0)
            ->orderBy('s_number', 'asc')
            ->orderBy('page_id', 'asc')
            ->get()
            ->all();

        $pages = [];
        foreach ($allPages as $page) {
            $pageData = (array) $page;
            // 开源版：page_content 不压缩存储，但兼容旧压缩数据
            if ($uncompress && !empty($pageData['page_content'])) {
                $decoded = \App\Common\Helper\ContentCodec::decompress($pageData['page_content']);
                if ($decoded !== '' && $decoded !== $pageData['page_content']) {
                    // 是压缩数据，解压后返回
                    $pageData['page_content'] = $decoded;
                }
                // 未压缩数据，直接返回
            }
            if (empty($pageData['cat_id'])) {
                $pages[] = $pageData;
            }
        }

        // 获取所有目录
        $allCatalogs = DB::table('catalog')
            ->select(explode(',', $catalogField))
            ->where('item_id', $itemId)
            ->orderBy('s_number', 'asc')
            ->orderBy('cat_id', 'asc')
            ->get()
            ->all();

        // 获取所有二级目录
        $catalogs = [];
        foreach ($allCatalogs as $catalog) {
            if ((int) ($catalog->level ?? 0) === 2) {
                $catalogData = (array) $catalog;
                $catalogs[] = self::buildCatalogTreeForExport($catalogData, $allPages, $allCatalogs, $uncompress);
            }
        }

        return [
            'pages'    => $pages,
            'catalogs' => $catalogs,
        ];
    }

    /**
     * 构建目录树（用于导出）
     *
     * @param array $catalogData 目录数据
     * @param array $allPages 所有页面
     * @param array $allCatalogs 所有目录
     * @param bool $uncompress 是否解压内容
     * @return array 构建好的目录树
     */
    private static function buildCatalogTreeForExport(array $catalogData, array $allPages, array $allCatalogs, bool $uncompress): array
    {
        $catId = (int) $catalogData['cat_id'];

        // 获取该目录下的页面
        $catalogData['pages'] = [];
        foreach ($allPages as $page) {
            if ((int) ($page->cat_id ?? 0) === $catId) {
                $pageData = (array) $page;
                // 开源版：page_content 不压缩存储，但兼容旧压缩数据
                if ($uncompress && !empty($pageData['page_content'])) {
                    $decoded = \App\Common\Helper\ContentCodec::decompress($pageData['page_content']);
                    if ($decoded !== '' && $decoded !== $pageData['page_content']) {
                        // 是压缩数据，解压后返回
                        $pageData['page_content'] = $decoded;
                    }
                    // 未压缩数据，直接返回
                }
                $catalogData['pages'][] = $pageData;
            }
        }

        // 获取该目录下的子目录
        $catalogData['catalogs'] = [];
        foreach ($allCatalogs as $catalog) {
            if ((int) ($catalog->parent_cat_id ?? 0) === $catId) {
                $subCatalogData = (array) $catalog;
                $catalogData['catalogs'][] = self::buildCatalogTreeForExport($subCatalogData, $allPages, $allCatalogs, $uncompress);
            }
        }

        return $catalogData;
    }

    /**
     * 导入项目数据
     *
     * @param string $json JSON 字符串
     * @param int $uid 用户 ID
     * @param int $itemId 项目 ID（0 表示新建项目）
     * @param string $itemName 项目名称（可选）
     * @param string $itemDescription 项目描述（可选）
     * @param string $itemPassword 项目密码（可选）
     * @param string $itemDomain 项目域名（可选）
     * @return int|false 返回项目 ID，失败返回 false
     */
    public static function import(
        string $json,
        int $uid,
        int $itemId = 0,
        string $itemName = '',
        string $itemDescription = '',
        string $itemPassword = '',
        string $itemDomain = ''
    ) {
        $user = User::findById($uid);
        if (!$user) {
            return false;
        }

        $item = json_decode($json, true);
        if (!$item) {
            return false;
        }

        // 如果存在 item_id，那就是项目内导入
        if ($itemId > 0) {
            // 项目内导入，不需要创建新项目
        } else {
            // 新建项目
            if (!empty($item['item_domain'])) {
                // 检查个性域名是否已存在
                $existing = self::findByDomain($item['item_domain']);
                if ($existing) {
                    return false; // 个性域名已经存在
                }
                if (!ctype_alnum($itemDomain) || is_numeric($itemDomain)) {
                    return false; // 个性域名只能是字母或数字的组合
                }
            } else {
                $item['item_domain'] = '';
            }

            $itemData = [
                'item_name'        => $itemName ?: htmlspecialchars(htmlspecialchars_decode($item['item_name'] ?? '')),
                'item_domain'      => $itemDomain ?: htmlspecialchars(htmlspecialchars_decode($item['item_domain'] ?? '')),
                'item_type'        => (int) ($item['item_type'] ?? 1),
                'item_description' => $itemDescription ?: htmlspecialchars(htmlspecialchars_decode($item['item_description'] ?? '')),
                'password'         => $itemPassword ?: htmlspecialchars(htmlspecialchars_decode($item['password'] ?? '')),
                'uid'              => $uid,
                'username'          => $user->username ?? '',
                'addtime'           => time(),
            ];

            // 创建新项目（需要实现 add 方法）
            $itemId = self::add($itemData);
            if ($itemId <= 0) {
                return false;
            }
        }

        // 导入页面
        if (!empty($item['pages'])) {
            // 父页面们（一级目录）
            if (!empty($item['pages']['pages'])) {
                foreach ($item['pages']['pages'] as $value) {
                    // 与旧版逻辑一致：使用 _htmlspecialchars 处理
                    // 之所以先 htmlspecialchars_decode 是为了防止被 htmlspecialchars 转义了两次
                    $pageData = [
                        'author_uid'      => $uid,
                        'author_username' => $user->username ?? '',
                        'page_title'       => htmlspecialchars(htmlspecialchars_decode($value['page_title'] ?? '')),
                        'page_content'    => htmlspecialchars(htmlspecialchars_decode($value['page_content'] ?? '')),
                        's_number'         => (int) ($value['s_number'] ?? 99),
                        'page_comments'    => htmlspecialchars(htmlspecialchars_decode($value['page_comments'] ?? '')),
                    ];
                    Page::addPage($itemId, $pageData);
                }
            }

            // 二级目录及以下
            if (!empty($item['pages']['catalogs'])) {
                $catPathPages = self::toItemPageCatPath($item['pages']['catalogs']);
                foreach ($catPathPages as $value) {
                    Page::updateByTitle(
                        $itemId,
                        $value['page_title'] ?? '',
                        $value['page_content'] ?? '',
                        $value['cat_path'] ?? '',
                        (int) ($value['s_number'] ?? 99),
                        $uid,
                        $user->username ?? ''
                    );
                }
            }
        }

        return $itemId;
    }

    /**
     * 把目录嵌套的项目页面数据平摊为目录路径形式
     *
     * @param array $catalogs 目录数组
     * @param string $parentCatName 父目录路径
     * @return array 页面数组（包含 cat_path）
     */
    public static function toItemPageCatPath(array $catalogs, string $parentCatName = ''): array
    {
        if (empty($catalogs)) {
            return [];
        }

        $returnArray = [];

        foreach ($catalogs as $value) {
            $catName = $value['cat_name'] ?? '';
            if (empty($catName)) {
                continue;
            }

            if ($parentCatName) {
                $catPath = $parentCatName . '/' . $catName;
            } else {
                $catPath = $catName;
            }

            // 该目录下的页面们
            if (!empty($value['pages'])) {
                foreach ($value['pages'] as $page) {
                    $page['cat_path'] = $catPath;
                    unset($page['cat_name'], $page['level']);
                    $returnArray[] = $page;
                }
            }

            // 该目录的子目录
            if (!empty($value['catalogs'])) {
                $subArray = self::toItemPageCatPath($value['catalogs'], $catPath);
                $returnArray = array_merge($returnArray, $subArray);
            }
        }

        return $returnArray;
    }

    /**
     * 添加项目（新建）
     *
     * @param array $data 项目数据（如果包含 item_id，则使用该值；否则自动生成）
     * @return int 新创建的项目 ID，失败返回 0
     */
    public static function add(array $data): int
    {
        if (empty($data['uid']) || empty($data['item_name'])) {
            return 0;
        }

        try {
            // 如果已经指定了 item_id，直接插入；否则使用 insertGetId 自动生成
            if (!empty($data['item_id'])) {
                DB::table('item')->insert($data);
                return (int) $data['item_id'];
            } else {
                $itemId = DB::table('item')->insertGetId($data);
                return (int) $itemId;
            }
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 获取已删除的项目（超过指定天数）
     *
     * @param int $days 天数
     * @param int $limit 限制数量
     * @return array 项目列表
     */
    public static function getDeletedItems(int $days, int $limit = 1000): array
    {
        if ($days <= 0) {
            return [];
        }

        $time = time() - ($days * 24 * 60 * 60);
        $rows = DB::table('item')
            ->where('is_del', 1)
            ->where('last_update_time', '<', $time)
            ->limit($limit)
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    /**
     * 删除单个项目（物理删除，兼容旧版 delete_item 方法）
     * 
     * 旧版实现：
     * - 删除 page 表数据
     * - 删除 catalog 表数据
     * - 删除 page_history 表数据
     * - 删除 item_member 表数据
     * - 删除 team_item 表数据
     * - 删除 team_item_member 表数据
     * - 最后删除 item 表数据
     *
     * @param int $itemId 项目 ID
     * @return bool 是否成功
     */
    public static function deleteItem(int $itemId): bool
    {
        if ($itemId <= 0) {
            return false;
        }

        try {
            DB::beginTransaction();

            // 1. 删除 page 表数据（开源版：单表，无分表）
            DB::table('page')
                ->where('item_id', $itemId)
                ->delete();

            // 2. 删除 catalog 表数据
            DB::table('catalog')
                ->where('item_id', $itemId)
                ->delete();

            // 3. 删除 page_history 表数据（开源版：单表，无分表）
            DB::table('page_history')
                ->where('item_id', $itemId)
                ->delete();

            // 4. 删除 item_member 表数据
            DB::table('item_member')
                ->where('item_id', $itemId)
                ->delete();

            // 5. 删除 team_item 表数据
            DB::table('team_item')
                ->where('item_id', $itemId)
                ->delete();

            // 6. 删除 team_item_member 表数据
            DB::table('team_item_member')
                ->where('item_id', $itemId)
                ->delete();

            // 7. 最后删除 item 表数据
            $affected = DB::table('item')
                ->where('item_id', $itemId)
                ->delete();

            DB::commit();

            // 清理缓存
            self::deleteCache($itemId);

            return $affected > 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * 删除项目（物理删除）
     *
     * @param string $itemIdStr 项目 ID 字符串（逗号分隔）
     * @return bool 是否成功
     */
    public static function deleteItems(string $itemIdStr): bool
    {
        if (empty($itemIdStr)) {
            return false;
        }

        $itemIds = array_filter(array_map('intval', explode(',', $itemIdStr)));
        if (empty($itemIds)) {
            return false;
        }

        try {
            DB::beginTransaction();

            // 1. 删除 page 表数据（开源版：单表，无分表）
            DB::table('page')
                ->whereIn('item_id', $itemIds)
                ->delete();

            // 2. 删除 catalog 表数据
            DB::table('catalog')
                ->whereIn('item_id', $itemIds)
                ->delete();

            // 3. 删除 page_history 表数据（开源版：单表，无分表）
            DB::table('page_history')
                ->whereIn('item_id', $itemIds)
                ->delete();

            // 4. 删除 item_change_log 表数据（开源版：单表，无分表）
            DB::table('item_change_log')
                ->whereIn('item_id', $itemIds)
                ->delete();

            // 6. 删除 item_member 表数据
            DB::table('item_member')
                ->whereIn('item_id', $itemIds)
                ->delete();

            // 7. 删除 team_item 表数据
            DB::table('team_item')
                ->whereIn('item_id', $itemIds)
                ->delete();

            // 8. 删除 team_item_member 表数据
            DB::table('team_item_member')
                ->whereIn('item_id', $itemIds)
                ->delete();

            // 9. 最后删除 item 表数据
            $affected = DB::table('item')
                ->whereIn('item_id', $itemIds)
                ->delete();

            DB::commit();

            // 清理缓存
            foreach ($itemIds as $itemId) {
                self::deleteCache($itemId);
            }

            return $affected > 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            return false;
        }
    }

    /**
     * 软删除项目
     *
     * @param int $itemId 项目 ID
     * @return bool 是否成功
     */
    public static function softDeleteItem(int $itemId): bool
    {
        if ($itemId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('item')
                ->where('item_id', $itemId)
                ->update(['is_del' => 1]);
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取不活跃账号的示例项目
     *
     * @param array $uidArray 用户 ID 数组
     * @return array 项目列表
     */
    public static function getInactiveDemoItems(array $uidArray): array
    {
        if (empty($uidArray)) {
            return [];
        }

        $demoNames = ['技术团队文档示例', '数据字典示例', 'API文档示例', '表格示例', 'runapi默认项目'];
        $rows = DB::table('item')
            ->where('last_update_time', 0)
            ->whereIn('uid', $uidArray)
            ->whereIn('item_name', $demoNames)
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    /**
     * 创建一个新的可用的 item_id（兼容旧版 get_a_new_item_id）
     *
     * @return int 新的 item_id
     */
    public static function getANewItemId(): int
    {
        $row = DB::table('item')
            ->orderBy('item_id', 'desc')
            ->select('item_id')
            ->first();

        $maxItemId = $row ? (int) $row->item_id : 0;
        $newItemId = $maxItemId + rand(10000, 1000000);

        return $newItemId;
    }

    /**
     * 复制项目（兼容旧版 copy 方法）
     *
     * @param int $itemId 源项目 ID
     * @param int $uid 新项目创建者 ID
     * @param string $itemName 新项目名称（可选）
     * @param string $itemDescription 新项目描述（可选）
     * @param string $itemPassword 新项目密码（可选）
     * @param string $itemDomain 新项目域名（可选）
     * @return int|false 新项目 ID，失败返回 false
     */
    public static function copy(int $itemId, int $uid, string $itemName = '', string $itemDescription = '', string $itemPassword = '', string $itemDomain = '')
    {
        // 导出源项目数据
        $json = self::export($itemId, true); // true 表示解压内容

        if (empty($json)) {
            return false;
        }

        // 导入为新项目
        return self::import($json, $uid, 0, $itemName, $itemDescription, $itemPassword, $itemDomain);
    }
}
