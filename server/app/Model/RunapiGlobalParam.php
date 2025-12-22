<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class RunapiGlobalParam
{
    /**
     * 根据项目 ID 和参数类型获取全局参数
     *
     * @param int $itemId 项目 ID
     * @param string $paramType 参数类型
     * @return array|null 参数数据
     */
    public static function findByItemIdAndType(int $itemId, string $paramType): ?array
    {
        if ($itemId <= 0 || empty($paramType)) {
            return null;
        }

        $row = DB::table('runapi_global_param')
            ->where('item_id', $itemId)
            ->where('param_type', $paramType)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * 更新全局参数
     *
     * @param int $itemId 项目 ID
     * @param string $paramType 参数类型
     * @param string $contentJsonStr 内容 JSON 字符串
     * @return bool 是否成功
     */
    public static function update(int $itemId, string $paramType, string $contentJsonStr): bool
    {
        if ($itemId <= 0 || empty($paramType)) {
            return false;
        }

        try {
            // 先查找是否存在
            $existing = self::findByItemIdAndType($itemId, $paramType);
            if ($existing) {
                // 更新
                $affected = DB::table('runapi_global_param')
                    ->where('item_id', $itemId)
                    ->where('param_type', $paramType)
                    ->update([
                        'content_json_str' => $contentJsonStr,
                        'last_update_time' => date('Y-m-d H:i:s'),
                    ]);
                return $affected >= 0;
            } else {
                // 新建
                $id = DB::table('runapi_global_param')->insertGetId([
                    'item_id'          => $itemId,
                    'param_type'       => $paramType,
                    'content_json_str' => $contentJsonStr,
                    'last_update_time' => date('Y-m-d H:i:s'),
                ]);
                return $id > 0;
            }
        } catch (\Throwable $e) {
            return false;
        }
    }
}
