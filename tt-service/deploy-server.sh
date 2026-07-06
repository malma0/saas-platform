#!/bin/sh
# Авто-деплой демо-стенда «Теннис Клуб НСК» на чистом Ubuntu/Debian сервере.
# Запуск:  cd /root/tt-service && sh deploy-server.sh
set -e

echo "================================================="
echo "  Деплой: Теннис Клуб НСК"
echo "================================================="

# 1. Docker — установить, если его нет
if ! command -v docker >/dev/null 2>&1; then
  echo "→ Docker не найден. Устанавливаю..."
  curl -fsSL https://get.docker.com | sh
else
  echo "→ Docker уже установлен: $(docker --version)"
fi

# 2. Открыть порт 80 в фаерволе (если ufw активен)
if command -v ufw >/dev/null 2>&1; then
  ufw allow 80/tcp 2>/dev/null || true
fi

# 3. Сборка и запуск
echo "→ Собираю образ и поднимаю контейнеры (первый раз 3–6 минут)..."
docker compose up -d --build

echo ""
echo "================================================="
echo "  Готово!"
echo "  Сайт:    http://<SERVER_IP>"
echo "  Админка: http://<SERVER_IP>/admin"
echo ""
echo "  Логи:    docker compose logs -f app"
echo "================================================="
