#!/usr/bin/env bash

set -e

if [ ! -f "/var/www/html/index.php" ]; then
    \cp -fr /showdoc_data/html/* /var/www/html/
fi

## backup sqlite file every day
db_file=/var/www/html/Sqlite/showdoc.db.php
while [ -f $db_file ]; do
    if [[ $(date +%H%M) == 0401 ]]; then
        \cp $db_file ${db_file}."$(date +%F-%H-%M-%S)"
    fi
    # sleep 86400
    sleep 50
done &

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
