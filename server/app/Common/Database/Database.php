<?php

namespace App\Common\Database;

use App\Common\Helper\Env;
use Illuminate\Database\Capsule\Manager as Capsule;

class Database
{
    private static ?Capsule $capsule = null;

    public static function getInstance(): Capsule
    {
        if (self::$capsule instanceof Capsule) {
            return self::$capsule;
        }

        $capsule = new Capsule();

        // 开源版默认使用 SQLite，可通过环境变量 DB_TYPE=mysql 切换到 MySQL（可选）
        $driver = strtolower(Env::get('DB_TYPE', 'sqlite'));

        if ($driver === 'sqlite') {
            // SQLite 配置（开源版主要使用）
            $dbPath = Env::get('DB_NAME', __DIR__ . '/../../../../Sqlite/showdoc.db.php');
            $capsule->addConnection([
                'driver'   => 'sqlite',
                'database' => $dbPath,
                'prefix'   => '',
                'options'  => [
                    // 将所有数字字段返回为字符串，兼容旧前端
                    \PDO::ATTR_STRINGIFY_FETCHES => true,
                ],
            ]);
        } else {
            // MySQL 配置（可选，兼容性）
            $capsule->addConnection([
                'driver'    => 'mysql',
                'host'      => Env::get('DB_HOST', '127.0.0.1'),
                'port'      => (int) Env::get('DB_PORT', 3306),
                'database'  => Env::get('DB_NAME', 'showdoc'),
                'username'  => Env::get('DB_USER', 'root'),
                'password'  => Env::get('DB_PWD', ''),
                'charset'   => Env::get('DB_CHARSET', 'utf8mb4'),
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'options'   => [
                    // 将所有数字字段返回为字符串，兼容旧前端
                    \PDO::ATTR_STRINGIFY_FETCHES => true,
                ],
            ]);
        }

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // SQLite 并发优化：锁竞争时按序等待，而非立即抛 SQLITE_BUSY。
        // PDO SQLite 驱动默认 busy_timeout 即为 60s（php-src 写死 60000ms），
        // 这里显式设置同值以保持行为可控、不依赖驱动隐式默认值。
        // busy_timeout 是连接级参数，PHP-FPM 每个请求新建连接，须在连接建立时设置。
        if ($driver === 'sqlite') {
            try {
                $capsule->getConnection()->statement('PRAGMA busy_timeout = 60000');
            } catch (\Throwable $e) {
                // PRAGMA 失败不应阻断应用启动
                error_log('SQLite busy_timeout setup failed: ' . $e->getMessage());
            }
        }

        self::$capsule = $capsule;

        return self::$capsule;
    }
}

