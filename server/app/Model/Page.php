<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * 页面模型（开源版：使用单表 page，不支持分表）
 * 
 * 注意：开源版的 page_content 不经过压缩存储，与主版不同。
 * 读取时兼容旧数据（可能有些是压缩的），但写入时不压缩。
 */
class Page
{
    public static function listTitles(int $itemId): array
    {
        if ($itemId <= 0) {
            return [];
        }

        return DB::table('page')
            ->select(['page_id', 'page_title'])
            ->where('item_id', $itemId)
            ->where('is_del', 0)
            ->orderBy('s_number')
            ->orderBy('page_id')
            ->get()
            ->all();
    }

    public static function findByIdWithContent(int $itemId, int $pageId): ?array
    {
        if ($itemId <= 0 || $pageId <= 0) {
            return null;
        }

        $row = DB::table('page')
            ->where('item_id', $itemId)
            ->where('page_id', $pageId)
            ->where('is_del', 0)
            ->first();

        if (!$row) {
            return null;
        }

        $data = (array) $row;

        // 开源版：page_content 不压缩存储，直接返回
        // 为了兼容旧数据（可能有些是压缩的），尝试解压，但失败时保持原样
        if (!empty($data['page_content'])) {
            $decoded = \App\Common\Helper\ContentCodec::decompress($data['page_content']);
            // 如果解压成功（返回非空且不等于原始字符串），说明是旧压缩数据，使用解压后的内容
            if ($decoded !== '' && $decoded !== $data['page_content']) {
                $data['page_content'] = $decoded;
            }
            // 如果解压失败或内容未压缩，保持原样（开源版新数据不压缩）
        }

        return $data;
    }

    /**
     * 搜索项目下的页面
     *
     * @param int $itemId 项目 ID
     * @param array|int $catIds 目录 ID 或目录 ID 数组（0 表示所有目录）
     * @param string $keyword 搜索关键词
     * @return array 匹配的页面数组
     */
    public static function search(int $itemId, $catIds = 0, string $keyword = ''): array
    {
        if ($itemId <= 0 || empty($keyword)) {
            return [];
        }

        $query = DB::table('page')
            ->where('item_id', $itemId)
            ->where('is_del', 0);

        // 目录筛选
        if (is_array($catIds) && !empty($catIds)) {
            $catIds = array_filter(array_map('intval', $catIds));
            if ($catIds) {
                $query->whereIn('cat_id', $catIds);
            }
        } elseif (is_numeric($catIds) && $catIds > 0) {
            $query->where('cat_id', (int) $catIds);
        }

        $pages = $query
            ->orderBy('s_number', 'asc')
            ->get()
            ->all();

        $keyword = strtolower(trim($keyword));
        $result  = [];

        foreach ($pages as $page) {
            $pageTitle   = strtolower((string) ($page->page_title ?? ''));
            $pageContent = (string) ($page->page_content ?? '');

            // 开源版：page_content 不压缩存储，但兼容旧压缩数据
            $decoded = \App\Common\Helper\ContentCodec::decompress($pageContent);
            if ($decoded !== '' && $decoded !== $pageContent) {
                $pageContent = $decoded;
            }

            $pageContentLower = strtolower($pageContent);

            // 在标题或内容中搜索关键词
            if (strpos($pageTitle . '  ' . $pageContentLower, $keyword) !== false) {
                $data = (array) $page;
                $data['page_content'] = $pageContent;
                $result[] = $data;
            }
        }

        return $result;
    }

    /**
     * 根据 page_id 查找页面
     *
     * @param int $pageId 页面 ID
     * @return array|null 页面数据
     */
    public static function findById(int $pageId): ?array
    {
        if ($pageId <= 0) {
            return null;
        }

        // 从 page 表获取 item_id
        $pageRow = DB::table('page')
            ->where('page_id', $pageId)
            ->first();

        if (!$pageRow) {
            return null;
        }

        $itemId = (int) $pageRow->item_id;
        return self::findByIdWithContent($itemId, $pageId);
    }

    /**
     * 获取带缓存的页面内容
     *
     * @param int $pageId 页面 ID
     * @param int $itemId 项目 ID
     * @return array|null 页面数据
     */
    public static function findPageByCache(int $pageId, int $itemId): ?array
    {
        if ($pageId <= 0 || $itemId <= 0) {
            return null;
        }

        $cacheKey = 'showdoc_page_cache2_page_id_' . $pageId;
        $cache    = \App\Common\Cache\CacheManager::getInstance();
        $page     = $cache->get($cacheKey);

        if ($page === null) {
            // 缓存不存在，从数据库读取
            $page = self::findByIdWithContent($itemId, $pageId);
            if ($page) {
                // 写入缓存，24小时过期
                $cache->set($cacheKey, $page, 60 * 60 * 24);
            }
        }

        return $page;
    }

