<?php

namespace App\Model;

use App\Common\Cache\CacheManager;
use Illuminate\Database\Capsule\Manager as DB;

class Captcha
{
    /* ==================== 全站登录失败率熔断（防撞库） ====================
     * 动机：撞库通常以大量不同 IP + 不同用户名分布式进行，按 IP/用户名限流均拦不住。
     * 这里统计全站 15 分钟滑动窗口内的「密码错误」次数（不分 IP 不分用户名，天然免疫代理池），
     * 失败总数 ≥500 时验证码从 4 位升级为 6 位，抬高打码成本；
     * 跌破 300 才回落（上 500 下 300 防抖，避免在阈值附近反复抖动）。
     * 注意：只有密码错误计入，验证码错误不计入，防止攻击者故意刷验证码错误把全站带进熔断。
     */
    /** 滑动窗口总时长（秒） */
    const FAIL_WINDOW_SECONDS = 900;
    /** 时间片粒度（秒），窗口 = 15 个 1 分钟片，读时求和 */
    const FAIL_SLICE_SECONDS = 60;
    /** 窗口内全站密码错误总数达到该值 → 验证码升级为 6 位 */
    const FAIL_TRIGGER = 500;
    /** 窗口内全站密码错误总数跌破该值 → 回落为 4 位（防抖下限） */
    const FAIL_RELEASE = 300;
    /** 熔断状态标记的缓存键 */
    const FAIL_FLAG_KEY = 'login_captcha_long_flag';

    /**
     * 记录一次登录密码错误（全站维度，不分 IP 不分用户名）。
     * 按分钟时间片计数：写当前片 +1，读取时对窗口内所有片求和即为滑动窗口总数。
     * Redis 可用时用原子 INCR（高并发下不丢计数），不可用时退化为文件缓存 get/set
     * （极端并发下可能轻微少计，仅影响熔断触发时机，不影响正确性）。
     */
    public static function recordLoginPasswordFailure(): void
    {
        $cache = CacheManager::getInstance();
        $key   = 'login_fail_slice_' . (int) (time() / self::FAIL_SLICE_SECONDS);

        $redis = $cache->getRedis();
        if ($redis !== null) {
            try {
                $redis->incrBy($key, 1);
                $redis->expire($key, self::FAIL_WINDOW_SECONDS + self::FAIL_SLICE_SECONDS);
                return;
            } catch (\Throwable $e) {
                // Redis 异常时退化到文件缓存
            }
        }

        $count = (int) $cache->get($key);
        $cache->set($key, $count + 1, self::FAIL_WINDOW_SECONDS + self::FAIL_SLICE_SECONDS);
    }

    /**
     * 当前应生成的验证码位数：4 位（常态）或 6 位（熔断中）。
     * 带 500/300 防抖：达标升级，跌破下限才回落，中间维持原状态。
     */
    public static function captchaLength(): int
    {
        $cache    = CacheManager::getInstance();
        $tripped  = (bool) $cache->get(self::FAIL_FLAG_KEY);
        $total    = 0;
        $slices   = (int) ceil(self::FAIL_WINDOW_SECONDS / self::FAIL_SLICE_SECONDS);
        $curSlice = (int) (time() / self::FAIL_SLICE_SECONDS);

        $redis = $cache->getRedis();
        for ($i = 0; $i < $slices; $i++) {
            $key = 'login_fail_slice_' . ($curSlice - $i);
            if ($redis !== null) {
                try {
                    $total += (int) $redis->get($key);
                    continue;
                } catch (\Throwable $e) {
                }
            }
            $total += (int) $cache->get($key);
        }

        if (!$tripped && $total >= self::FAIL_TRIGGER) {
            $tripped = true;
            $cache->set(self::FAIL_FLAG_KEY, true, self::FAIL_WINDOW_SECONDS);
        } elseif ($tripped && $total < self::FAIL_RELEASE) {
            $tripped = false;
            $cache->delete(self::FAIL_FLAG_KEY);
        }

        return $tripped ? 6 : 4;
    }

    /**
     * 按旧版 CaptchaModel 逻辑校验验证码。
     * 说明：校验为忽略大小写的字符串比对，与位数无关——4 位或 6 位验证码均按
     * captcha 表中实际存储的答案比对，故熔断升级位数无需改动本方法。
     */
    public static function check(int $captchaId, string $captcha, string $mobile = ''): bool
    {
        $captchaId = (int) $captchaId;
        $captcha   = trim($captcha);

        if ($captchaId <= 0 || $captcha === '') {
            return false;
        }

        $now = time();

        $query = DB::table('captcha')
            ->where('captcha_id', $captchaId)
            ->where('expire_time', '>', $now);

        if ($mobile !== '') {
            $query->where('mobile', $mobile);
        }

        $row = $query->first();

        // 忽略大小写比较验证码内容
        if ($row && isset($row->captcha) && strtolower((string) $row->captcha) === strtolower($captcha)) {
            // 验证成功：立即将验证码置为过期
            DB::table('captcha')
                ->where('captcha_id', $captchaId)
                ->update(['expire_time' => 0]);

            return true;
        }

        // 验证失败：有效期减少 10 秒，防止被暴力枚举
        if ($row && isset($row->expire_time)) {
            $newExpire = max(0, (int) $row->expire_time - 10);
            DB::table('captcha')
                ->where('captcha_id', $captchaId)
                ->update(['expire_time' => $newExpire]);
        }

        return false;
    }
}
