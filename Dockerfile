FROM richarvey/nginx-php-fpm:1.5.3
COPY ./ /var/www/html/

RUN apk update
RUN apk add openldap-dev
RUN docker-php-ext-install ldap

RUN chmod -R 777 /var/www/html/
RUN mkdir /showdoc_data
RUN mkdir /showdoc_data/html
RUN cp -R /var/www/html/ /showdoc_data/
