<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class VersionUpdate
{
    /**
     * 根据应用类型获取版本信息
     *
     * @param string $appType 应用类型（opensource/app/pc/runapi_pc）
     * @return array|null 版本信息
     */
    public static function findByAppType(string $appType): ?array
    {
        if (empty($appType)) {
            return null;
        }

        $row = DB::table('version_update')
            ->where('app_type', $appType)
            ->where('status', 1)
            ->first();

        return $row ? (array) $row : null;
    }
}
