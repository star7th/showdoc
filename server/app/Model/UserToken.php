<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;
use App\Common\Helper\IpHelper;

class UserToken
{
    public static function createToken(int $uid, int $ttlSeconds = 0): ?string
    {
        if ($uid <= 0) {
            return null;
        }

        $now   = time();
        $ttl   = $ttlSeconds > 0 ? $ttlSeconds : (60 * 60 * 24 * 90);
        $expire = $now + $ttl;

        $token = md5(md5($uid . $expire . $now . rand() . 'showdoc') . 'rdgtrd12367hghf54t')
            . md5($uid . $expire . $now . rand() . 'showdoc');

        // 旧版开源版的 user_token 表没有 user_agent 字段，这里只写旧表中已有的字段
        $data = [
            'uid'          => $uid,
            'token'        => $token,
            'token_expire' => $expire,
            'ip'           => IpHelper::getClientIp(),
            'addtime'      => $now,
        ];

        $ok = DB::table('user_token')->insert($data);
        if (!$ok) {
            return null;
        }

        // 清理过期记录（与旧实现保持一致）
        $fiveYearsAgo = $now - 5 * 365 * 24 * 60 * 60;

        DB::table('user_token')
            ->where('token_expire', '<', $now)
            ->update(['token' => '']);

        DB::table('user_token')
            ->where('token_expire', '<', $fiveYearsAgo)
            ->delete();

        return $token;
    }

    public static function getToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $row = DB::table('user_token')
            ->where('token', $token)
            ->first();

        return $row ? (array) $row : null;
    }

    public static function touch(string $token): void
    {
        $token = trim($token);
        if ($token === '') {
            return;
        }

        DB::table('user_token')
            ->where('token', $token)
            ->update(['last_check_time' => time()]);
    }

    /**
     * 设置用户所有 token 的过期时间（用于拉黑用户）
     *
     * @param int $uid 用户 ID
     * @param int $expire 过期时间戳（0 表示立即过期）
     * @return bool 是否成功
     */
    public static function setTokenExpire(int $uid, int $expire = 0): bool
    {
        if ($uid <= 0) {
            return false;
        }

        try {
            $affected = DB::table('user_token')
                ->where('uid', $uid)
                ->update(['token_expire' => $expire]);
            return $affected >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取用户的登录日志列表
     *
     * @param int $uid 用户 ID
     * @param int $limit 限制数量
     * @return array 登录日志列表
     */
    public static function getLoginLog(int $uid, int $limit = 100): array
    {
        if ($uid <= 0) {
            return [];
        }

        $rows = DB::table('user_token')
            ->where('uid', $uid)
            ->orderBy('last_check_time', 'desc')
            ->orderBy('addtime', 'desc')
            ->limit($limit)
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $data = (array) $row;
            $data['addtime'] = date('Y-m-d H:i:s', (int) ($data['addtime'] ?? time()));
            $data['last_check_time'] = date('Y-m-d H:i:s', (int) ($data['last_check_time'] ?? time()));
            $data['token'] = ''; // 不返回 token
            $result[] = $data;
        }

        return $result;
    }
}
