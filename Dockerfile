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

COPY ./ /showdoc/

RUN \
rm -rf /var/www/html && ln -s /showdoc /var/www/html && \
cd /showdoc/  && \
cp ./Sqlite/showdoc.db.php ./showdoc.db.php && \
chown www-data install && \
chown -R www-data  Sqlite  Public/Uploads  Application/Runtime  server/Application/Runtime \
 Application/Common/Conf/config.php  Application/Home/Conf/config.php && \
 ln -s $(pwd)/startApp.sh /usr/bin/startApp.sh && \
 chmod -R  700  composer.json docker-compose.yml Dockerfile startApp.sh 

VOLUME [ "/showdoc/Sqlite","/showdoc/install","/showdoc/Public/Uploads" \
	,"/showdoc/Application/Runtime","/showdoc/server/Application/Runtime" \
       ,"/showdoc/Application/Common/Conf","/showdoc/Application/Home/Conf" ]

CMD ["startApp.sh"]