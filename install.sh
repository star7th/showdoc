#!/bin/bash

[[ ! -d "${PWD}/showdoc/Sqlite" ]] && mkdir -p ${PWD}/showdoc/Sqlite

[[ ! -f "${PWD}/showdoc/Sqlite/showdoc.db.php" ]] && {
  curl -L https://raw.githubusercontent.com/star7th/showdoc/master/Sqlite/showdoc.db.php \
  -o ${PWD}/showdoc/Sqlite/showdoc.db.php
}

docker ps -a | grep showdoc | awk '{print $1}' | xargs -I {} docker rm -f -v {}

docker run --restart always --name showdoc -p 4999:80 \
  -v ${PWD}/showdoc/Public/Uploads:/var/www/html/Public/Uploads \
  -v ${PWD}/showdoc/Sqlite:/var/www/html/Sqlite \
  -d star7th/showdoc

curl localhost:4999?s=/home/update/db
