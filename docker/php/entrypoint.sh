#!/bin/sh
# Точка входа контейнера app: готовит код, смонтированный с хоста, и запускает php-fpm.
# Зависимости ставятся здесь, а не в образе: каталог проекта монтируется поверх /var/www
# и скрыл бы vendor из образа.
set -e
cd /var/www

[ -f .env ] || cp .env.example .env

app-composer-install

if ! grep -q '^APP_KEY=base64' .env; then
    php artisan key:generate --force
fi

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ugo+rwX storage bootstrap/cache

exec "$@"
