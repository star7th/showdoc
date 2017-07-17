FROM php:5.6-apache
MAINTAINER xing7th@gmail.com

ENV SHOWDOC_VERSION=1.5.2 \
    SHOWDOC_HOME="/var/www/html"

COPY ./ ${SHOWDOC_HOME}

RUN  \
		sed -i  's#http[:]//deb[^/ ]\+#http://ftp.cn.debian.org#g' /etc/apt/sources.list  && \
		apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng12-dev \
    && docker-php-ext-install -j$(nproc) gd mcrypt


RUN chmod -R 777 ${SHOWDOC_HOME}

CMD ["apache2-foreground"]
