<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * 页面评论模型。
 *
 * 表结构：page_comment
 * - comment_id: 主键
 * - page_id: 页面 ID
 * - item_id: 项目 ID
 * - parent_id: 父评论 ID（0 表示一级评论）
 * - uid: 用户 ID
 * - username: 用户名
 * - content: 评论内容
 * - is_deleted: 是否删除（0=未删除，1=已删除）
 * - addtime: 添加时间
 */
class PageComment
{
    /**
     * 根据 ID 查找评论
     *
     * @param int $commentId 评论 ID
     * @return object|null
     */
    public static function findById(int $commentId): ?object
    {
        if ($commentId <= 0) {
            return null;
        }

        return DB::table('page_comment')
            ->where('comment_id', $commentId)
            ->first();
    }

    /**
     * 添加评论
     *
     * @param array $data 评论数据
     * @return int 评论 ID，失败返回 0
     */
    public static function add(array $data): int
    {
        try {
            $id = DB::table('page_comment')->insertGetId($data);
            return (int) $id;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 更新评论
     *
     * @param int $commentId 评论 ID
     * @param array $data 更新数据
     * @return bool 是否成功
     */
    public static function update(int $commentId, array $data): bool
    {
        if ($commentId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('page_comment')
                ->where('comment_id', $commentId)
                ->update($data);

            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取一级评论列表（分页）
     *
     * @param int $pageId 页面 ID
     * @param int $page 页码
     * @param int $count 每页数量
     * @return array 包含 total 和 list
     */
    public static function getTopLevelList(int $pageId, int $page = 1, int $count = 20): array
    {
        if ($pageId <= 0) {
            return ['total' => 0, 'list' => []];
        }

        $query = DB::table('page_comment')
            ->where('page_id', $pageId)
            ->where('parent_id', 0)
            ->where('is_deleted', 0);

        // 获取总数
        $total = (clone $query)->count();

        // 获取列表
        $list = $query->orderBy('addtime', 'desc')
            ->offset(($page - 1) * $count)
            ->limit($count)
            ->get()
            ->all();

        $result = [];
        foreach ($list as $row) {
            $result[] = (array) $row;
        }

        return [
            'total' => (int) $total,
            'list' => $result,
        ];
    }

    /**
     * 获取评论的所有回复（不分页）
     *
     * @param int $commentId 评论 ID
     * @return array
     */
    public static function getReplies(int $commentId): array
    {
        if ($commentId <= 0) {
            return [];
        }

        $list = DB::table('page_comment')
            ->where('parent_id', $commentId)
            ->where('is_deleted', 0)
            ->orderBy('addtime', 'asc')
            ->get()
            ->all();

        $result = [];
        foreach ($list as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }
}
