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
        $host = Env::get('REDIS_HOST', '');
        if ($host === '') {
            return;
        }

        try {
            if (!extension_loaded('redis')) {
                return;
            }
            $redis = new \Redis();
            $host  = Env::get('REDIS_HOST', '127.0.0.1');
            $port  = Env::get('REDIS_PORT', 6379);
            $redis->connect($host, (int) $port, 1.0);
            $this->redis = $redis;
        } catch (\Throwable $e) {
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

    public function get(string $key)
    {
        if ($this->redis instanceof \Redis) {
            try {
                $value = $this->redis->get($key);
                if ($value !== false) {
                    return json_decode($value, true);
                }
            } catch (\Throwable $e) {
            }
        }

        return $this->fileGet($key);
    }

    public function set(string $key, $value, int $ttl = 86400): bool
    {
        if ($this->redis instanceof \Redis) {
            try {
                $serialized = json_encode($value, JSON_UNESCAPED_UNICODE);
                if ($this->redis->setex($key, $ttl, $serialized)) {
                    return true;
                }
            } catch (\Throwable $e) {
            }
        }

        return $this->fileSet($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        $result = false;

        if ($this->redis instanceof \Redis) {
            try {
                $result = $this->redis->del($key) > 0;
            } catch (\Throwable $e) {
            }
        }

        $fileResult = $this->fileDelete($key);

        return $result || $fileResult;
    }

    public function isEnabled(): bool
    {
        return $this->redis instanceof \Redis;
    }

    public function getRedis(): ?\Redis
    {
        return $this->redis instanceof \Redis ? $this->redis : null;
    }

    private static function getCacheDir(): string
    {
        $runtimePath = defined('RUNTIME_PATH') ? RUNTIME_PATH : dirname(__DIR__, 2) . '/Runtime/';
        $dir = $runtimePath . 'cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private static function getCachePath(string $key): string
    {
        return self::getCacheDir() . DIRECTORY_SEPARATOR . md5($key) . '.json';
    }

    private function fileGet(string $key)
    {
        $file = self::getCachePath($key);
        if (!file_exists($file)) {
            return null;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['expire_at'], $data['value'])) {
            return null;
        }

        if ($data['expire_at'] > 0 && $data['expire_at'] <= time()) {
            @unlink($file);
            return null;
        }

        return $data['value'];
    }

    private function fileSet(string $key, $value, int $ttl): bool
    {
        $file = self::getCachePath($key);
        $data = [
            'expire_at' => $ttl > 0 ? time() + $ttl : 0,
            'value'     => $value,
        ];
        return @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE)) !== false;
    }

    private function fileDelete(string $key): bool
    {
        $file = self::getCachePath($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return false;
    }
}
