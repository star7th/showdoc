#!/usr/bin/env php
<?php
/**
 * 将旧 SQLite 数据库迁移到当前 PostgreSQL 18 数据库。
 *
 * 用法（在 showdoc 容器内执行）：
 *   php documentation/scripts/migrate-sqlite-to-pgsql.php /var/www/html/Sqlite/showdoc.db.php --force
 *
 * 不使用 web 页面：迁移是一次性运维操作，放在 CLI 里可以停机、备份、校验，
 * 也不会增加一个可被匿名访问的迁移接口。
 */

declare(strict_types=1);

$root = getenv('SHOWDOC_ROOT') ?: dirname(__DIR__);
require $root . '/server/vendor/autoload.php';
require $root . '/server/app/Common/bootstrap.php';

use App\Common\Database\Database;
use App\Common\Database\Upgrade;

$source = $argv[1] ?? ($root . '/Sqlite/showdoc.db.php');
$force  = in_array('--force', $argv, true);

if (!is_file($source)) {
    fwrite(STDERR, "SQLite source not found: {$source}\n");
    exit(2);
}

if (!Upgrade::checkAndUpgrade()) {
    fwrite(STDERR, "PostgreSQL schema init failed\n");
    exit(2);
}

$pg = Database::getInstance()->getConnection()->getPdo();
$pg->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqlite = new PDO('sqlite:' . $source, null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

function ident(string $name): string
{
    return '"' . str_replace('"', '""', $name) . '"';
}

function scalar(PDO $pdo, string $sql): int
{
    return (int) $pdo->query($sql)->fetchColumn();
}

function convertValue($value, string $type)
{
    if ($value === null) {
        return null;
    }

    $type = strtolower($type);

    if (strpos($type, 'timestamp') !== false || $type === 'date') {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return date('Y-m-d H:i:s', (int) $value);
        }
        $v = trim((string) $value);
        if ($v === '') {
            return '1970-01-01 00:00:00';
        }
        $ts = strtotime($v);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : '1970-01-01 00:00:00';
    }

    if (in_array($type, ['bigint', 'integer', 'smallint'], true)) {
        if (is_int($value)) {
            return $value;
        }
        $v = trim((string) $value);
        if ($v === '') {
            return 0;
        }
        if (is_numeric($v)) {
            return (int) $v;
        }
        $ts = strtotime($v);
        return $ts !== false ? $ts : 0;
    }

    if ($type === 'boolean') {
        return ($value === true || $value == 1) ? 1 : 0;
    }

    // 旧库的 BLOB/TEXT 里可能有 NUL，PostgreSQL 的 text/varchar 不接受
    return str_replace("\0", '', (string) $value);
}

$targetTables = $pg->query(
    "SELECT table_name FROM information_schema.tables
     WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
     ORDER BY table_name"
)->fetchAll(PDO::FETCH_COLUMN);

$sourceTables = $sqlite->query(
    "SELECT name FROM sqlite_master
     WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE '<?php%'"
)->fetchAll(PDO::FETCH_COLUMN);

$sourceTables = array_values(array_filter(
    $sourceTables,
    static fn(string $t): bool => strpos($t, 'item_password_bak_') !== 0
));

$targetItemCount = scalar($pg, 'SELECT COUNT(*) FROM ' . ident('item'));
if ($targetItemCount > 0 && !$force) {
    fwrite(STDERR, "PostgreSQL target already has {$targetItemCount} items. Re-run with --force to replace it.\n");
    exit(3);
}

// 目标会被整体替换；脚本在 showdoc 停止后执行，避免并发写入。
foreach ($targetTables as $table) {
    $pg->exec('TRUNCATE TABLE ' . ident($table) . ' RESTART IDENTITY CASCADE');
}

$summary = [];
$skipped = [];

