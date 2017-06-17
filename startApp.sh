#!/bin/sh
storageDir=/showdoc/Sqlite
test ! -d $storageDir && mkdir -p $storageDir
test ! -f "$storageDir"
#o=$(ls -ld $storageDir/showdoc.db.php | awk 'NR==1 {print $3}')

if [ !  -f  $storageDir/showdoc.db.php ] ; then
   cp /showdoc/showdoc.db.php $storageDir/
fi

test -z "${WWW_USER}" && WWW_USER=www-data
if [ ! "${WWW_USER}" = "NOP" ] ; then
 echo "change owner to ${WWW_USER}"
 cd /showdoc 
 chown  ${WWW_USER} install 
 chown -R ${WWW_USER} Sqlite  Public/Uploads  Application/Runtime  server/Application/Runtime \
  Application/Common/Conf/config.php  Application/Home/Conf/config.php
fi
if [ -n "${CONTEXT_PATH}" ] ; then
  if [ ! "${CONTEXT_PATH}" = "/" ]  ; then
     rm  /var/www/html  && mkdir -p /var/www/html && ln -s /showdoc /var/www/html/${CONTEXT_PATH}
  fi
fi
apache2-foreground $*
