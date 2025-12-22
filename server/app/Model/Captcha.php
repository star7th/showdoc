<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class Captcha
{
    /**
     * 按旧版 CaptchaModel 逻辑校验验证码。
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
