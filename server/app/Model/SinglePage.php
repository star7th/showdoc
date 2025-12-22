<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class SinglePage
{
    /**
     * 获取单页链接的唯一标识
     *
     * @param int $pageId 页面 ID
     * @return string 唯一标识，不存在或已过期返回空字符串
     */
    public static function getUniqueKey(int $pageId): string
    {
        if ($pageId <= 0) {
            return '';
        }

        $row = DB::table('single_page')
            ->where('page_id', $pageId)
            ->first();

        if (!$row) {
            return '';
        }

        // 检查是否已过期
        $expireTime = (int) ($row->expire_time ?? 0);
        if ($expireTime > 0 && $expireTime < time()) {
            // 链接已过期，删除记录
            DB::table('single_page')
                ->where('page_id', $pageId)
                ->delete();
            return '';
        }

        return (string) ($row->unique_key ?? '');
    }

    /**
     * 根据唯一标识查找单页
     *
     * @param string $uniqueKey 唯一标识
     * @return object|null 单页记录，不存在返回 null
     */
    public static function findByUniqueKey(string $uniqueKey): ?object
    {
        if (empty($uniqueKey)) {
            return null;
        }

        $row = DB::table('single_page')
            ->where('unique_key', $uniqueKey)
            ->first();

        if (!$row) {
            return null;
        }

        // 检查是否已过期
        $expireTime = (int) ($row->expire_time ?? 0);
        if ($expireTime > 0 && $expireTime < time()) {
            // 链接已过期，删除记录
            DB::table('single_page')
                ->where('unique_key', $uniqueKey)
                ->delete();
            return null;
        }

        return $row;
    }
}
