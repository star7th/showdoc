#!/usr/bin/env bash

set -xe

rm -rf /app
ln -sf /var/www/html /app
## php setting
(
    echo "upload_max_filesize=5120M;"
    echo "post_max_size=5120M;"
) >>/opt/docker/etc/php/php.ini
echo "client_max_body_size 5120m;" >/opt/docker/etc/nginx/vhost.common.d/10-general.conf

## fix nginx warning
sed -i -e '1d' /opt/docker/etc/nginx/vhost.ssl.conf
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
apk add --update --no-cache nodejs npm

showdoc_dir='/showdoc_data'
mv $showdoc_dir/html/mock $showdoc_dir/
cd $showdoc_dir/mock || exit 1
if [ "$IN_CHINA" = true ]; then
    npm config set registry https://registry.npmmirror.com/
fi
## fix old warn
rm -f package-lock.json
npm install
