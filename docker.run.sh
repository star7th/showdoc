#!/usr/bin/env bash

## web site dir
showdoc_dir='/showdoc_data'
showdoc_dir_old='/showdoc_data_old'
showdoc_dir_old_skip='/showdoc_data_old/.skip_old'
showdoc_html_dir="$showdoc_dir/html"
web_dir='/var/www/html'
file_ver=$web_dir/.ver
# file_ver_json=$web_dir/.json.ver
file_json=$showdoc_html_dir/composer.json

db_file=$web_dir/Sqlite/showdoc.db.php

## 首次启动需要 copy to /var/www/html
if [ -f "$web_dir/index.php" ]; then
    echo "Found $web_dir/index.php, skip copy."
else
    echo "Not found $web_dir/index.php, copy..."
    ## 兼容历史版本 宿主机 /showdoc_data
    if [[ -f $showdoc_dir_old/html/index.php && ! -f $showdoc_dir_old_skip ]]; then
        echo "Found old version of \"showdoc_data\", copy..."
        rsync -a $showdoc_dir_old/html/ $web_dir/ &&
            touch $showdoc_dir_old_skip
    else
        rsync -a $showdoc_html_dir/ $web_dir/
    fi
fi
## upgrade (通过 Dockerfile 的环境变量 变更版本)
## upgrade (通过 composer.json "version" 变更版本)
version_json=$(grep -o '"version":.*"' $file_json | awk '{print $2}')
version_json="${version_json//\"/}"
if [ -f $file_ver ]; then
    # if [[ "$SHOWDOC_DOCKER_VERSION" == "$(cat $file_ver)" ]]; then
    if [[ "${version_json}" == "$(cat $file_ver)" ]]; then
        echo "Same version, skip upgrade."
    else
        echo "Backup db file before upgrade..."
        \cp -av $db_file ${db_file}."$(date +%F-%H-%M-%S)".php
        echo "Upgrade application files..."
        ## 此处不同步 db 文件和 upload 文件，自动排除
        rsync -a --exclude='Sqlite/' --exclude='Public/Uploads/' $showdoc_html_dir/ $web_dir/
        ## revert lang if lang=en
        if grep -q 'lang:.*en' $web_dir/web/index.html; then
            sed -i -e "/lang:.*zh-cn.*/s//lang: 'zh-cn'/" $web_dir/web/index.html $web_dir/web_src/index.html
        fi
    fi
else
    # echo "$SHOWDOC_DOCKER_VERSION" >$file_ver
    echo "$version_json" >$file_ver
fi
## fix file permission
# find $web_dir -type f -exec chmod 644 {} \;
# find $web_dir -type d -exec chmod 755 {} \;
# find $web_dir -type f -iname '*.sh' -exec chmod 755 {} \;
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
while [ -f $db_file ]; do
    # backup on 20:01 (UTC) every day
    if [[ $(date +%H%M) == 2001 ]]; then
        \cp $db_file ${db_file}."$(date +%F-%H-%M-%S)".php
        ## remove old files (15 days ago)
        find ${db_file}.* -type f -ctime +15 -print0 |
            xargs -t -0 rm -f >/dev/null
        sleep 2
    fi
    sleep 58
done &
pids="$pids $!"

(
    sleep 3
    cd $showdoc_html_dir/server || exit 1
    php index.php /api/update/dockerUpdateCode
)
(
    echo "delay 30s start mock..."
    sleep 30
    cd $showdoc_dir/mock/ || exit 1
    npm run start
) &
pids="$pids $!"

supervisord -c /opt/docker/etc/supervisor.conf &
pids="$pids $!"

## 识别中断信号，停止进程
trap _kill HUP INT QUIT TERM

wait
