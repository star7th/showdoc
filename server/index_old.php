<?php

// 当php版本达到composer包要求的版本时候，则require autoload
// vendor/composer/platform_check.php 里面可以看到composer包要求的最低版本
define('COMPOSER_PHP_VERSION', '7.2.5');
if (version_compare(PHP_VERSION, COMPOSER_PHP_VERSION, '>')) {
    // 增加自动加载
    require './vendor/autoload.php';
}

// cookie安全，给PHPSESSID加上Strict
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params(array(
        'samesite' => 'Strict'
    ));
} else {
    session_set_cookie_params(
        NULL,
        '/; samesite=Strict'
    );
}

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG', True);

// 定义应用目录
define('APP_PATH', './Application/');

// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';
