<?php

namespace App\Common\Helper;

/**
 * HTTP 请求 Helper
 * 封装 http_post、http_get 等 HTTP 请求方法
 */
class HttpHelper
{
    /**
     * HTTP POST 请求
     *
     * @param string $url 请求URL
     * @param array|string $param 请求参数（数组或字符串）
     * @return string|false 返回响应内容，失败返回false
     */
    public static function post(string $url, $param)
    {
        $ch = curl_init();

        // 如果是 HTTPS，跳过证书验证
        if (stripos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        // 处理参数
        if (is_string($param)) {
            $postData = $param;
        } else {
            $postArray = [];
            foreach ($param as $key => $val) {
                $postArray[] = $key . "=" . urlencode($val);
            }
            $postData = join("&", $postArray);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return false;
        }

        return $response;
    }

    /**
     * HTTP GET 请求
     *
     * @param string $url 请求URL
     * @param array $headers 请求头（可选）
     * @param int $timeout 超时时间（秒，默认30）
     * @return string|false 返回响应内容，失败返回false
     */
    public static function get(string $url, array $headers = [], int $timeout = 30)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return false;
        }

        return $response;
    }
}
