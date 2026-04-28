<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class Mock
{
    /**
     * 根据页面ID查找Mock数据
     *
     * @param int $pageId 页面ID
     * @return array|null
     */
    public static function findByPageId(int $pageId): ?array
    {
        if ($pageId <= 0) {
            return null;
        }

        $row = DB::table('mock')
            ->where('page_id', $pageId)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * 根据唯一key查找Mock数据
     *
     * @param string $uniqueKey 唯一key
     * @return array|null
     */
    public static function findByUniqueKey(string $uniqueKey): ?array
    {
        if (empty($uniqueKey)) {
            return null;
        }

        $row = DB::table('mock')
            ->where('unique_key', $uniqueKey)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * 根据项目ID和路径查找Mock数据
     * 支持精确匹配和正则匹配（路径以~开头为正则）
     *
     * @param int $itemId 项目ID
     * @param string $path 路径
     * @return array|null
     */
    public static function findByItemIdAndPath(int $itemId, string $path): ?array
    {
        if ($itemId <= 0 || empty($path)) {
            return null;
        }

        // 1. 先精确匹配
        $row = DB::table('mock')
            ->where('item_id', $itemId)
            ->where('path', $path)
            ->first();

        if ($row) {
            return (array) $row;
        }

        // 2. 精确匹配未命中，尝试正则匹配（path LIKE '~%' 的记录）
        $regexRows = DB::table('mock')
            ->where('item_id', $itemId)
            ->where('path', 'like', '~%')
            ->orderBy('id', 'asc')
            ->get();

        if ($regexRows->isEmpty()) {
            return null;
        }

        // 设置 PCRE 回溯限制，防止 ReDoS
        $prevBacktrackLimit = ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', '10000');

        foreach ($regexRows as $regexRow) {
            $storedPath = $regexRow->path;
            if (strpos($storedPath, '~') !== 0) {
                continue;
            }
            $pattern = substr($storedPath, 1);
            if (!self::isSafeRegex($pattern)) {
                continue; // 跳过不安全的正则
            }
            $delimiter = '/';
            $escapedPattern = str_replace($delimiter, '\\' . $delimiter, $pattern);
            $fullPattern = $delimiter . $escapedPattern . '/u';

            $match = @preg_match($fullPattern, $path);
            if ($match === 1) {
                ini_set('pcre.backtrack_limit', $prevBacktrackLimit);
                return (array) $regexRow;
            }
        }

        ini_set('pcre.backtrack_limit', $prevBacktrackLimit);
        return null;
    }

    /**
     * 检测正则表达式是否安全（防止 ReDoS 攻击）
     *
     * @param string $pattern 正则表达式（不含定界符）
     * @return bool
     */
    public static function isSafeRegex(string $pattern): bool
    {
        // 长度限制
        if (strlen($pattern) > 500) {
            return false;
        }

        // 检测嵌套量词：(a+)+ 或 (a*)* 等
        if (preg_match('/(\([^)]*[+*][^)]*\)[+*])/', $pattern)) {
            return false;
        }

        // 检测重叠 alternation：(a|a)+ 等 ReDoS 模式
        if (preg_match_all('/\(([^)]+)\)\s*[+*]/', $pattern, $matches)) {
            foreach ($matches[1] as $groupContent) {
                if (strpos($groupContent, '|') !== false) {
                    $parts = explode('|', $groupContent);
                    $seen = [];
                    foreach ($parts as $part) {
                        $trimmed = trim($part);
                        if (isset($seen[$trimmed])) {
                            return false;
                        }
                        $seen[$trimmed] = true;
                    }
                }
            }
        }

        // 检测连续量化多段（4个以上）：X{1,30}Y{1,30}Z{1,30}W{1,30}
        $quantifiedCount = preg_match_all('/[^+*?|()\\s]+\{[\d,\s]+\}/', $pattern);
        if ($quantifiedCount >= 4) {
            return false;
        }

        return true;
    }

    /**
     * 保存Mock数据（新建或更新）
     *
     * @param int $pageId 页面ID
     * @param array $data Mock数据
     * @return bool
     */
    public static function saveByPageId(int $pageId, array $data): bool
    {
        if ($pageId <= 0) {
            return false;
        }

        $affected = DB::table('mock')
            ->where('page_id', $pageId)
            ->update($data);

        return $affected >= 0; // 即使没有更新（affected=0）也算成功
    }

    /**
     * 添加Mock数据
     *
     * @param array $data Mock数据
     * @return int|false 返回插入的ID，失败返回false
     */
    public static function add(array $data)
    {
        $id = DB::table('mock')->insertGetId($data);
        return $id ?: false;
    }

    /**
     * 增加查看次数
     *
     * @param int $id Mock记录ID
     * @return bool
     */
    public static function incrementViewTimes(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $affected = DB::table('mock')
            ->where('id', $id)
            ->increment('view_times', 1);

        return $affected > 0;
    }
}
