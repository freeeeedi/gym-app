#!/bin/sh
# Точка входа контейнера app: готовит код, смонтированный с хоста, и запускает php-fpm.
# Зависимости ставятся здесь, а не в образе: каталог проекта монтируется поверх /var/www
# и скрыл бы vendor из образа.
set -e
cd /var/www

[ -f .env ] || cp .env.example .env

app-composer-install

# Ключ: создаём, если его нет или он повреждён (должно быть base64 от 32 байт).
KEY=$(grep '^APP_KEY=' .env | tail -n1 | cut -d= -f2- | tr -d '\r"')
if ! php -r 'exit(strlen(base64_decode(substr($argv[1], 7), true) ?: "") === 32 && str_starts_with($argv[1], "base64:") ? 0 : 1);' "$KEY"; then
    php artisan key:generate --force
fi
# Сбрасываем закешированный конфиг, чтобы подхватились актуальные .env и ключ.
php artisan config:clear >/dev/null

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chmod -R ugo+rwX storage bootstrap/cache

exec "$@"
