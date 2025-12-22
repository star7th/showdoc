<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class UploadFile
{
    /**
     * 根据 sign 查找文件
     *
     * @param string $sign 文件签名
     * @return object|null
     */
    public static function findBySign(string $sign): ?object
    {
        if (empty($sign)) {
            return null;
        }

        return DB::table('upload_file')
            ->where('sign', $sign)
            ->first();
    }

    /**
     * 根据 file_id 查找文件
     *
     * @param int $fileId 文件 ID
     * @return object|null
     */
    public static function findById(int $fileId): ?object
    {
        if ($fileId <= 0) {
            return null;
        }

        return DB::table('upload_file')
            ->where('file_id', $fileId)
            ->first();
    }

    /**
     * 添加文件记录
     *
     * @param array $data 文件数据
     * @return int 文件 ID，失败返回 0
     */
    public static function add(array $data): int
    {
        try {
            $id = DB::table('upload_file')->insertGetId($data);
            return (int) $id;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 更新文件记录
     *
     * @param int $fileId 文件 ID
     * @param array $data 更新数据
     * @return bool 是否成功
     */
    public static function update(int $fileId, array $data): bool
    {
        if ($fileId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('upload_file')
                ->where('file_id', $fileId)
                ->update($data);
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 删除文件记录
     *
     * @param int $fileId 文件 ID
     * @return bool 是否成功
     */
    public static function delete(int $fileId): bool
    {
        if ($fileId <= 0) {
            return false;
        }

        try {
            $deleted = DB::table('upload_file')
                ->where('file_id', $fileId)
                ->delete();
            return $deleted > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取用户的文件列表
     *
     * @param int $uid 用户 ID
     * @param array $filters 筛选条件
     * @param int $page 页码
     * @param int $count 每页数量
     * @return array 包含 list 和 total
     */
    public static function getMyList(int $uid, array $filters = [], int $page = 1, int $count = 20): array
    {
        if ($uid <= 0) {
            return ['list' => [], 'total' => 0];
        }

        $query = DB::table('upload_file')
            ->where('uid', $uid);

        // 文件类型筛选
        if (isset($filters['attachment_type'])) {
            if ($filters['attachment_type'] == 1) {
                $query->where('file_type', 'like', '%image%');
            } elseif ($filters['attachment_type'] == 2) {
                $query->where('file_type', 'not like', '%image%');
            }
        }

        // 文件名搜索
        if (!empty($filters['display_name'])) {
            $likeDisplay = '%' . addcslashes($filters['display_name'], '%_\\') . '%';
            $query->where('display_name', 'like', $likeDisplay);
        }

        // 获取总数
        $total = (clone $query)->count();

        // 获取列表
        $list = $query
            ->orderBy('addtime', 'desc')
            ->offset(($page - 1) * $count)
            ->limit($count)
            ->get()
            ->all();

        $result = [];
        foreach ($list as $row) {
            $data = (array) $row;
            $data['file_size_m'] = round($data['file_size'] / (1024 * 1024), 3);
            $data['addtime'] = date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time()));
            $data['last_visit_time'] = date('Y-m-d H:i:s', (int) ($data['last_visit_time'] ?? time()));
            $result[] = $data;
        }

        // 获取已使用空间
        $used = DB::table('upload_file')
            ->where('uid', $uid)
            ->sum('file_size');

        return [
            'list'     => $result,
            'total'    => (int) $total,
            'used'     => (int) ($used ?? 0),
            'used_m'   => round(($used ?? 0) / (1024 * 1024), 3),
        ];
    }

    /**
     * 获取全站文件列表（管理员用）
     *
     * @param array $filters 筛选条件
     * @param int $page 页码
     * @param int $count 每页数量
     * @return array 包含 list 和 total
     */
    public static function getAllList(array $filters = [], int $page = 1, int $count = 20): array
    {
        $query = DB::table('upload_file');

        // 文件类型筛选
        if (isset($filters['attachment_type'])) {
            if ($filters['attachment_type'] == 1) {
                $query->where('file_type', 'like', '%image%');
            } elseif ($filters['attachment_type'] == 2) {
                $query->where('file_type', 'not like', '%image%');
            }
        }

        // 文件名或 sign 搜索
        if (!empty($filters['display_name'])) {
            $likeDisplay = '%' . addcslashes($filters['display_name'], '%_\\') . '%';
            $query->where(function ($q) use ($likeDisplay) {
                $q->where('display_name', 'like', $likeDisplay)
                    ->orWhere('sign', 'like', $likeDisplay);
            });
        }

        // 用户名筛选
        if (!empty($filters['username'])) {
            $user = User::findByUsernameOrEmail($filters['username']);
            if ($user) {
                $query->where('uid', (int) $user->uid);
            } else {
                // 用户不存在，返回空结果
                $query->where('uid', -99);
            }
        }

        // 获取总数
        $total = (clone $query)->count();

        // 获取列表
        $list = $query
            ->orderBy('addtime', 'desc')
            ->offset(($page - 1) * $count)
            ->limit($count)
            ->get()
            ->all();

        $result = [];
        foreach ($list as $row) {
            $data = (array) $row;

            // 获取用户名
            $username = '';
            if ($data['uid']) {
                $user = User::findById((int) $data['uid']);
                if ($user) {
                    $username = $user->username ?? '';
                }
            }

            $data['username'] = $username;
            $data['file_size_m'] = round($data['file_size'] / (1024 * 1024), 3);
            $data['addtime'] = date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time()));
            $data['last_visit_time'] = date('Y-m-d H:i:s', (int) ($data['last_visit_time'] ?? time()));
            $result[] = $data;
        }

        // 获取已使用空间
        $usedQuery = DB::table('upload_file');
        if (isset($filters['attachment_type'])) {
            if ($filters['attachment_type'] == 1) {
                $usedQuery->where('file_type', 'like', '%image%');
            } elseif ($filters['attachment_type'] == 2) {
                $usedQuery->where('file_type', 'not like', '%image%');
            }
        }
        if (!empty($filters['username'])) {
            $user = User::findByUsernameOrEmail($filters['username']);
            if ($user) {
                $usedQuery->where('uid', (int) $user->uid);
            } else {
                $usedQuery->where('uid', -99);
            }
        }
        $used = $usedQuery->sum('file_size');

        return [
            'list'   => $result,
            'total'  => (int) $total,
            'used'   => (int) ($used ?? 0),
            'used_m' => round(($used ?? 0) / (1024 * 1024), 3),
        ];
    }

    /**
     * 批量更新文件的 uid（转让附件）
     *
     * @param array $fileIds 文件 ID 数组
     * @param int $targetUid 目标用户 ID
     * @return int 更新的数量
     */
    public static function transferFiles(array $fileIds, int $targetUid): int
    {
        if (empty($fileIds) || $targetUid <= 0) {
            return 0;
        }

        try {
            $affected = DB::table('upload_file')
                ->whereIn('file_id', $fileIds)
                ->update(['uid' => $targetUid]);
            return $affected;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 获取未使用的图片文件
     *
     * @param int $time 时间戳
     * @param int $maxVisitTimes 最大访问次数
     * @param int $limit 限制数量
     * @return array 文件列表
     */
    public static function getUnusedImages(int $time, int $maxVisitTimes = 3, int $limit = 50): array
    {
        $rows = DB::table('upload_file')
            ->where('visit_times', '<=', $maxVisitTimes)
            ->where('file_type', 'like', '%image%')
            ->where('last_visit_time', '<', $time)
            ->where('addtime', '<', $time)
            ->limit($limit)
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }
}
