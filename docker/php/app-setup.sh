#!/bin/sh
# Первичная настройка приложения внутри контейнера app.
# Использование: docker compose exec app app-setup [--demo]
set -e
cd /var/www

[ -f .env ] || cp .env.example .env

echo "→ Зависимости PHP"
app-composer-install

if ! grep -q '^APP_KEY=base64:.\{40,\}' .env; then
    echo "→ Ключ приложения"
    php artisan key:generate --force
fi

echo "→ Ожидание PostgreSQL"
i=0
until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-gym}" >/dev/null 2>&1; do
    i=$((i+1)); [ "$i" -gt 60 ] && { echo "PostgreSQL недоступен"; exit 1; }
    sleep 1
done

echo "→ Миграции и справочники"
php artisan app:install "$@"

echo "→ Ожидание сборки фронтенда (сервис node)"
i=0
until [ -f public/build/manifest.json ]; do
    i=$((i+1)); [ "$i" -gt 300 ] && { echo "Нет public/build/manifest.json — проверьте: docker compose logs node"; break; }
    sleep 1
done

php artisan optimize:clear >/dev/null
chmod -R ugo+rwX storage bootstrap/cache

echo ""
echo "Готово: откройте http://localhost:${APP_PORT:-8080}"
