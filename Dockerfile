FROM  webdevops/php-nginx:7.4-alpine

# 写环境变量
ENV SHOWDOC_DOCKER_VERSION 2.4

COPY ./ /var/www/html/
RUN bash /var/www/html/docker.build.sh

CMD ["bash", "/var/www/html/docker.run.sh"]
