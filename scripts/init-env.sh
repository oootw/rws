#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_NAME="${1:-local}"

case "$ENV_NAME" in
  local|staging|production) ;;
  *)
    echo "Usage: $0 {local|staging|production}"
    exit 1
    ;;
esac

# 1. Laravel .env --------------------------------------------------------
BACKEND_SRC="$ROOT/deploy/env/${ENV_NAME}.env.example"
BACKEND_DEST="$ROOT/backend/.env"

if [[ -f "$BACKEND_DEST" ]]; then
    echo "Файл backend/.env уже существует — пропускаю."
    echo "Если хотите начать заново: rm backend/.env && повторите запуск."
else
    cp "$BACKEND_SRC" "$BACKEND_DEST"
    echo "✓ Создан backend/.env из $ENV_NAME шаблона."
fi

# 2. Compose .env --------------------------------------------------------
COMPOSE_SRC="$ROOT/deploy/env/${ENV_NAME}.compose.env.example"
COMPOSE_DEST="$ROOT/.env"

if [[ ! -f "$COMPOSE_SRC" ]]; then
    echo "⚠ Нет шаблона $COMPOSE_SRC — пропускаю корневой .env."
elif [[ -f "$COMPOSE_DEST" ]]; then
    echo "Файл .env (для docker compose) уже существует — пропускаю."
else
    cp "$COMPOSE_SRC" "$COMPOSE_DEST"
    echo "✓ Создан корневой .env из ${ENV_NAME}.compose.env.example"
fi

cat <<EOF

Дальше:
  1. Откройте backend/.env и заполните секреты
     (APP_KEY, DB_PASSWORD, TELEGRAM_BOT_TOKEN, TINKOFF_*, MAIL_*, ADMIN_ALERT_EMAIL).
  2. Сгенерируйте APP_KEY:
       make ${ENV_NAME}-key            # или: cd backend && php artisan key:generate
  3. Поднимите стек:
       make ${ENV_NAME}-up
EOF
