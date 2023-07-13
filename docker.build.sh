#!/usr/bin/env bash

set -xe

rm -rf /app
ln -s /var/www/html /app
echo "upload_max_filesize=5000M;" >>/opt/docker/etc/php/php.ini
echo "post_max_size=5000M;" >>/opt/docker/etc/php/php.ini
echo "client_max_body_size 5000m;" >/opt/docker/etc/nginx/vhost.common.d/10-general.conf
## TODO: fix warning
# /opt/docker/etc/nginx/vhost.conf
# /opt/docker/etc/nginx/vhost.ssl.conf
#

mv /showdoc_data/html/mock /showdoc_data/

if [ "$IN_CHINA" = true ] && [ -f /etc/apk/repositories ]; then
    sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/' /etc/apk/repositories
fi
apk update
apk add --update nodejs npm

cd /showdoc_data/mock || exit 1
if [ "$IN_CHINA" = true ]; then
    npm config set registry https://registry.npm.taobao.org/
fi
npm install
