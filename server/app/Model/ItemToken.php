<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;
use App\Common\Helper\FileHelper;

class ItemToken
{
    /**
     * 创建 Token
     *
     * @param int $itemId 项目 ID
     * @return int|false 返回插入的 ID，失败返回 false（与旧版逻辑一致）
     */
    public static function createToken(int $itemId)
    {
        if ($itemId <= 0) {
            return false;
        }

        $apiKey = FileHelper::getRandStr() . rand();
        $apiToken = FileHelper::getRandStr() . rand();

        $data = [
            'item_id'   => $itemId,
            'api_key'   => $apiKey,
            'api_token' => $apiToken,
            'addtime'   => time(),
        ];

        try {
            $id = DB::table('item_token')->insertGetId($data);
            if ($id > 0) {
                return $id; // 返回插入的 ID，与旧版逻辑一致
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 根据项目 ID 获取 Token
     *
     * @param int $itemId 项目 ID
     * @return array|null Token 数据
     */
    public static function getTokenByItemId(int $itemId): ?array
    {
        if ($itemId <= 0) {
            return null;
        }

        $row = DB::table('item_token')
            ->where('item_id', $itemId)
            ->first();

        if (!$row) {
            // 如果不存在，创建新的 Token（与旧版逻辑一致：直接创建后立即查询，不 sleep）
            $result = self::createToken($itemId);
            if ($result) {
                // 立即查询，不 sleep（与旧版逻辑一致）
                $row = DB::table('item_token')
                    ->where('item_id', $itemId)
                    ->first();
                return $row ? (array) $row : null;
            }
            return null;
        }

        return (array) $row;
    }

    /**
     * 根据 api_key 获取 Token
     *
     * @param string $apiKey API Key
     * @return array|null Token 数据
     */
    public static function getTokenByKey(string $apiKey): ?array
    {
        if (empty($apiKey)) {
            return null;
        }

        $row = DB::table('item_token')
            ->where('api_key', $apiKey)
            ->first();

        return $row ? (array) $row : null;
    }

    /**
     * 设置最后使用时间
     *
     * @param int $itemId 项目 ID
     * @return bool 是否成功
     */
    public static function setLastTime(int $itemId): bool
    {
        if ($itemId <= 0) {
            return false;
        }

        try {
            // 开源版只更新 last_check_time，不更新 use_times（与旧版逻辑一致）
            $affected = DB::table('item_token')
                ->where('item_id', $itemId)
                ->update([
                    'last_check_time' => time(),
                ]);
            return $affected > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 检查 Token
     * 如果检测通过则返回 item_id
     *
     * @param string $apiKey API Key
     * @param string $apiToken API Token
     * @param string $no 预留参数（兼容旧接口）
     * @param int $limitTimes 预留参数（开源版不使用限流）
     * @return int|false 成功返回 item_id，失败返回 false
     */
    public static function check(string $apiKey, string $apiToken, string $no = '', int $limitTimes = 1000)
    {
        $ret = self::getTokenByKey($apiKey);
        if (!$ret || $ret['api_token'] !== $apiToken) {
            return false; // 验证失败
        }

        $itemId = (int) $ret['item_id'];

        // 开源版不需要限流，只更新最后使用时间
        self::setLastTime($itemId);
        return $itemId;
    }

    /**
     * 重置 Token
     *
     * @param int $itemId 项目 ID
     * @return array|false Token 数据，失败返回 false
     */
    public static function resetToken(int $itemId)
    {
        if ($itemId <= 0) {
            return false;
        }

        // 与旧版逻辑一致：先查询，如果不存在则创建并 sleep(1)
        $row = DB::table('item_token')
            ->where('item_id', $itemId)
            ->first();
        
        $itemToken = $row ? (array) $row : null;
        
        if (!$itemToken) {
            $result = self::createToken($itemId);
            if ($result) {
                sleep(1); // 与旧版逻辑一致：创建后 sleep(1)
                $row = DB::table('item_token')
                    ->where('item_id', $itemId)
                    ->first();
                $itemToken = $row ? (array) $row : null;
                if (!$itemToken) {
                    return false;
                }
            } else {
                return false;
            }
        }

        $newToken = FileHelper::getRandStr() . rand();
        $affected = DB::table('item_token')
            ->where('item_id', $itemId)
            ->update(['api_token' => $newToken]);

        if ($affected > 0) {
            $itemToken['api_token'] = $newToken;
            return $itemToken;
        }

        return false;
    }
}
