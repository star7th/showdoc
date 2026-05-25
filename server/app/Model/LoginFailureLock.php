<?php

namespace App\Model;

use App\Common\Cache\CacheManager;

class LoginFailureLock
{
    private const MAX_FAILURES = 5;
    private const LOCK_SECONDS = 300;
    private const KEY_PREFIX = 'login_failure:';

    private static function buildKey(string $username): string
    {
        return self::KEY_PREFIX . md5(strtolower($username));
    }

    public static function isLocked(string $username): bool
    {
        $cache = CacheManager::getInstance();
        $data = $cache->get(self::buildKey($username));
        if (!$data || !isset($data['count'], $data['locked_until'])) {
            return false;
        }

        if ($data['locked_until'] <= time()) {
            $cache->delete(self::buildKey($username));
            return false;
        }

        return $data['count'] >= self::MAX_FAILURES;
    }

    public static function recordFailure(string $username): void
    {
        $cache = CacheManager::getInstance();
        $key = self::buildKey($username);
        $now = time();
        $data = $cache->get($key);

        if (!$data || !isset($data['count'])) {
            $data = ['count' => 0, 'locked_until' => 0];
        }

        if (isset($data['locked_until']) && $data['locked_until'] <= $now) {
            $data['count'] = 0;
            $data['locked_until'] = 0;
        }

        $data['count']++;
        if ($data['count'] >= self::MAX_FAILURES) {
            $data['locked_until'] = $now + self::LOCK_SECONDS;
        }

        $cache->set($key, $data, self::LOCK_SECONDS + 60);
    }

    public static function clearFailure(string $username): void
    {
        $cache = CacheManager::getInstance();
        $cache->delete(self::buildKey($username));
    }
}
