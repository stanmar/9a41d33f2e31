FROM php:latest

RUN apt-get update \
    && apt-get install -y iproute2 \
    && docker-php-ext-install shmop \
    && pecl install xdebug-3.0.0

RUN docker-php-ext-enable xdebug \
    && echo "xdebug.mode=debug" >> ${PHP_INI_DIR}/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host = `/sbin/ip route|awk '/default/ { print $3 }'`" >> ${PHP_INI_DIR}/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=trigger" >> ${PHP_INI_DIR}/conf.d/docker-php-ext-xdebug.ini

VOLUME /var/www/html

RUN chown -R www-data:www-data /var/www/html

USER www-data

WORKDIR /var/www/html