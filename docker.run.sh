#!/usr/bin/env bash

_docker_build() {
    set -xe
    rm -rf /app
    ln -sf $web_dir /app
    ## php setting
    (
        echo "upload_max_filesize=5120M;"
        echo "post_max_size=5120M;"
    ) >>/opt/docker/etc/php/php.ini
    (
        echo "client_max_body_size 5120m;"
    ) >/opt/docker/etc/nginx/vhost.common.d/10-general.conf

    ## fix nginx warning
    sed -i -e '1 s/^/#/' /opt/docker/etc/nginx/vhost.ssl.conf
    sed -i -e '/443\ default_server/s//443\ default_server\ ssl/' /opt/docker/etc/nginx/vhost.conf

    ## disable service
    mv /opt/docker/etc/supervisor.d/cron.conf{,.bak}
    mv /opt/docker/etc/supervisor.d/dnsmasq.conf{,.bak}
    # mv /opt/docker/etc/supervisor.d/nginx.conf{,.bak}
    # mv /opt/docker/etc/supervisor.d/php-fpm.conf{,.bak}
    mv /opt/docker/etc/supervisor.d/postfix.conf{,.bak}
    mv /opt/docker/etc/supervisor.d/ssh.conf{,.bak}
    mv /opt/docker/etc/supervisor.d/syslog.conf{,.bak}

    ## mirror in china
    if [ "$IN_CHINA" = true ] && [ -f /etc/apk/repositories ]; then
        sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/' /etc/apk/repositories
    fi
    apk update
    apk add --update --no-cache nodejs npm sqlite sqlite-dev

    mv $showdoc_dir_html/mock $showdoc_dir/
    cd $showdoc_dir/mock || exit 1
    if [ "$IN_CHINA" = true ]; then
        npm config set registry https://registry.npmmirror.com/
    fi
    ## fix old warn
    # rm -f package-lock.json

    npm install
}

_kill() {
    echo "receive SIGTERM, kill ${pids[*]}"
    for pid in "${pids[@]}"; do
        kill "$pid"
        wait "$pid"
    done
}

_backup_dbfile() {
    if [[ "${IN_CHINA}" == true ]]; then
        backup_time="$(TZ='Asia/Shanghai' date +%F-%H-%M-%S)"
    else
        backup_time="$(date +%F-%H-%M-%S)"
    fi
    \cp $db_file ${db_file}.backup.full."$backup_time".php
    ## remove old files (15 days ago)
    find ${db_file}.* -type f -ctime +15 -print0 |
        xargs -t -0 rm -f >/dev/null
}

_docker_run() {
    pids=()
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
            rsync -a $showdoc_dir_html/ $web_dir/
        fi
    fi
    ## upgrade (通过 Dockerfile 的环境变量 变更版本)
    ## upgrade (通过 composer.json "version" 变更版本)
    ver_file=$web_dir/.ver
    # ver_file_json=$web_dir/.json.ver
    json_file=$showdoc_dir_html/composer.json
    version_json=$(grep -o '"version":.*"' $json_file | awk '{split($2,a,"\""); print a[2]}')
    # version_json=$(grep -o '"version":.*"' $json_file | awk '{print substr($2,2,6)}')
    if [ -f $ver_file ]; then
        # if [[ "$SHOWDOC_DOCKER_VERSION" == "$(cat $ver_file)" ]]; then
        if [[ "${version_json}" == "$(cat $ver_file)" ]]; then
            echo "Same version, skip upgrade."
        else
            echo "Backup db file before upgrade..."
            _backup_dbfile
            echo "Upgrade application files..."
            ## 此处不同步 db 文件和 upload 文件，自动排除
            rsync -a --exclude='Sqlite/' --exclude='Public/Uploads/' $showdoc_dir_html/ $web_dir/
            ## revert lang if lang=en
            if grep -q 'lang:.*en' $web_dir/web/index.html; then
                sed -i -e "/lang:.*zh-cn.*/s//lang: 'zh-cn'/" $web_dir/web/index.html $web_dir/web_src/index.html
            fi
        fi
    else
        # echo "$SHOWDOC_DOCKER_VERSION" >$ver_file
        echo "$version_json" >$ver_file
    fi
    ## fix file permission
    # find $web_dir -type f -exec chmod 644 {} \;
    # find $web_dir -type d -exec chmod 755 {} \;
    # find $web_dir -type f -iname '*.sh' -exec chmod 755 {} \;
    runtime_dir="$web_dir/server/Application/Runtime"
    [[ -d $runtime_dir ]] || mkdir -p $runtime_dir
    chown -R 1000:1000 \
        $web_dir/Sqlite \
        $web_dir/Public/Uploads \
        $web_dir/install \
        $runtime_dir

    ## backup sqlite file every day
    while [ -f $db_file ]; do
        # backup on (20:01 UTC) (04:01 Asia/Shanghai) every day
        if [[ $(date +%H%M) == 2001 ]]; then
            _backup_dbfile
            sleep 5
        fi
        sleep 55
    done &
    pids+=("$!")

    (
        sleep 3
        cd $showdoc_dir_html/server || exit 1
        php index.php /api/update/dockerUpdateCode
    )
    (
        echo "delay 30s start mock..."
        sleep 30
        cd $showdoc_dir/mock/ || exit 1
        npm run start
    ) &
    pids+=("$!")

    supervisord -c /opt/docker/etc/supervisor.conf &
    pids+=("$!")

    ## 识别中断信号，停止进程
    trap _kill HUP INT QUIT TERM

    wait
}

main() {
    showdoc_dir='/showdoc_data'
    showdoc_dir_old='/showdoc_data_old'
    showdoc_dir_old_skip='/showdoc_data_old/.skip_old'
    showdoc_dir_html="$showdoc_dir/html"
    ## web site dir
    web_dir='/var/www/html'

    db_file=$web_dir/Sqlite/showdoc.db.php

    case $1 in
    -b | --build)
        _docker_build
        ;;
    *)
        _docker_run
        ;;
    esac
}

main "$@"
