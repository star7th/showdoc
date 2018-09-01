FROM richarvey/nginx-php-fpm:1.5.3
COPY ./ /var/www/html/

RUN chmod -R 777 /var/www/html/
RUN mkdir /showdoc_data
RUN mkdir /showdoc_data/html
RUN cp -R /var/www/html/ /showdoc_data/
