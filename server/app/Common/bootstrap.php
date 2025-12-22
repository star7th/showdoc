<?php

namespace App\Common;

use App\Common\Database\Database;
use App\Common\Database\Upgrade;
use App\Common\Cache\CacheManager;

// 基础引导文件：后续可在此初始化配置、日志等。

try {
    Database::getInstance();
    CacheManager::getInstance();
    
    // 检查并执行数据库升级（仅在 Web 环境下执行，避免 CLI 任务时重复执行）
    if (PHP_SAPI !== 'cli') {
        Upgrade::checkAndUpgrade();
    }
} catch (\Throwable $e) {
    // 初始化失败时抛出异常，让 Slim 错误处理器处理
    throw new \RuntimeException('Bootstrap initialization failed: ' . $e->getMessage(), 0, $e);
}

