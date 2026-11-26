#!/usr/bin/env bash
# 将旧 SQLite 数据库迁移到当前 PostgreSQL 18 部署。
#
# 用法：
#   ./documentation/scripts/migrate-sqlite-to-pgsql.sh
#   ./documentation/scripts/migrate-sqlite-to-pgsql.sh --force   # 目标已有项目时覆盖
#
# 脚本会：
#   1. 备份 SQLite 文件和目标 PostgreSQL
#   2. 停止 showdoc，避免迁移期间写入
#   3. 在 showdoc 容器里执行 PHP 迁移脚本
#   4. 恢复 showdoc
#
# 只支持 SQLite -> PostgreSQL；不是 web 功能，不新增可匿名访问的迁移接口。

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
FORCE="${1:-}"
SOURCE_HOST="${ROOT}/showdocdata/html/Sqlite/showdoc.db.php"
SOURCE_CONTAINER="/var/www/html/Sqlite/showdoc.db.php"
STAMP="$(date +%Y%m%d-%H%M%S)"

if [[ ! -f "${SOURCE_HOST}" ]]; then
    echo "SQLite source not found: ${SOURCE_HOST}" >&2
    echo "确认旧库在 showdocdata/html/Sqlite/showdoc.db.php" >&2
    exit 2
fi

if [[ "${FORCE}" != "" && "${FORCE}" != "--force" ]]; then
    echo "Unknown argument: ${FORCE}" >&2
    exit 2
fi

if ! docker compose --profile postgres config | grep -q 'DB_TYPE: pgsql'; then
    echo "DB_TYPE is not pgsql. Set DB_TYPE=pgsql in .env before migrating." >&2
    exit 2
fi

mkdir -p "${ROOT}/showdocdata/backup"

echo "Backup SQLite -> showdocdata/backup/sqlite-${STAMP}.db"
cp "${SOURCE_HOST}" "${ROOT}/showdocdata/backup/sqlite-${STAMP}.db"

echo "Start PostgreSQL..."
docker compose --profile postgres up -d postgres >/dev/null

echo "Backup PostgreSQL -> showdocdata/backup/pg-${STAMP}.sql"
docker compose --profile postgres exec -T postgres \
    sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --no-owner --no-privileges' \
    > "${ROOT}/showdocdata/backup/pg-${STAMP}.sql"

echo "Stop showdoc..."
docker compose stop showdoc >/dev/null 2>&1 || true

echo "Migrate SQLite -> PostgreSQL..."
if ! docker compose --profile postgres run --rm --no-deps \
    -v "${ROOT}/documentation/scripts:/var/www/html/scripts:ro" \
    -e DB_TYPE=pgsql \
    -e SHOWDOC_ROOT=/var/www/html \
    --entrypoint php showdoc \
    /var/www/html/scripts/migrate-sqlite-to-pgsql.php "${SOURCE_CONTAINER}" "${FORCE}"; then
    echo "Migration failed. showdoc is still stopped; fix the error and re-run." >&2
    exit 1
fi

echo "Start showdoc..."
docker compose start showdoc >/dev/null

echo "Done. Verify /server/index.php?s=/api/ping/index, then keep the backups."
