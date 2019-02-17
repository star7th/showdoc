FROM richarvey/nginx-php-fpm:1.5.7
COPY . /var/www/html/

RUN apk update \
 && apk add --no-cache openldap-dev \
 && docker-php-ext-install ldap
