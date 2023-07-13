#!/usr/bin/env bash

set -e

if [ ! -f "/var/www/html/index.php" ]; then
    \cp -fr /showdoc_data/html/ /var/www/
fi

chmod 777 -R /var/www/
(
    sleep 3
    cd /showdoc_data/html/server
    php index.php /api/update/dockerUpdateCode
    chmod 777 -R /var/www/
)
(
    sleep 30
    cd /showdoc_data/mock/
    npm run start
)

supervisord
