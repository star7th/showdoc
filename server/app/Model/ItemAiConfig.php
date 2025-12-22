<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class ItemAiConfig
{
    /**
     * 获取项目的 AI 配置
     *
     * @param int $itemId 项目 ID
     * @return array|null AI 配置信息
     */
    public static function getConfig(int $itemId): ?array
    {
        if ($itemId <= 0) {
            return null;
        }

        $row = DB::table('item_ai_config')
            ->where('item_id', $itemId)
            ->first();

        if (!$row) {
            return null;
        }

        $config = (array) $row;
        
        // 解析 JSON 配置
        if (!empty($config['config'])) {
            $decoded = json_decode($config['config'], true);
            if (is_array($decoded)) {
                $config = array_merge($config, $decoded);
            }
        }

        return $config;
    }

    /**
     * 保存项目的 AI 配置
     *
     * @param int $itemId 项目 ID
     * @param array $config AI 配置数据
     * @return bool 是否成功
     */
    public static function saveConfig(int $itemId, array $config): bool
    {
        if ($itemId <= 0) {
            return false;
        }

        try {
            $configJson = json_encode($config, JSON_UNESCAPED_UNICODE);
            
            $exists = DB::table('item_ai_config')
                ->where('item_id', $itemId)
                ->exists();

            if ($exists) {
                DB::table('item_ai_config')
                    ->where('item_id', $itemId)
                    ->update([
                        'config' => $configJson,
                        'update_time' => time(),
                    ]);
            } else {
                DB::table('item_ai_config')->insert([
                    'item_id' => $itemId,
                    'config' => $configJson,
                    'addtime' => time(),
                    'update_time' => time(),
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}

