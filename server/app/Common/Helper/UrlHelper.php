<?php

namespace App\Common\Helper;

use App\Model\Options;

/**
 * URL 生成 Helper（兼容旧 server_url 和 site_url 函数）。
 */
class UrlHelper
{
    /**
     * 获得当前的域名
     *
     * @return string
     */
    public static function getDomain(): string
    {
        // 协议
        $protocol = (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) !== 'off')) ? 'https://' : 'http://';

        // 域名或 IP 地址
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } else {
            // 端口
            $port = '';
            if (isset($_SERVER['SERVER_PORT'])) {
                $port = ':' . $_SERVER['SERVER_PORT'];
                if ((':80' === $port && 'http://' === $protocol) || (':443' === $port && 'https://' === $protocol)) {
                    $port = '';
                }
            }

            if (isset($_SERVER['SERVER_NAME'])) {
                $host = $_SERVER['SERVER_NAME'] . $port;
            } elseif (isset($_SERVER['SERVER_ADDR'])) {
                $host = $_SERVER['SERVER_ADDR'] . $port;
            } else {
                $host = 'localhost';
            }
        }

        return $protocol . $host;
    }

    /**
     * 获得网站的 URL 地址
     *
     * @return string
     */
    public static function siteUrl(): string
    {
        $siteUrl = Options::get('site_url');
        if (!$siteUrl) {
            $siteUrl = self::getDomain() . substr($_SERVER['PHP_SELF'] ?? '/', 0, strrpos($_SERVER['PHP_SELF'] ?? '/', '/'));
            $siteUrl = str_replace('/server', '', $siteUrl);
        }
        // 确保返回的 URL 末尾没有斜杠，避免拼接时出现双斜杠
        return rtrim((string) $siteUrl, '/');
    }

    /**
     * 拼接后台 server 链接（开源版：使用 ?s=/api/... 兼容旧入口）
     *
     * @param string $path 路径
     * @param array $params 参数
     * @return string
     */
    public static function serverUrl(string $path = '', array $params = []): string
    {
        // 移除路径开头的斜杠，避免出现 //api
        $path = ltrim($path, '/');
        $base = self::siteUrl() . '/server/';

        // 开源版没有 nginx 重写规则，统一走 ?s=/api/... 入口
        // 这里保持 s 参数中的斜杠不被 urlencode（兼容老格式：?s=/api/attachment/visitFile）
        $queryStringParts = [];

        if ($path !== '') {
            $queryStringParts[] = 's=/' . $path;
        }

        if (!empty($params)) {
            // 其他参数正常使用 http_build_query 编码
            $queryStringParts[] = http_build_query($params);
        }

        if (empty($queryStringParts)) {
            return rtrim($base, '/');
        }

        return $base . '?' . implode('&', $queryStringParts);
    }
}
