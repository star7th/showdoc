<?php

namespace App\Common\Cache;

use App\Common\Helper\Env;

class CacheManager
{
    private static ?CacheManager $instance = null;
    /** @var \Redis|\RedisCluster|null */
    private $redis = null;

    private function __construct()
    {
        // 开源版默认不启用 Redis（REDIS_ENABLED=false），降级为无缓存模式
        // 只有明确设置为 'true' 或 '1' 时才启用 Redis
        $enabled = Env::get('REDIS_ENABLED', 'false');
        
        // 检查是否启用 Redis：只有 'true' 或 '1' 才启用，其他情况（包括未配置）都禁用
        $isEnabled = false;
        if (is_string($enabled)) {
            $enabled = strtolower(trim($enabled));
            $isEnabled = ($enabled === 'true' || $enabled === '1');
        } elseif (is_bool($enabled)) {
            $isEnabled = $enabled;
        } elseif (is_numeric($enabled)) {
            $isEnabled = ((int) $enabled) === 1;
        }
        
        if (!$isEnabled) {
            // 未启用 Redis，直接返回，使用无缓存模式
            return;
        }

        try {
            if (!extension_loaded('redis')) {
                // Redis 扩展未安装，降级为无缓存模式
                return;
            }
            $redis = new \Redis();
            $host  = Env::get('REDIS_HOST', '127.0.0.1');
            $port  = Env::get('REDIS_PORT', 6379);
            $redis->connect($host, (int) $port, 1.0);
            $this->redis = $redis;
        } catch (\Throwable $e) {
            // 连接失败时降级为无 Redis 模式，避免影响正常请求
            $this->redis = null;
        }
    }

    public static function getInstance(): CacheManager
    {
        if (self::$instance instanceof CacheManager) {
            return self::$instance;
        }

        self::$instance = new CacheManager();
        return self::$instance;
    }

    /**
     * 获取缓存值
     *
     * @param string $key 缓存键
     * @return mixed|null 缓存值，不存在时返回 null
     */
    public function get(string $key)
    {
        if (!$this->redis instanceof \Redis) {
            return null;
        }

        try {
            $value = $this->redis->get($key);
            if ($value === false) {
                return null;
            }
            return unserialize($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 设置缓存值
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值（会自动序列化）
     * @param int $ttl 过期时间（秒），默认 86400（24小时）
     * @return bool 是否设置成功
     */
    public function set(string $key, $value, int $ttl = 86400): bool
    {
        if (!$this->redis instanceof \Redis) {
            return false;
        }

        try {
            $serialized = serialize($value);
            return $this->redis->setex($key, $ttl, $serialized);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 删除缓存
     *
     * @param string $key 缓存键
     * @return bool 是否删除成功
     */
    public function delete(string $key): bool
    {
        if (!$this->redis instanceof \Redis) {
            return false;
        }

        try {
            return $this->redis->del($key) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取 Redis 实例（用于需要直接操作 Redis 的场景）
     *
     * @return \Redis|null Redis 实例，未启用或连接失败时返回 null
     */
    public function getRedis(): ?\Redis
    {
        return $this->redis instanceof \Redis ? $this->redis : null;
    }
}

