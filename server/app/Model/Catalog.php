<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class Catalog
{
    /**
     * 根据目录 ID 查找目录
     *
     * @param int $catId 目录 ID
     * @return object|null
     */
    public static function findById(int $catId): ?object
    {
        if ($catId <= 0) {
            return null;
        }

        return DB::table('catalog')
            ->where('cat_id', $catId)
            ->first();
    }

    /**
     * 根据父目录 ID 查找目录
     *
     * @param int $parentCatId 父目录 ID
     * @return object|null
     */
    public static function findByParentId(int $parentCatId): ?object
    {
        if ($parentCatId <= 0) {
            return null;
        }

        return DB::table('catalog')
            ->where('parent_cat_id', $parentCatId)
            ->first();
    }

    /**
     * 根据目录 ID 和项目 ID 查找目录
     *
     * @param int $catId 目录 ID
     * @param int $itemId 项目 ID
     * @return object|null
     */
    public static function findByIdAndItemId(int $catId, int $itemId): ?object
    {
        if ($catId <= 0 || $itemId <= 0) {
            return null;
        }

        return DB::table('catalog')
            ->where('cat_id', $catId)
            ->where('item_id', $itemId)
            ->first();
    }

    /**
     * 获取目录列表
     *
     * @param int $itemId 项目 ID
     * @param bool $isGroup 是否按分组返回（树形结构）
     * @return array 目录列表
     */
    public static function getList(int $itemId, bool $isGroup = false): array
    {
        if ($itemId <= 0) {
            return [];
        }

        $rows = DB::table('catalog')
            ->where('item_id', $itemId)
            ->orderBy('s_number', 'asc')
            ->orderBy('cat_id', 'asc')
            ->get()
            ->all();

        if (empty($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $data = (array) $row;
            $data['addtime'] = date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time()));
            $result[] = $data;
        }

        if ($isGroup) {
            $result = self::buildTree($result);
        }

        return $result;
    }

    /**
     * 构建目录树
     *
     * @param array $catalogs 所有目录
     * @return array 树形结构的目录
     */
    private static function buildTree(array $catalogs): array
    {
        $tree = [];
        foreach ($catalogs as $catalog) {
            $parentCatId = (int) ($catalog['parent_cat_id'] ?? 0);
            if ($parentCatId === 0) {
                // 根目录
                $catalog['sub'] = self::getChildren($catalog['cat_id'], $catalogs);
                $tree[] = $catalog;
            }
        }
        return $tree;
    }

    /**
     * 递归获取子目录
     *
     * @param int $catId 目录 ID
     * @param array $catalogs 所有目录
     * @return array 子目录列表
     */
    private static function getChildren(int $catId, array $catalogs): array
    {
        $children = [];
        foreach ($catalogs as $catalog) {
            if ((int) ($catalog['parent_cat_id'] ?? 0) === $catId) {
                $catalog['sub'] = self::getChildren($catalog['cat_id'], $catalogs);
                $children[] = $catalog;
            }
        }
        return $children;
    }

    /**
     * 根据层级获取目录列表
     *
     * @param int $itemId 项目 ID
     * @param int $level 层级（2=二级目录，3=三级目录等）
     * @return array 目录列表
     */
    public static function getListByLevel(int $itemId, int $level = 2): array
    {
        if ($itemId <= 0) {
            return [];
        }

        $rows = DB::table('catalog')
            ->where('item_id', $itemId)
            ->where('level', $level)
            ->orderBy('s_number', 'asc')
            ->orderBy('cat_id', 'asc')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $data = (array) $row;
            $data['addtime'] = date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time()));
            $result[] = $data;
        }

        return $result;
    }

    /**
     * 获取某个目录的子目录列表
     *
     * @param int $itemId 项目 ID
     * @param int $catId 目录 ID
     * @return array 子目录列表
     */
    public static function getChildrenByCatId(int $itemId, int $catId): array
    {
        if ($itemId <= 0 || $catId <= 0) {
            return [];
        }

        $allCatalogs = self::getList($itemId, true);
        return self::findChildrenInTree($allCatalogs, $catId);
    }

    /**
     * 在树形结构中查找指定目录的子目录
     *
     * @param array $tree 目录树
     * @param int $catId 目录 ID
     * @return array 子目录列表
     */
    private static function findChildrenInTree(array $tree, int $catId): array
    {
        foreach ($tree as $catalog) {
            if ((int) ($catalog['cat_id'] ?? 0) === $catId) {
                return $catalog['sub'] ?? [];
            }
            if (!empty($catalog['sub'])) {
                $found = self::findChildrenInTree($catalog['sub'], $catId);
                if (!empty($found)) {
                    return $found;
                }
            }
        }
        return [];
    }

    /**
     * 过滤成员目录（根据用户目录权限）
     *
     * @param int $uid 用户 ID
     * @param array $catData 目录数据
     * @return array 过滤后的目录数据
     */
    public static function filterMemberCat(int $uid, array $catData): array
    {
        if (empty($catData) || $uid <= 0) {
            return $catData;
        }

        $itemId = (int) ($catData[0]['item_id'] ?? 0);
        if ($itemId <= 0) {
            return $catData;
        }

        // 获取用户在该项目下拥有权限的目录 ID 集合
        $catIds = \App\Model\Member::getCatIds($itemId, $uid);
        if (empty($catIds)) {
            // 没有目录权限限制，返回原数据
            return $catData;
        }

        $allowed = array_flip(array_map('intval', $catIds));
        $filtered = [];

        foreach ($catData as $catalog) {
            $catId = (int) ($catalog['cat_id'] ?? 0);
            if (isset($allowed[$catId])) {
                // 如果有子目录，递归过滤
                if (!empty($catalog['sub'])) {
                    $catalog['sub'] = self::filterMemberCat($uid, $catalog['sub']);
                }
                $filtered[] = $catalog;
            }
        }

        return $filtered;
    }

    /**
     * 保存目录（新建或更新）
     *
     * @param int $catId 目录 ID（0 表示新建）
     * @param int $itemId 项目 ID
     * @param string $catName 目录名称
     * @param int $parentCatId 父目录 ID
     * @param int $sNumber 排序号
     * @return array 保存后的目录数据
     */
    public static function save(int $catId, int $itemId, string $catName, int $parentCatId = 0, int $sNumber = 0): ?array
    {
        if ($itemId <= 0 || empty($catName)) {
            return null;
        }

        $data = [
            'cat_name'     => $catName,
            'item_id'      => $itemId,
            'parent_cat_id' => $parentCatId,
        ];

        if ($sNumber > 0) {
            $data['s_number'] = $sNumber;
        }

        // 计算层级
        if ($parentCatId > 0) {
            $parent = self::findById($parentCatId);
            if ($parent) {
                $data['level'] = (int) ($parent->level ?? 2) + 1;
            } else {
                $data['level'] = 2;
            }
        } else {
            $data['level'] = 2;
        }

        if ($catId > 0) {
            // 更新
            try {
                $affected = DB::table('catalog')
                    ->where('cat_id', $catId)
                    ->update($data);
                if ($affected > 0) {
                    $row = DB::table('catalog')
                        ->where('cat_id', $catId)
                        ->first();
                    return $row ? (array) $row : null;
                }
                return null;
            } catch (\Throwable $e) {
                return null;
            }
        } else {
            // 新建
            $data['addtime'] = time();
            try {
                $catId = DB::table('catalog')->insertGetId($data);
                if ($catId > 0) {
                    $row = DB::table('catalog')
                        ->where('cat_id', $catId)
                        ->first();
                    return $row ? (array) $row : null;
                }
                return null;
            } catch (\Throwable $e) {
                return null;
            }
        }
    }

    /**
     * 获取目录数量
     *
     * @param int $itemId 项目 ID
     * @return int 目录数量
     */
    public static function getCount(int $itemId): int
    {
        if ($itemId <= 0) {
            return 0;
        }

        return DB::table('catalog')
            ->where('item_id', $itemId)
            ->count();
    }

    /**
     * 根据目录路径保存目录（如果不存在则创建）
     * 路径格式：'二级目录/三级目录/四级目录'
     *
     * @param string $catPath 目录路径
     * @param int $itemId 项目 ID
     * @return int|false 返回最后一层目录的 ID，失败返回 false
     */
    public static function saveCatPath(string $catPath, int $itemId)
    {
        if (empty($catPath) || strlen($catPath) > 1000 || $itemId <= 0) {
            return false;
        }

        // 如果路径以 / 开头且长度大于1，则去掉第一个 /
        if (substr($catPath, 0, 1) == '/' && strlen($catPath) > 1) {
            $catPath = substr($catPath, 1);
        }

        $catalogArray = explode('/', $catPath);
        $catIdsArray = [];

        for ($i = 0; $i < count($catalogArray); $i++) {
            $level = $i + 2; // 二级目录从 level=2 开始
            $catName = trim($catalogArray[$i]);
            if (empty($catName)) {
                continue;
            }

            $parentCatId = 0;
            if ($i > 0) {
                // 非顶级目录，应该有 parent_cat_id
                $parentCatId = $catIdsArray[$i - 1] ?? 0;
            }

            // 查找是否已存在该目录
            $existing = DB::table('catalog')
                ->where('item_id', $itemId)
                ->where('level', $level)
                ->where('cat_name', $catName)
                ->where('parent_cat_id', $parentCatId)
                ->first();

            if ($existing) {
                $catIdsArray[$i] = (int) $existing->cat_id;
            } else {
                // 创建新目录
                $addData = [
                    'cat_name'      => $catName,
                    'item_id'       => $itemId,
                    'addtime'       => time(),
                    'level'         => $level,
                    'parent_cat_id' => $parentCatId,
                ];
                $catId = DB::table('catalog')->insertGetId($addData);
                if ($catId > 0) {
                    $catIdsArray[$i] = (int) $catId;
                } else {
                    return false;
                }
            }
        }

        // 返回最后一层目录的 ID
        if (!empty($catIdsArray)) {
            return end($catIdsArray);
        }

        return false;
    }

    /**
     * 删除目录（包括子目录和页面）
     *
     * @param int $catId 目录 ID
     * @return bool 是否成功
     */
    public static function deleteCat(int $catId): bool
    {
        if ($catId <= 0) {
            return false;
        }

        $catalog = self::findById($catId);
        if (!$catalog) {
            return false;
        }

        $itemId = (int) $catalog->item_id;

        try {
            // 获取所有子目录
            $subCatalogs = DB::table('catalog')
                ->where('item_id', $itemId)
                ->where('parent_cat_id', $catId)
                ->get()
                ->all();

            // 递归删除子目录
            foreach ($subCatalogs as $subCatalog) {
                self::deleteCat((int) $subCatalog->cat_id);
            }

            // 删除该目录下的所有页面（软删除）
            // 开源版：使用单表 page，不支持分表
            $table = 'page';
            DB::table($table)
                ->where('item_id', $itemId)
                ->where('cat_id', $catId)
                ->update(['is_del' => 1]);

            // 删除目录
            $affected = DB::table('catalog')
                ->where('cat_id', $catId)
                ->delete();

            if ($affected > 0) {
                // 删除缓存
                \App\Model\Item::deleteCache($itemId);
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
