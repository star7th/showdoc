<?php

/**
 * 爬虫检测工具类
 * 用于检测和拦截各种搜索引擎爬虫和恶意爬虫工具
 *
 * 使用方法:
 * use App\Common\BotDetector;
 * if (BotDetector::isBot()) {
 *     // 拦截处理
 * }
 */
namespace App\Common;

class BotDetector
{
    /**
     * 检测当前请求是否为爬虫
     *
     * @return bool 如果是爬虫返回true，否则返回false
     */
    public static function isBot()
    {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';

        // 常见搜索引擎爬虫User-Agent特征列表
        // 注意: 不拦截 curl、wget、python、java 等，避免影响正常的API调用
        $botPatterns = [
            // 百度系列（重点拦截 - 完整列表）
            'baiduspider',              // 百度通用爬虫
            'baiduspider/2.0',          // 百度爬虫2.0版本
            'baiduspider-render',       // 百度渲染爬虫(支持JS)
            'baiduspider-render/2.0',   // 百度渲染爬虫2.0
            'baiduspider-mobile',       // 百度移动端爬虫
            'baiduspider-image',        // 百度图片爬虫
            'baiduspider-image+',       // 百度图片爬虫变体
            'baiduspider-video',        // 百度视频爬虫
            'baiduspider-ads',          // 百度广告爬虫
            'baiduspider-cpro',         // 百度广告联盟爬虫
            'baiduspider-jp',           // 百度日语版爬虫
            'baiduspider+',             // 百度爬虫变体
            'baiduspider, anonymous',   // 百度匿名爬虫
            'baiduspider-feed',         // 百度信息流爬虫
            'baiduspider-fisheye',      // 百度全景爬虫
            'baiduspider-link',         // 百度链接爬虫
            // 谷歌系列
            'googlebot',
            'googlebot-mobile',
            'googlebot-image',
            'mediapartners-google',
            // 搜狗系列
            'sogou web spider',
            'sogou push spider',
            'sogou-',
            // 360系列
            '360spider',
            '360spider-',
            // 必应系列
            'bingbot',
            'msnbot',
            // 其他搜索引擎爬虫（明确的爬虫标识）
            'yandexbot',
            'duckduckbot',
            'slurp',              // 雅虎爬虫
            'spider',             // 通用爬虫标识（仅包含spider关键词）
            'crawler',            // 通用爬虫标识（仅包含crawler关键词）
            // 恶意爬虫和自动化工具
            'scrapy',             // Scrapy爬虫框架
            'phantomjs',          // 无头浏览器
            'headlesschrome',     // 无头Chrome
            'selenium',           // 自动化测试工具
            'scraper',            // 爬虫工具
            'ahrefsbot',          // SEO工具
            'semrushbot',         // SEO工具
            'mj12bot',            // Majestic爬虫
            'dotbot',             // DotBot爬虫
            'gigabot',            // Gigablast爬虫
            'archive.org_bot',    // 互联网档案馆爬虫
            'ia_archiver',        // 互联网档案馆爬虫
            // 社交媒体爬虫
            'facebookexternalhit', // Facebook爬虫
            'twitterbot',         // Twitter爬虫
            'linkedinbot',        // LinkedIn爬虫
            'embedly',            // Embedly爬虫
            'quora link preview', // Quora爬虫
            'pinterest',          // Pinterest爬虫
            'applebot',           // Apple爬虫
        ];

        // 检查是否匹配任一爬虫特征
        foreach ($botPatterns as $pattern) {
            if (strpos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 记录被拦截的爬虫访问日志
     *
     * @param string|null $userAgent User-Agent字符串
     * @param string|null $ip IP地址
     * @param string|null $uri 请求URI
     * @return void
     */
    public static function logBlockedBot($userAgent = null, $ip = null, $uri = null)
    {
        $logEntry = sprintf(
            "[%s] Blocked bot: %s | IP: %s | UA: %s | URI: %s\n",
            date('Y-m-d H:i:s'),
            $userAgent ?? 'Unknown',
            $ip ?? 'Unknown',
            $userAgent ?? 'Unknown',
            $uri ?? '/'
        );
        // 记录到PHP错误日志
        error_log($logEntry);
    }

    /**
     * 执行爬虫检测并拦截（包含日志记录和403响应）
     *
     * @return void 如果检测到爬虫会终止程序执行
     */
    public static function blockBot()
    {
        if (self::isBot()) {
            // 记录被拦截的爬虫访问日志
            self::logBlockedBot(
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['REQUEST_URI'] ?? null
            );

            // 返回403 Forbidden响应
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            die('Access Denied - 403 Forbidden');
        }
    }
}
