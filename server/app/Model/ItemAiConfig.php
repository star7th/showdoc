<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class ItemAiConfig
{
    /**
     * 获取项目的 AI 配置
     *
     * @param int $itemId 项目 ID
     * @return array AI 配置信息
     */
    public static function getConfig(int $itemId): array
    {
        if ($itemId <= 0) {
            return ['enabled' => 0, 'dialog_collapsed' => 1];
        }

        $row = DB::table('item_ai_config')
            ->where('item_id', $itemId)
            ->first();

        if (!$row) {
            return ['enabled' => 0, 'dialog_collapsed' => 1];
        }

        return [
            'enabled'           => (int) ($row->enabled ?? 0),
            'dialog_collapsed'  => (int) ($row->dialog_collapsed ?? 1),
            'welcome_message'   => (string) ($row->welcome_message ?? ''),
        ];
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
            $exists = DB::table('item_ai_config')
                ->where('item_id', $itemId)
                ->exists();

            // 准备更新数据
            $saveData = [
                'updatetime' => time(),
            ];

            // 只更新提供的字段
            if (isset($config['enabled'])) {
                $saveData['enabled'] = (int) $config['enabled'];
            }
            if (isset($config['dialog_collapsed'])) {
                $saveData['dialog_collapsed'] = (int) $config['dialog_collapsed'];
            }
            if (isset($config['welcome_message'])) {
                $saveData['welcome_message'] = (string) $config['welcome_message'];
            }

            // 如果没有任何字段需要更新（除了 updatetime），返回 false
            if (count($saveData) <= 1) {
                return false;
            }

            if ($exists) {
                DB::table('item_ai_config')
                    ->where('item_id', $itemId)
                    ->update($saveData);
            } else {
                $saveData['item_id'] = $itemId;
                $saveData['addtime'] = time();
                DB::table('item_ai_config')->insert($saveData);
            }

            return true;
        } catch (\Throwable $e) {
            // 记录异常信息，便于调试
            error_log('ItemAiConfig::saveConfig failed: ' . $e->getMessage());
            error_log($e->getTraceAsString());
            return false;
        }
    }
}
