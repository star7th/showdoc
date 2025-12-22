<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class FilePage
{
    /**
     * 获取页面的附件数量
     *
     * @param int $pageId 页面 ID
     * @return int 附件数量
     */
    public static function getAttachmentCount(int $pageId): int
    {
        if ($pageId <= 0) {
            return 0;
        }

        return DB::table('file_page')
            ->where('page_id', $pageId)
            ->count();
    }

    /**
     * 添加文件页面关联
     *
     * @param int $fileId 文件 ID
     * @param int $itemId 项目 ID
     * @param int $pageId 页面 ID
     * @return int 关联 ID，失败返回 0
     */
    public static function add(int $fileId, int $itemId, int $pageId): int
    {
        if ($fileId <= 0 || $itemId <= 0 || $pageId <= 0) {
            return 0;
        }

        try {
            $id = DB::table('file_page')->insertGetId([
                'file_id' => $fileId,
                'item_id' => $itemId,
                'page_id' => $pageId,
                'addtime' => time(),
            ]);
            return (int) $id;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 删除文件页面关联
     *
     * @param int $fileId 文件 ID
     * @param int $pageId 页面 ID
     * @return bool 是否成功
     */
    public static function delete(int $fileId, int $pageId): bool
    {
        if ($fileId <= 0 || $pageId <= 0) {
            return false;
        }

        try {
            $deleted = DB::table('file_page')
                ->where('file_id', $fileId)
                ->where('page_id', $pageId)
                ->delete();
            return $deleted > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取文件关联的页面数量
     *
     * @param int $fileId 文件 ID
     * @return int 页面数量
     */
    public static function getPageCount(int $fileId): int
    {
        if ($fileId <= 0) {
            return 0;
        }

        return DB::table('file_page')
            ->where('file_id', $fileId)
            ->where('page_id', '>', 0)
            ->count();
    }

    /**
     * 获取页面的附件列表
     *
     * @param int $pageId 页面 ID
     * @return array 附件列表
     */
    public static function getPageAttachments(int $pageId): array
    {
        if ($pageId <= 0) {
            return [];
        }

        $rows = DB::table('file_page')
            ->join('upload_file', 'file_page.file_id', '=', 'upload_file.file_id')
            ->select([
                'upload_file.*',
                'file_page.item_id',
                'file_page.page_id',
            ])
            ->where('file_page.page_id', $pageId)
            ->orderBy('file_page.addtime', 'desc')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $data = (array) $row;
            $result[] = [
                'file_id'     => (int) $data['file_id'],
                'display_name' => $data['display_name'] ?? '',
                'addtime'     => date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time())),
            ];
        }

        return $result;
    }

    /**
     * 根据文件 ID 和项目 ID 查找文件页面关联
     *
     * @param int $fileId 文件 ID
     * @param int $itemId 项目 ID
     * @return array|null 关联数据
     */
    public static function findByFileIdAndItemId(int $fileId, int $itemId): ?array
    {
        if ($fileId <= 0 || $itemId <= 0) {
            return null;
        }

        $row = DB::table('file_page')
            ->where('file_id', $fileId)
            ->where('item_id', $itemId)
            ->first();

        return $row ? (array) $row : null;
    }
}
