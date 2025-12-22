<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class ParameterDescriptionEntry
{
    /**
     * 添加条目
     *
     * @param array $data 条目数据
     * @return int|false 返回插入的ID，失败返回false
     */
    public static function add(array $data)
    {
        $id = DB::table('parameter_description_entry')->insertGetId($data);
        return $id ?: false;
    }

    /**
     * 根据ID查找条目
     *
     * @param string $id 条目ID
     * @return object|null
     */
    public static function findById(string $id)
    {
        return DB::table('parameter_description_entry')
            ->where('id', $id)
            ->first();
    }

    /**
     * 更新条目
     *
     * @param string $id 条目ID
     * @param array $data 更新数据
     * @return int 受影响的行数
     */
    public static function update(string $id, array $data): int
    {
        return DB::table('parameter_description_entry')
            ->where('id', $id)
            ->update($data);
    }

    /**
     * 删除条目
     *
     * @param string|array $id 条目ID或ID数组
     * @return int 删除的行数
     */
    public static function delete($id): int
    {
        $query = DB::table('parameter_description_entry');
        if (is_array($id)) {
            $query->whereIn('id', $id);
        } else {
            $query->where('id', $id);
        }
        return $query->delete();
    }

    /**
     * 获取条目列表（支持条件查询和分页）
     *
     * @param array $conditions 查询条件
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param string $orderBy 排序字段
     * @param string $orderDir 排序方向
     * @return array ['data' => [], 'total' => int]
     */
    public static function getList(array $conditions = [], int $page = 1, int $pageSize = 20, string $orderBy = 'quality_score', string $orderDir = 'DESC'): array
    {
        $query = DB::table('parameter_description_entry');

        // 应用查询条件
        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                // 处理数组条件，如 ['in' => [1,2,3]]
                if (isset($value['in'])) {
                    $query->whereIn($key, $value['in']);
                } elseif (isset($value['like'])) {
                    $query->where($key, 'LIKE', $value['like']);
                }
            } else {
                $query->where($key, $value);
            }
        }

        // 计算总数
        $total = $query->count();

        // 分页
        $offset = ($page - 1) * $pageSize;
        $data = $query
            ->orderBy($orderBy, $orderDir)
            ->offset($offset)
            ->limit($pageSize)
            ->get()
            ->toArray();

        return [
            'data' => $data,
            'total' => $total
        ];
    }

    /**
     * 统计条目数量
     *
     * @param array $conditions 查询条件
     * @return int
     */
    public static function count(array $conditions = []): int
    {
        $query = DB::table('parameter_description_entry');
        foreach ($conditions as $key => $value) {
            $query->where($key, $value);
        }
        return $query->count();
    }

    /**
     * 获取Top使用字段
     *
     * @param int $itemId 项目ID
     * @param int $limit 限制数量
     * @return array
     */
    public static function getTopFields(int $itemId, int $limit = 10): array
    {
        return DB::table('parameter_description_entry')
            ->where('item_id', $itemId)
            ->select('name', 'usage_count')
            ->orderBy('usage_count', 'DESC')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * 检查是否存在相同的条目（根据item_id、name、type）
     *
     * @param int $itemId 项目ID
     * @param string $name 字段名
     * @param string $type 字段类型
     * @return object|null
     */
    public static function findExisting(int $itemId, string $name, string $type)
    {
        return DB::table('parameter_description_entry')
            ->where('item_id', $itemId)
            ->where('name', $name)
            ->where('type', $type)
            ->first();
    }

    /**
     * 批量更新使用次数
     *
     * @param array $ids 条目ID数组
     * @param int $itemId 项目ID（用于安全检查）
     * @return array ['updated' => int, 'notFound' => array]
     */
    public static function batchUpdateUsage(array $ids, int $itemId): array
    {
        $updated = 0;
        $notFound = [];

        foreach ($ids as $entryId) {
            $entry = self::findById($entryId);
            if ($entry) {
                // 验证该条目是否属于同一项目（安全检查）
                if ($entry->item_id != $itemId) {
                    continue; // 跳过不同项目的条目
                }

                $newUsageCount = $entry->usage_count + 1;
                $result = self::update($entryId, [
                    'usage_count' => $newUsageCount,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                if ($result > 0) {
                    $updated++;
                }
            } else {
                $notFound[] = $entryId;
            }
        }

        return [
            'updated' => $updated,
            'notFound' => $notFound
        ];
    }
}
