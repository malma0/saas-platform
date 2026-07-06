# syntax=docker/dockerfile:1
#
# Теннис Клуб НСК — образ для демо-деплоя.
# Self-contained: nginx + php-fpm под supervisor, отдаёт сайт и админку MoonShine.
# БД — внешний PostgreSQL (см. docker-compose.yml).

FROM php:8.3-fpm-bookworm

# --- Системные пакеты: nginx (веб-сервер) + supervisor (держит fpm и nginx) ---
RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor \
    && rm -rf /var/lib/apt/lists/*

# --- PHP-расширения (mlocati сам ставит нужные системные библиотеки) ---
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
      pdo_pgsql \
      pgsql \
      bcmath \
      intl \
      zip \
      gd \
      exif \
      pcntl \
      opcache

# --- Composer ---
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Сначала только манифесты — чтобы слой с зависимостями кэшировался
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Затем весь код и финальная сборка автозагрузчика.
# --no-scripts: package:discover запускаем в рантайме (entrypoint), где есть
# переменные окружения — так сборка не зависит от БД/ключа и не падает.
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction --no-scripts \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# --- Конфиги веб-сервера и процессов ---
COPY docker/nginx.conf       /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY docker/php.ini          /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint
# Защита от CRLF (если файл редактировался на Windows) — иначе shebang ломается
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint && chmod +x /usr/local/bin/entrypoint

EXPOSE 80

# entrypoint ждёт БД, прогоняет миграции и сиды, затем стартует supervisor
ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]
