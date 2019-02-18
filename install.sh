#!/bin/bash

[[ ! -d "${PWD}/showdoc/Conf" ]] && mkdir -p ${PWD}/showdoc/Conf
[[ ! -f "${PWD}/showdoc/Conf/config.php" ]] && {
  curl -L https://raw.githubusercontent.com/star7th/showdoc/master/server/Application/Home/Conf/config.php \
  -o ${PWD}/showdoc/Conf/config.php
}
[[ ! -f "${PWD}/showdoc/Conf/tags.php" ]] && {
  curl -L https://raw.githubusercontent.com/star7th/showdoc/master/server/Application/Home/Conf/tags.php \
  -o ${PWD}/showdoc/Conf/tags.php
}

[[ ! -d "${PWD}/showdoc/Sqlite" ]] && mkdir -p ${PWD}/showdoc/Sqlite
[[ ! -f "${PWD}/showdoc/Sqlite/showdoc.db.php" ]] && {
  curl -L https://raw.githubusercontent.com/star7th/showdoc/master/Sqlite/showdoc.db.php \
  -o ${PWD}/showdoc/Sqlite/showdoc.db.php
}

docker ps -a | grep showdoc | awk '{print $1}' | xargs -I {} docker rm -f -v {}

docker run --restart always --name showdoc -p 4999:80 \
  -v ${PWD}/showdoc/Conf:/var/www/html/server/Application/Home/Conf \
  -v ${PWD}/showdoc/Sqlite:/var/www/html/Sqlite \
  -v ${PWD}/showdoc/Uploads:/var/www/html/Public/Uploads \
  -d star7th/showdoc

sleep 5

curl localhost:4999?s=/home/update/db
