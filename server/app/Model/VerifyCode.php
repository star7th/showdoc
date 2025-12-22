<?php

namespace App\Model;

use App\Common\Cache\CacheManager;

/**
 * 验证码模型（用于登录失败次数限制等）
 */
class VerifyCode
{
    /**
     * 次数加1
     *
     * @param string $key 缓存键
     * @return bool 是否成功
     */
    public static function insTimes(string $key): bool
    {
        $cache = CacheManager::getInstance();
        $cacheTimes = (int) ($cache->get($key) ?? 0);
        $ret = $cache->set($key, $cacheTimes + 1, 24 * 60 * 60); // 24小时过期
        return $ret;
    }

    /**
     * 检查次数是否超过限制
     *
     * @param string $key 缓存键
     * @param int $maxTimes 最大次数，默认5次
     * @return bool true表示未超过限制，false表示超过限制
     */
    public static function checkTimes(string $key, int $maxTimes = 5): bool
    {
        $cache = CacheManager::getInstance();
        $cacheTimes = (int) ($cache->get($key) ?? 0);
        
        if ($cacheTimes >= $maxTimes) {
            return false; // 超过限制
        }
        
        return true; // 未超过限制
    }
}

