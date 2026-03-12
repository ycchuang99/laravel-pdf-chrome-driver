ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli-alpine

LABEL maintainer="ycchuang99" \
      description="Test environment for laravel-pdf-chrome-driver"

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        linux-headers \
        autoconf \
        g++ \
        make;

RUN pecl install xdebug \
    && docker-php-ext-install sockets \
    && docker-php-ext-enable xdebug

RUN echo "xdebug.mode=off" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["vendor/bin/pest"]
