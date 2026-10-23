FROM php:8.3-fpm-alpine

RUN apk add --no-cache icu-libs libzip libpq postgresql-client git unzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev postgresql-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip opcache bcmath \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/app-setup.sh /usr/local/bin/app-setup
COPY docker/php/composer-install.sh /usr/local/bin/app-composer-install
COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
# Скрипты могли прийти с CRLF (git на Windows) — приводим к LF.
RUN sed -i 's/\r$//' /usr/local/bin/app-setup /usr/local/bin/app-composer-install /usr/local/bin/app-entrypoint \
    && chmod +x /usr/local/bin/app-setup /usr/local/bin/app-composer-install /usr/local/bin/app-entrypoint

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1
WORKDIR /var/www

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]
