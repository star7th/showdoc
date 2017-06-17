#!/bin/sh
storageDir=/var/www/html/Sqlite
test ! -d $storageDir && mkdir -p $storageDir
test ! -f "$storageDir"
#o=$(ls -ld $storageDir/showdoc.db.php | awk 'NR==1 {print $3}')

if [ !  -f  $storageDir/showdoc.db.php ] ; then
   cp /var/www/html/showdoc.db.php $storageDir/
fi

test -z "${WWW_USER}" && WWW_USER=www-data
if [ ! "${WWW_USER}" = "NOP" ] ; then
 echo "change owner to ${WWW_USER}"
 cd /var/www/html 
 chown  ${WWW_USER} install 
 chown -R ${WWW_USER} Sqlite  Public/Uploads  Application/Runtime  server/Application/Runtime \
  Application/Common/Conf/config.php  Application/Home/Conf/config.php
fi
apache2-foreground $*
