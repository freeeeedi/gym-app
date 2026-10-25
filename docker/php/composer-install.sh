#!/bin/sh
# Установка PHP-зависимостей с блокировкой: entrypoint и app-setup не мешают друг другу.
set -e
cd /var/www
exec flock /tmp/composer-install.lock sh -ec '
    if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/autoload.php ]; then
        echo "→ composer install"
        composer install --no-interaction --prefer-dist --no-progress --optimize-autoloader
        touch vendor/autoload.php
    fi
'
