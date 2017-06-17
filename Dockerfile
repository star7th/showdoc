FROM php:5.6-apache
RUN \
sed -i  's#http[:]//deb[^/ ]\+#http://ftp.cn.debian.org#g' /etc/apt/sources.list  && \
trubo=libjpeg62-turbo-dev && \
#trubo=libjpeg-turbo8-dev && \
 apt-get update && apt-get install -y \
        libfreetype6-dev \
        $trubo  \
        libmcrypt-dev \
        libpng12-dev \
    && docker-php-ext-install -j$(nproc) gd mcrypt  && \
  apt-get clean && rm -rf /var/lib/apt/lists/*

COPY ./ /var/www/html/

RUN \
cd /var/www/html/  && \
cp ./Sqlite/showdoc.db.php ./showdoc.db.php && \
chown www-data install && \
chown -R www-data  Sqlite  Public/Uploads  Application/Runtime  server/Application/Runtime \
 Application/Common/Conf/config.php  Application/Home/Conf/config.php && \
 ln -s $(pwd)/startApp.sh /usr/bin/startApp.sh && \
 chmod -R  700  composer.json docker-compose.yml Dockerfile startApp.sh 

VOLUME [ "/var/www/html/Sqlite","/var/www/html/install","/var/www/html/Public/Uploads" \
	,"/var/www/html/Application/Runtime","/var/www/html/server/Application/Runtime" \
       ,"/var/www/html/Application/Common/Conf","/var/www/html/Application/Home/Conf" ]

CMD ["startApp.sh"]