<?php

namespace App\Common\Helper;

/**
 * 统一环境变量读取工具，优先 .env / $_ENV / $_SERVER，最后退回 getenv()。
 *
 * 注意：不依赖旧的 Application/Common/Common/function.php 里的 env()，
 * 以免和 ThinkPHP 的实现绑死逻辑。
 */
class Env
{
    public static function get(string $key, $default = null)
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        $val = getenv($key);
        return $val !== false ? $val : $default;
    }
}

