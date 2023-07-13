#!/usr/bin/env bash

set -xe
if [ ! -d /showdoc_data/html ]; then
    mkdir -p /showdoc_data/html
fi
cp -R /var/www/html/ /showdoc_data/
rm -rf /app
ln -s /var/www/html /app
echo "<?php echo file_get_contents('index.html'); ?>" >/var/www/html/web/index.php
echo "upload_max_filesize=5000M;" >>/opt/docker/etc/php/php.ini
echo "post_max_size=5000M;" >>/opt/docker/etc/php/php.ini
echo "client_max_body_size 5000m;" >/opt/docker/etc/nginx/vhost.common.d/10-general.conf

apk update
apk add --update nodejs npm
mv /showdoc_data/html/mock/ /showdoc_data/mock
(
    cd /showdoc_data/mock/
    npm install
)