foreach ($sourceTables as $table) {
    if (!in_array($table, $targetTables, true)) {
        $skipped[] = $table . ' (target table missing)';
        continue;
    }

    $colStmt = $pg->prepare(
        "SELECT column_name, data_type FROM information_schema.columns
         WHERE table_schema = 'public' AND table_name = ? ORDER BY ordinal_position"
    );
    $colStmt->execute([$table]);
    $targetColumns = $colStmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$targetColumns) {
        continue;
    }

    $sourceInfo = $sqlite->query('PRAGMA table_info(' . ident($table) . ')')->fetchAll(PDO::FETCH_ASSOC);
    $sourceColumnNames = array_column($sourceInfo, 'name');
    $targetColumnNames = array_column($targetColumns, 'column_name');
    $columns = array_values(array_intersect($targetColumnNames, $sourceColumnNames));

    if (!$columns) {
        $skipped[] = $table . ' (no common columns)';
        continue;
    }

    $types = [];
    foreach ($targetColumns as $column) {
        $types[$column['column_name']] = $column['data_type'];
    }

    $select = 'SELECT ' . implode(', ', array_map('ident', $columns)) . ' FROM ' . ident($table);
    $rows = $sqlite->query($select);

    $insert = 'INSERT INTO ' . ident($table)
        . ' (' . implode(', ', array_map('ident', $columns)) . ')'
        . ' VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')';
    $stmt = $pg->prepare($insert);

    $count = 0;
    $pg->beginTransaction();
    try {
        foreach ($rows as $row) {
            $params = [];
            foreach ($columns as $column) {
                $params[] = convertValue($row[$column], $types[$column]);
            }
            $stmt->execute($params);
            $count++;
        }
        $pg->commit();
    } catch (Throwable $e) {
        $pg->rollBack();
        fwrite(STDERR, "Migration failed at table {$table}: {$e->getMessage()}\n");
        exit(4);
    }

    $summary[$table] = $count;
}

// 旧 options 里可能没有新版本的 db_version_num；迁移后固定写成当前版本
$pg->prepare(
    "INSERT INTO options (option_name, option_value) VALUES ('db_version_num', '36')
     ON CONFLICT (option_name) DO UPDATE SET option_value = EXCLUDED.option_value"
)->execute();

// 显式导入 ID 后，identity 序列必须对齐，否则下一次自增会撞主键
$seqStmt = $pg->query(
    "SELECT table_name, column_name FROM information_schema.columns
     WHERE table_schema = 'public' AND is_identity = 'YES'"
);
foreach ($seqStmt->fetchAll(PDO::FETCH_ASSOC) as $seq) {
    $table  = $seq['table_name'];
    $column = $seq['column_name'];
    $max    = scalar($pg, 'SELECT COALESCE(MAX(' . ident($column) . '), 0) FROM ' . ident($table));
    $seqName = $pg->query(
        'SELECT pg_get_serial_sequence(' . $pg->quote('public.' . $table) . ', ' . $pg->quote($column) . ')'
    )->fetchColumn();

    if ($seqName) {
        $pg->query(
            'SELECT setval(' . $pg->quote($seqName) . ', ' . max(1, $max) . ', ' . ($max > 0 ? 'true' : 'false') . ')'
        );
    }
}

echo "Migration complete.\n";
foreach ($summary as $table => $count) {
    $sourceCount = scalar($sqlite, 'SELECT COUNT(*) FROM ' . ident($table));
    $targetCount = scalar($pg, 'SELECT COUNT(*) FROM ' . ident($table));
    $flag = ($table === 'options' || $sourceCount === $targetCount) ? 'ok' : 'MISMATCH';
    echo sprintf("  %-32s source=%-8d target=%-8d %s\n", $table, $sourceCount, $targetCount, $flag);
}
if ($skipped) {
    echo "Skipped:\n  " . implode("\n  ", $skipped) . "\n";
}
echo "PostgreSQL db_version_num=36, identity sequences synchronized.\n";
