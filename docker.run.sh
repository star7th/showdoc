#!/usr/bin/env bash

set -e

## web site dir
if [ ! -f "/var/www/html/index.php" ]; then
    \cp -fr /showdoc_data/html/* /var/www/html/
fi
## set file mode
chown -R 1000:1000 /var/www/

_kill() {
    echo "receive SIGTERM, kill $pids"
    for pid in $pids; do
        kill "$pid"
        wait "$pid"
    done
}

## 识别中断信号，停止进程
trap _kill HUP INT QUIT TERM

## backup sqlite file every day
db_file=/var/www/html/Sqlite/showdoc.db.php
while [ -f $db_file ]; do
    # backup on 20:01 (UTC) every day
    if [[ $(date +%H%M) == 2001 ]]; then
        \cp $db_file ${db_file}."$(date +%F-%H-%M-%S)".php
        ## remove old files (15 days ago)
        find ${db_file}.* -type f -ctime +15 -print0 |
            xargs -t -0 rm -f >/dev/null
    fi
    sleep 50
done &
pids="$pids $!"
(
    sleep 3
    cd /showdoc_data/html/server
    php index.php /api/update/dockerUpdateCode
    chown -R 1000:1000 /var/www/
)
(
    echo "delay 30s start mock..."
    sleep 30
    cd /showdoc_data/mock/
    npm run start
) &
pids="$pids $!"

supervisord -c /opt/docker/etc/supervisor.conf &
pids="$pids $!"

wait

