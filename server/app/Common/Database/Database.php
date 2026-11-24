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

        // 默认 SQLite；设置 DB_TYPE=pgsql 使用 PostgreSQL。
        $driver = strtolower(Env::get('DB_TYPE', 'sqlite'));

        if ($driver === 'sqlite') {
            // SQLite 配置（默认）
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
        } elseif ($driver === 'pgsql') {
            // PostgreSQL 配置
            $capsule->addConnection([
                'driver'   => 'pgsql',
                'host'     => Env::get('DB_HOST', '127.0.0.1'),
                'port'     => (int) Env::get('DB_PORT', 5432),
                'database' => Env::get('DB_NAME', 'showdoc'),
                'username' => Env::get('DB_USER', 'showdoc'),
                'password' => Env::get('DB_PWD', ''),
                'charset'  => Env::get('DB_CHARSET', 'utf8'),
                'prefix'   => '',
                'schema'   => Env::get('DB_SCHEMA', 'public'),
                'sslmode'  => Env::get('DB_SSLMODE', 'prefer'),
                'options'  => [
                    // 将所有数字字段返回为字符串，兼容旧前端
                    \PDO::ATTR_STRINGIFY_FETCHES => true,
                ],
            ]);
        } else {
            throw new \RuntimeException('Unsupported DB_TYPE: ' . $driver);
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

