<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class ItemBlacklist
{
    /**
     * 检查用户是否有权限访问项目
     *
     * @param int $itemId 项目 ID
     * @param array $user 用户信息
     * @return array ['allowed' => bool, 'error_msg' => string]
     */
    public static function checkAccess(int $itemId, array $user): array
    {
        // 开源版无黑名单功能，始终允许访问
        return [
            'allowed' => true,
            'error_msg' => '',
        ];
    }

    /**
     * 检查项目是否在黑名单中
     *
     * @param int $itemId 项目 ID
     * @return object|null 开源版始终返回 null
     */
    public static function findByItemId(int $itemId): ?object
    {
        // 开源版无黑名单功能
        return null;
    }
}