    /**
     * 删除页面缓存
     *
     * @param int $pageId 页面 ID
     * @return bool 是否删除成功
     */
    public static function deleteCache(int $pageId): bool
    {
        if ($pageId <= 0) {
            return false;
        }

        $cacheKey = 'showdoc_page_cache2_page_id_' . $pageId;
        $cache    = \App\Common\Cache\CacheManager::getInstance();
        return $cache->delete($cacheKey);
    }

    /**
     * 保存页面（更新）
     *
     * @param int $pageId 页面 ID
     * @param int $itemId 项目 ID
     * @param array $data 页面数据
     * @return bool 是否成功
     */
    public static function savePage(int $pageId, int $itemId, array $data): bool
    {
        if ($pageId <= 0 || $itemId <= 0) {
            return false;
        }

        // 开源版：page_content 不压缩存储，直接保存
        // 如果传入的是压缩数据（旧数据），先解压再保存
        if (!empty($data['page_content'])) {
            $decoded = \App\Common\Helper\ContentCodec::decompress($data['page_content']);
            if ($decoded !== '' && $decoded !== $data['page_content']) {
                // 是压缩数据，解压后保存
                $data['page_content'] = $decoded;
            }
            // 未压缩数据，直接保存
        }

        try {
            $affected = DB::table('page')
                ->where('page_id', $pageId)
                ->where('item_id', $itemId)
                ->update($data);
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 添加页面（新建）
     *
     * @param int $itemId 项目 ID
     * @param array $data 页面数据
     * @return int 新创建的页面 ID，失败返回 0
     */
    public static function addPage(int $itemId, array $data): int
    {
        if ($itemId <= 0) {
            return 0;
        }

        // 设置 page_id（开源版直接使用自增 ID）
        $data['item_id'] = $itemId;

        // 开源版：page_content 不压缩存储，直接保存
        // 如果传入的是压缩数据（旧数据），先解压再保存
        if (!empty($data['page_content'])) {
            $decoded = \App\Common\Helper\ContentCodec::decompress($data['page_content']);
            if ($decoded !== '' && $decoded !== $data['page_content']) {
                // 是压缩数据，解压后保存
                $data['page_content'] = $decoded;
            }
            // 未压缩数据，直接保存
        }

        try {
            $pageId = DB::table('page')
                ->insertGetId($data);
            return (int) $pageId;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 根据页面标题更新或创建页面（用于导入）
     *
     * @param int $itemId 项目 ID
     * @param string $pageTitle 页面标题
     * @param string $pageContent 页面内容
     * @param string $catName 目录路径（如 '二级目录/三级目录'）
     * @param int $sNumber 排序号
     * @param int $authorUid 作者用户 ID
     * @param string $authorUsername 作者用户名
     * @return int|false 返回页面 ID，失败返回 false
     */
    public static function updateByTitle(
        int $itemId,
        string $pageTitle,
        string $pageContent,
        string $catName = '',
        int $sNumber = 99,
        int $authorUid = 0,
        string $authorUsername = ''
    ) {
        if ($itemId <= 0 || empty($pageTitle)) {
            return false;
        }

        $catId = 0;
        if (!empty($catName)) {
            // 根据路径创建或获取目录
            $catId = \App\Model\Catalog::saveCatPath($catName, $itemId);
            if ($catId === false) {
                return false;
            }
        }

        // 查找是否已存在该页面
        $existing = DB::table('page')
            ->where('item_id', $itemId)
            ->where('is_del', 0)
            ->where('cat_id', $catId)
            ->where('page_title', $pageTitle)
            ->first();

        // HTML 转义处理（与旧版逻辑一致：_htmlspecialchars）
        // 之所以先 htmlspecialchars_decode 是为了防止被 htmlspecialchars 转义了两次
        $pageTitle = htmlspecialchars(htmlspecialchars_decode($pageTitle));

        // 开源版：page_content 不压缩存储
        // 如果传入的是压缩数据（旧数据），先解压（兼容旧数据）
        if (!empty($pageContent)) {
            $decoded = \App\Common\Helper\ContentCodec::decompress($pageContent);
            if ($decoded !== '' && $decoded !== $pageContent) {
                $pageContent = $decoded;
            }
        }

        // 与旧版逻辑一致：使用 _htmlspecialchars 处理
        // 之所以先 htmlspecialchars_decode 是为了防止被 htmlspecialchars 转义了两次
        $pageContent = htmlspecialchars(htmlspecialchars_decode($pageContent));

        if ($existing) {
            // 更新现有页面
            $pageId = (int) $existing->page_id;
            // 开源版：直接保存，不压缩
            $updateData = [
                'author_uid'      => $authorUid,
                'author_username'  => $authorUsername,
                'item_id'          => $itemId,
                'cat_id'           => $catId,
                'page_title'       => $pageTitle,
                'page_content'     => $pageContent,
                's_number'         => $sNumber,
            ];
            $affected = DB::table('page')
                ->where('page_id', $pageId)
                ->where('item_id', $itemId)
                ->update($updateData);
            if ($affected > 0) {
                // 删除缓存
                self::deleteCache($pageId);
                \App\Model\Item::deleteCache($itemId);
                return $pageId;
            }
            return false;
        } else {
            // 创建新页面
            // 开源版：直接保存，不压缩
            $addData = [
                'author_uid'      => $authorUid,
                'author_username' => $authorUsername,
                'item_id'         => $itemId,
                'cat_id'          => $catId,
                'page_title'      => $pageTitle,
                'page_content'    => $pageContent,
                's_number'         => $sNumber,
                'addtime'          => time(),
            ];
            $pageId = self::addPage($itemId, $addData);
            if ($pageId > 0) {
                // 删除缓存
                \App\Model\Item::deleteCache($itemId);
                return $pageId;
            }
            return false;
        }
    }

    /**
     * 查找页面（包含完整信息，用于 API 返回）
     *
     * @param int $pageId 页面 ID
     * @param int $itemId 项目 ID
     * @return array|null 页面数据
     */
    public static function findPage(int $pageId, int $itemId): ?array
    {
        $page = self::findByIdWithContent($itemId, $pageId);
        if (!$page) {
            return null;
        }

        // 格式化时间
        if (isset($page['addtime'])) {
            $page['addtime'] = date('Y-m-d H:i:s', (int) $page['addtime']);
        }

        return $page;
    }

    /**
     * 软删除页面
     *
     * @param int $pageId 页面 ID
     * @param int $itemId 项目 ID
     * @param int $uid 删除者用户 ID
     * @param string $username 删除者用户名
     * @return bool 是否成功
     */
    public static function softDeletePage(int $pageId, int $itemId, int $uid = 0, string $username = ''): bool
    {
        if ($pageId <= 0 || $itemId <= 0) {
            return false;
        }

        try {
            // 先获取页面信息（用于插入 recycle 表）
            $page = DB::table('page')
                ->where('page_id', $pageId)
                ->where('item_id', $itemId)
                ->first();

            if (!$page) {
                return false;
            }

            $pageTitle = $page->page_title ?? '';

            // 向 recycle 表插入记录
            \App\Model\Recycle::add([
                'item_id' => $itemId,
                'page_id' => $pageId,
                'page_title' => $pageTitle,
                'del_by_uid' => $uid,
                'del_by_username' => $username,
                'del_time' => time(),
            ]);

            // 更新 page 表的 is_del = 1
            $affected = DB::table('page')
                ->where('page_id', $pageId)
                ->where('item_id', $itemId)
                ->update([
                    'is_del' => 1,
                ]);

            if ($affected > 0) {
                // 删除缓存
                self::deleteCache($pageId);
                \App\Model\Item::deleteCache($itemId);
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            error_log("softDeletePage error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取项目的页面数量
     *
     * @param int $itemId 项目 ID
     * @return int 页面数量
     */
    public static function getPageCount(int $itemId): int
    {
        if ($itemId <= 0) {
            return 0;
        }

        return DB::table('page')
            ->where('item_id', $itemId)
            ->where('is_del', 0)
            ->count();
    }

    /**
     * 删除页面（物理删除，兼容旧版 deletePage 方法）
     * 
     * 旧版实现：只根据 page_id 删除，不检查 item_id
     *
     * @param int $pageId 页面 ID
     * @param int $itemId 项目 ID（可选，用于验证，但旧版不验证）
     * @return bool 是否成功
     */
    public static function deletePage(int $pageId, int $itemId = 0): bool
    {
        if ($pageId <= 0) {
            return false;
        }

        try {
            // 旧版逻辑：只根据 page_id 删除，不检查 item_id
            $query = DB::table('page')->where('page_id', $pageId);
            
            // 如果提供了 itemId，则添加验证（用于新代码，但保持向后兼容）
            if ($itemId > 0) {
                $query->where('item_id', $itemId);
            }
            
            $affected = $query->delete();
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

