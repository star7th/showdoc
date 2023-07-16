#!/usr/bin/env bash

set -e

## web site dir
showdoc_dir='/showdoc_data'
showdoc_html_dir="$showdoc_dir/html"
web_dir='/var/www/html'
file_ver=$web_dir/.ver
if [ -f "$web_dir/index.php" ]; then
    echo "Found $web_dir/index.php, skip copy."
else
    echo "Not found $web_dir/index.php, copy..."
    rsync -a $showdoc_html_dir/ $web_dir/
fi
if [ -f $file_ver ]; then
    if [[ "$SHOWDOC_DOCKER_VERSION" == "$(cat $file_ver)" ]]; then
        echo "Same version, skip upgrade."
    else
        echo "Upgrade application files..."
        rsync -a --exclude='Sqlite/' --exclude='Public/Uploads/' $showdoc_html_dir/ $web_dir/
    fi
else
    echo "$SHOWDOC_DOCKER_VERSION" >$file_ver
fi
## set file mode
[[ -d $web_dir/server/Application/Runtime ]] ||
    mkdir -p $web_dir/server/Application/Runtime
chown -R 1000:1000 \
    $web_dir/Sqlite \
    $web_dir/Public/Uploads \
    $web_dir/install \
    $web_dir/server/Application/Runtime

_kill() {
    echo "receive SIGTERM, kill $pids"
    for pid in $pids; do
        kill "$pid"
        wait "$pid"
    done
}

## backup sqlite file every day
db_file=$web_dir/Sqlite/showdoc.db.php
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
    cd $showdoc_html_dir/server
    php index.php /api/update/dockerUpdateCode
)
(
    echo "delay 30s start mock..."
    sleep 30
    cd $showdoc_dir/mock/
    npm run start
) &
pids="$pids $!"

supervisord -c /opt/docker/etc/supervisor.conf &
pids="$pids $!"

## 识别中断信号，停止进程
trap _kill HUP INT QUIT TERM

wait
