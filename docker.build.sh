#!/usr/bin/env bash

set -xe

rm -rf /app
ln -s /var/www/html /app
(
    echo "upload_max_filesize=5120M;"
    echo "post_max_size=5120M;"
) >>/opt/docker/etc/php/php.ini
echo "client_max_body_size 5120m;" >/opt/docker/etc/nginx/vhost.common.d/10-general.conf
## fix nginx warning
sed -i -e '1d' /opt/docker/etc/nginx/vhost.ssl.conf
sed -i -e '/443\ default_server/s//443\ default_server\ ssl/' /opt/docker/etc/nginx/vhost.conf

if [ "$IN_CHINA" = true ] && [ -f /etc/apk/repositories ]; then
    sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/' /etc/apk/repositories
fi
apk update
apk add --update --no-cache nodejs npm

mv /showdoc_data/html/mock /showdoc_data/
cd /showdoc_data/mock || exit 1
if [ "$IN_CHINA" = true ]; then
    npm config set registry https://registry.npmmirror.com/
fi
## fix old warn
rm -f package-lock.json
npm install
