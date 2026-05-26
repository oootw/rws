# Docker — локальная разработка

> Для развёртывания на VDS (prod + staging) смотрите **[DEPLOYMENT.md](DEPLOYMENT.md)**.
> Тут — только локальная сборка и режимы разработки.

## Три способа запуска

| Режим               | Команда           | Где живут backend/frontend |
|---------------------|-------------------|---------------------------|
| Полный стек в Docker| `make local-up`   | внутри контейнеров         |
| Только инфра + хост | `make infra-up`   | на хосте (`artisan serve`, `npm run dev`) |
| Только тесты        | `make test`       | хост                       |

### Вариант 1. Всё в Docker (рекомендую для первого запуска)

```bash
make local-up
make local-key                 # генерация APP_KEY
```

Откройте `http://localhost:8080/s/{place_uuid}?tenant=demo`.

В local-стеке после `db:seed` уже есть tenant `demo` и одна тестовая точка.
`?tenant=demo` — это dev-режим: фронтенд кладёт slug в `X-Tenant-Slug`, чтобы
не настраивать поддомены локально.

#### Админ-панель локально

```bash
make local-shell
  php artisan admin:password           # задаст хеш и выведет команду для .env
  exit
# Запишите ADMIN_EMAIL/ADMIN_PASSWORD_HASH в backend/.env, затем:
make local-shell
  php artisan config:clear
  exit
```
Откройте `http://localhost:8080/admin`. См. `backend/админка-план.md` — карта Resource'ов и подводные камни.

### Вариант 2. Backend на хосте + Docker DB

Удобнее для отладки в IDE / xdebug.

```bash
make infra-up                  # PostgreSQL + Redis на хосте
bash scripts/init-env.sh local

# В backend/.env переключите хосты на localhost:
#   DB_HOST=127.0.0.1
#   REDIS_HOST=127.0.0.1

cd backend
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve              # http://127.0.0.1:8000

# В отдельном терминале:
cd frontend
npm ci
npm run dev                    # http://localhost:5173/s/{uuid}?tenant=demo
```

Vite проксирует `/api` и `/privacy` на `127.0.0.1:8000`.

---

## Структура compose-файлов

| Файл                          | Назначение |
|------------------------------|-----------|
| `docker-compose.base.yml`     | Базовые сервисы: postgres, redis, app, worker, scheduler, nginx (без host-портов) |
| `docker-compose.local.yml`    | `target: development`, bind-mount исходников, опубликован `${HTTP_PORT}` на хост |
| `docker-compose.staging.yml`  | `target: production`, alias `staging-nginx` в сети `guard-edge` |
| `docker-compose.prod.yml`     | `target: production`, alias `prod-nginx` в сети `guard-edge` |
| `docker-compose.yml`          | Только postgres + redis с публикацией портов на хост (для `make infra-up`) |

Makefile собирает их через `-f` последовательно. Не запускайте
`docker compose up` без `-f` — это поднимет только `docker-compose.yml`.

---

## Сервисы стека

| Сервис      | Назначение                                          | Когда смотреть логи |
|-------------|-----------------------------------------------------|---------------------|
| `postgres`  | БД                                                  | при проблемах с миграциями |
| `redis`     | очереди, cache, sessions                            | редко |
| `app`       | PHP-FPM (Laravel) — публичные API, `/admin` (Filament), вебхуки | основной |
| `worker`    | `queue:work redis` — отправка алертов, дайджестов   | если уведомления не приходят |
| `scheduler` | `schedule:work` — крон (например, weekly digest)    | если ежедневные/еженедельные задачи не идут |
| `nginx`     | scan SPA на `/s/`, API на `/api/`, `/admin`, privacy | при 502 / роутинге |

При старте `app` контейнер автоматически:
1. Ждёт PostgreSQL (`wait_for_database`).
2. Прогоняет `migrate --force` (если `RUN_MIGRATIONS=true`).
3. На local — `db:seed` (если `RUN_SEED=true`).
4. На staging/prod — `config:cache + route:cache + view:cache`.

Только `app` запускает миграции; у `worker` и `scheduler` `RUN_MIGRATIONS=false`.

---

## Полезные команды

```bash
make local-down && make local-up    # перезапуск с пересборкой
make local-shell                    # shell в контейнере app
make local-migrate                  # миграции вручную
make test                           # pest
make pint                           # формат

# Произвольный artisan
docker compose -f docker-compose.base.yml -f docker-compose.local.yml \
    exec app php artisan tinker
```

---

## Переменные окружения

| Файл                 | Что внутри |
|----------------------|------------|
| `/.env`              | переменные для **docker compose** (порты, имя проекта, пароль БД для контейнера postgres) |
| `/backend/.env`      | переменные для **Laravel** (APP_KEY, TELEGRAM_*, TINKOFF_*, ...) |
| `/deploy/proxy/.env` | переменные для **Caddy** (только на сервере, см. DEPLOYMENT.md) |

Шаблоны:
- Корневой compose-env: `deploy/env/{local,staging,production}.compose.env.example`
- Laravel: `deploy/env/{local,staging,production}.env.example`

Не редактируйте напрямую — используйте `bash scripts/init-env.sh <env>`. Скрипт
создаст оба файла из правильных шаблонов и не перезатрёт уже существующие.

---

## Frontend

Workspace: `frontend/` — npm workspaces (`scan` + `shared`).

Сборка `scan` идёт через Vite в `dist/scan/`. Этот каталог попадает в nginx-образ
на этапе билда (`docker/nginx/Dockerfile`, multi-stage).

Флаг `VITE_ALLOW_TENANT_QUERY`:
- `1` (local, staging) — фронт читает `?tenant=demo` из URL и шлёт `X-Tenant-Slug` заголовок;
- `0` (prod) — фронт всегда определяет tenant по поддомену.

Меняется через `args` в `docker-compose.{local,staging,prod}.yml`.

---

## Troubleshooting

| Симптом | Решение |
|---------|---------|
| `502 Bad Gateway` при заходе на `localhost:8080` | `make local-logs` — `app` ещё поднимается или упала миграция. |
| `Class … not found` после `git pull` | `make local-shell` → `composer install`. |
| Фронт не видит API (404 в браузере) | На local: проверьте, что nginx-контейнер запущен; на хосте — Vite proxy на `127.0.0.1:8000`. |
| `?tenant=demo` не работает | В prod-сборке фронта `VITE_ALLOW_TENANT_QUERY=0`. На local должен быть `1`. |
| Хочется чистый старт | `make local-down && docker volume rm guard-local_postgres_data guard-local_redis_data guard-local_app_storage && make local-up` |
