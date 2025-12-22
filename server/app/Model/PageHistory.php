<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * 页面历史模型（开源版：使用单表 page_history，不支持分表）
 * 
 * 注意：开源版的 page_content 不经过压缩存储，与主版不同。
 * 读取时兼容旧数据（可能有些是压缩的），但写入时不压缩。
 */
class PageHistory
{
    /**
     * 添加历史版本
     *
     * @param int $pageId 页面 ID
     * @param array $data 页面数据
     * @return bool 是否成功
     */
    public static function add(int $pageId, array $data): bool
    {
        if ($pageId <= 0) {
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
            return DB::table('page_history')->insert($data);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取历史版本列表
     *
     * @param int $pageId 页面 ID
     * @param int $limit 限制数量
     * @return array 历史版本列表
     */
    public static function getList(int $pageId, int $limit = 100): array
    {
        if ($pageId <= 0) {
            return [];
        }

        $rows = DB::table('page_history')
            ->where('page_id', $pageId)
            ->orderBy('addtime', 'desc')
            ->limit($limit)
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $data = (array) $row;
            $data['addtime'] = date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time()));

            // 开源版：page_content 不压缩存储，但兼容旧压缩数据
            if (!empty($data['page_content'])) {
                $decoded = \App\Common\Helper\ContentCodec::decompress($data['page_content']);
                if ($decoded !== '' && $decoded !== $data['page_content']) {
                    // 是压缩数据，解压后处理
                    $data['page_content'] = htmlspecialchars_decode($decoded);
                    $data['page_content'] = htmlspecialchars($data['page_content'], ENT_NOQUOTES);
                } else {
                    // 未压缩数据，直接处理
                    $data['page_content'] = htmlspecialchars_decode($data['page_content']);
                    $data['page_content'] = htmlspecialchars($data['page_content'], ENT_NOQUOTES);
                }
            }

            $result[] = $data;
        }

        return $result;
    }

    /**
     * 获取历史版本数量
     *
     * @param int $pageId 页面 ID
     * @return int 数量
     */
    public static function getCount(int $pageId): int
    {
        if ($pageId <= 0) {
            return 0;
        }

        return DB::table('page_history')
            ->where('page_id', $pageId)
            ->count();
    }

    /**
     * 删除超出限制的历史版本
     *
     * @param int $pageId 页面 ID
     * @param int $keepCount 保留数量
     * @return bool 是否成功
     */
    public static function deleteOldVersions(int $pageId, int $keepCount): bool
    {
        if ($pageId <= 0 || $keepCount <= 0) {
            return false;
        }

        // 获取需要保留的最新版本 ID
        $rows = DB::table('page_history')
            ->where('page_id', $pageId)
            ->orderBy('page_history_id', 'desc')
            ->limit($keepCount)
            ->get()
            ->all();

        if (empty($rows) || count($rows) < $keepCount) {
            return true; // 没有超出限制
        }

        $lastId = (int) ($rows[$keepCount - 1]->page_history_id ?? 0);
        if ($lastId <= 0) {
            return true;
        }

        // 删除旧版本
        try {
            DB::table('page_history')
                ->where('page_id', $pageId)
                ->where('page_history_id', '<', $lastId)
                ->delete();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 更新历史版本的备注
     *
     * @param int $pageId 页面 ID
     * @param int $pageHistoryId 历史版本 ID
     * @param string $pageComments 备注
     * @return bool 是否成功
     */
    public static function updateComments(int $pageId, int $pageHistoryId, string $pageComments): bool
    {
        if ($pageId <= 0 || $pageHistoryId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('page_history')
                ->where('page_history_id', $pageHistoryId)
                ->update(['page_comments' => $pageComments]);
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据历史版本 ID 查找历史版本
     *
     * @param int $pageId 页面 ID
     * @param int $pageHistoryId 历史版本 ID
     * @return array|null 历史版本数据
     */
    public static function findById(int $pageId, int $pageHistoryId): ?array
    {
        if ($pageId <= 0 || $pageHistoryId <= 0) {
            return null;
        }

        $row = DB::table('page_history')
            ->where('page_id', $pageId)
            ->where('page_history_id', $pageHistoryId)
            ->first();

        if (!$row) {
            return null;
        }

        $data = (array) $row;

        // 开源版：page_content 不压缩存储，但兼容旧压缩数据
        if (!empty($data['page_content'])) {
            $decoded = \App\Common\Helper\ContentCodec::decompress($data['page_content']);
            if ($decoded !== '' && $decoded !== $data['page_content']) {
                // 是压缩数据，解压后返回
                $data['page_content'] = $decoded;
            }
            // 未压缩数据，直接返回
        }

        return $data;
    }
}

