<?php

namespace App\Common\Helper;

class IpHelper
{
    /**
     * 获取客户端 IP，逻辑参考旧版 getIPaddress()。
     */
    public static function getClientIp(): string
    {
        $ip = '';

        if (!empty($_SERVER)) {
            if (!empty($_SERVER['HTTP_X_ORIGINAL_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_ORIGINAL_FORWARDED_FOR'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $ip = getenv('HTTP_CLIENT_IP');
            } else {
                $ip = getenv('REMOTE_ADDR') ?: '';
            }
        }

        // 如果存在逗号，取第一个
        $parts = explode(',', $ip);
        $ip    = trim($parts[0] ?? '');

        return $ip;
    }
}
