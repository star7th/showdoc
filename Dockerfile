FROM richarvey/nginx-php-fpm:1.5.3
COPY ./ /var/www/html/

RUN chmod -R 777 /var/www/html/
