#!/bin/sh
# Установка PHP-зависимостей с блокировкой: entrypoint и app-setup не мешают друг другу.
set -e
cd /var/www
exec flock /tmp/composer-install.lock sh -c '
    if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/autoload.php ]; then
        echo "→ composer install"
        composer install --no-interaction --prefer-dist --no-progress
        touch vendor/autoload.php
    fi
'
