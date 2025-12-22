<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * 用户设置模型
 */
class UserSetting
{
    /**
     * 获取用户设置
     *
     * @param int $uid 用户ID
     * @param string $key 设置键名
     * @return string|null 设置值
     */
    public static function getSetting(int $uid, string $key): ?string
    {
        if ($uid <= 0 || empty($key)) {
            return null;
        }

        $row = DB::table('user_setting')
            ->where('uid', $uid)
            ->where('key_name', $key)
            ->first();

        return $row ? ($row->key_value ?? null) : null;
    }

    /**
     * 保存用户设置
     *
     * @param int $uid 用户ID
     * @param string $key 设置键名
     * @param string $value 设置值
     * @return bool 是否成功
     */
    public static function saveSetting(int $uid, string $key, string $value): bool
    {
        if ($uid <= 0 || empty($key)) {
            return false;
        }

        try {
            $existing = DB::table('user_setting')
                ->where('uid', $uid)
                ->where('key_name', $key)
                ->first();

            if ($existing) {
                $affected = DB::table('user_setting')
                    ->where('id', $existing->id)
                    ->update(['key_value' => $value]);
                return $affected > 0;
            } else {
                $id = DB::table('user_setting')->insertGetId([
                    'uid'      => $uid,
                    'key_name' => $key,
                    'key_value' => $value,
                    'addtime'  => date('Y-m-d H:i:s'),
                ]);
                return $id > 0;
            }
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取用户的推送地址
     *
     * @param int $uid 用户ID
     * @return string|null 推送地址
     */
    public static function getPushUrl(int $uid): ?string
    {
        return self::getSetting($uid, 'push_url');
    }

    /**
     * 保存用户的推送地址
     *
     * @param int $uid 用户ID
     * @param string $url 推送地址
     * @return bool 是否成功
     */
    public static function savePushUrl(int $uid, string $url): bool
    {
        return self::saveSetting($uid, 'push_url', $url);
    }
}

