#!/bin/sh
set -e

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-5432}"

echo "→ Ожидаем PostgreSQL ${DB_HOST}:${DB_PORT} ..."
i=0
until php -r "exit(@fsockopen(getenv('DB_HOST') ?: 'db', (int)(getenv('DB_PORT') ?: 5432)) ? 0 : 1);" 2>/dev/null; do
  i=$((i + 1))
  if [ "$i" -ge 30 ]; then
    echo "✗ PostgreSQL не поднялся за 60 секунд" >&2
    exit 1
  fi
  sleep 2
done
echo "→ PostgreSQL готов."

# Обнаружение пакетов Laravel (в рантайме есть все переменные окружения)
php artisan package:discover --ansi || true

echo "→ Миграции..."
php artisan migrate --force

echo "→ Демо-данные (повторный запуск безопасен)..."
php artisan db:seed --force || echo "⚠ сид завершился с предупреждением — вероятно, данные уже есть"

php artisan storage:link 2>/dev/null || true

# Права на запись для www-data (логи, кэш, сессии)
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "→ Веб-сервер слушает :80 (сайт и /admin)"
exec "$@"
