# TT-Service — платформа для клуба настольного тенниса

Мультитенантная SaaS-платформа для управления клубом настольного тенниса: бронирование столов и залов, расписание, клиентская база, платежи, отчёты, админка для персонала, REST API для мобильного приложения и публичный сайт клуба.

> **EN:** Multi-tenant SaaS for a table-tennis club: booking engine with race-condition protection, schedules, CRM, payments domain, CSV/XLSX reports, MoonShine admin panel, REST API (Sanctum) for a mobile app and a public website. Laravel 13, PHP 8.3, PostgreSQL, domain-oriented architecture, feature tests.

Код лежит в [`tt-service/`](tt-service/). Подробный статус и готовность частей описаны в [`PROJECT_STATUS.md`](PROJECT_STATUS.md).

## Из чего состоит

| Часть | Назначение |
|-------|-----------|
| **Backend-ядро** (Laravel) | бизнес-логика по доменам: брони, расписание, платежи, CRM, отчёты |
| **Админка** (MoonShine) | управление клубом для владельца и администраторов |
| **Client API** (REST v1, Sanctum) | бэкенд для мобильного приложения |
| **Публичный сайт** (Blade) | витрина клуба и расписание |
| **Analyzer** | интеграция с анализатором реальной занятости столов (компьютерное зрение), пока в разработке |

## Архитектура

Код разделён на домены (`tt-service/app/Domain/`):

`Booking` · `Scheduling` · `Facilities` · `Services` · `Payments` · `Crm` · `Reports` · `Identity` · `Attendance` · `ClubCore` · `Analyzer`

- **Мультитенантность.** Данные изолированы по `club_id` через глобальный scope.
- **Брони** защищены от гонок (`SELECT … FOR UPDATE`), проверяются пересечения, рабочие часы и выходные. Есть временные holds слотов.
- **RBAC**: роли superadmin / owner / admin (spatie/laravel-permission).
- **Отчёты** выгружаются в CSV и XLSX.
- **Feature-тесты** покрывают бронирование, платежи, расписание, мультитенантность и RBAC.

## Стек

PHP 8.3 · Laravel 13 · PostgreSQL 16 · MoonShine 4 · Laravel Sanctum · Redis (predis) · Blade + JS · Docker

## Быстрый старт

### Docker

```bash
cd tt-service
cp .env.example .env
# сгенерировать APP_KEY и вписать его в .env
docker compose run --rm app php artisan key:generate --show
docker compose up -d --build
```

Сайт будет на http://localhost, админка на http://localhost/admin.

### Локально

```bash
cd tt-service
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Тесты

```bash
cd tt-service
php artisan test
```

Развёртывание на сервер описано в [`tt-service/DEPLOY.md`](tt-service/DEPLOY.md).
