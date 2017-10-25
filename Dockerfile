FROM php:5.6-apache
COPY ./ /var/www/html/

RUN  \
		sed -i  's#http[:]//deb[^/ ]\+#http://ftp.cn.debian.org#g' /etc/apt/sources.list  && \
		apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev \
    && docker-php-ext-install -j$(nproc) gd mcrypt


RUN chmod -R 777 /var/www/html/

CMD ["apache2-foreground"]