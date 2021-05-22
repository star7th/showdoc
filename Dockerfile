FROM  webdevops/php-nginx:7.4-alpine

COPY ./ /var/www/html/
RUN mkdir -p /showdoc_data/html
RUN cp -R /var/www/html/ /showdoc_data/
RUN rm -rf /app && ln -s /var/www/html /app
RUN echo "<?php echo file_get_contents('index.html'); ?>" > /var/www/html/web/index.php

RUN echo "upload_max_filesize=5000M;" >> /opt/docker/etc/php/php.ini
RUN echo "post_max_size=5000M;" >> /opt/docker/etc/php/php.ini
RUN echo "client_max_body_size 5000m;" > /opt/docker/etc/nginx/vhost.common.d/10-general.conf

RUN apk update
RUN apk add --update nodejs nodejs-npm
RUN mv /showdoc_data/html/mock/ /showdoc_data/mock
RUN (cd /showdoc_data/mock/ && npm install )

# 写环境变量
ENV SHOWDOC_DOCKER_VERSION 2.0

CMD if [ ! -f "/var/www/html/index.php" ]; then \cp -fr /showdoc_data/html/ /var/www/ ;fi;chmod 777 -R /var/www/ ;(sleep 30 && cd /showdoc_data/mock/ && npm run start) & supervisord
