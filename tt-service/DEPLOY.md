# Деплой демо-стенда «Теннис Клуб НСК»

Поднимается одной командой: контейнер приложения (nginx + PHP) + PostgreSQL.
Демо-данные (клуб, столы, брони, клиент) и ассеты MoonShine — уже внутри, ничего
докачивать не нужно. CSS и админка работают «из коробки».

---

## Шаг 1. Загрузить архив на сервер

На СВОЁМ компьютере (PowerShell), из папки `saas-platform`:

```powershell
scp C:\projectVS\saas-project\saas-platform\tt-service-deploy.tar.gz root@<SERVER_IP>:/root/
```
(введите пароль сервера, когда попросит)

## Шаг 2. Зайти на сервер и распаковать

```powershell
ssh root@<SERVER_IP>
```
Дальше — уже НА сервере:
```bash
cd /root
tar -xzf tt-service-deploy.tar.gz
cd tt-service
```

## Шаг 3. Запустить деплой (ставит Docker сам, если надо)

```bash
sh deploy-server.sh
```

Первый запуск собирает образ — это 3–6 минут. Когда увидите `Готово!` — стенд работает.

---

## Ссылки

| Что | Адрес |
|-----|-------|
| Сайт | http://<SERVER_IP> |
| Админка (MoonShine) | http://<SERVER_IP>/admin |

## Доступы

**Админка:**
| Роль | Логин | Пароль |
|------|-------|--------|
| Владелец клуба | `owner@tt-service.local` | `owner123` |
| Администратор | `admin@tt-service.local` | `admin123` |
| Суперадмин | `superadmin@tt-service.local` | `superadmin123` |

**Личный кабинет на сайте (вход по телефону):** `+79991234567` — Иван Петров

---

## Управление (на сервере, из папки tt-service)

```bash
docker compose logs -f app      # смотреть логи
docker compose ps               # статус контейнеров
docker compose restart app      # перезапустить приложение
docker compose down             # остановить (данные БД сохранятся)
docker compose up -d            # снова поднять
```

## Если что-то не так

- **Сайт не открывается** — проверьте логи: `docker compose logs app`
- **Включить подробные ошибки** — в `docker-compose.yml` поставьте `APP_DEBUG: "true"`,
  затем `docker compose up -d`
- **Полный сброс с нуля** (заново демо-данные):
  ```bash
  docker compose down -v && docker compose up -d --build
  ```
- **Привязали домен** — поменяйте `APP_URL` в `docker-compose.yml` на `http://your-domain.example.com`
  и выполните `docker compose up -d`

## Когда покажете заказчику — смените пароли

Логины демо-аккаунтов и пароль сервера, которые сейчас по умолчанию, для боевого
использования обязательно поменяйте.
