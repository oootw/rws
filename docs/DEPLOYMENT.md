# Развёртывание: prod + staging на одном VDS

Этот документ — полное пошаговое руководство по разворачиванию Guard Reviews
с нуля. На одном сервере параллельно работают **production** и **staging** —
изолированно, через единый reverse-proxy с TLS.

## Что получится

```
       Интернет (HTTPS 443)
              │
              ▼
   ┌──────────────────────┐
   │   Caddy (edge)       │   /srv/guard-edge/
   │   Let's Encrypt      │   маршрутизирует по Host:
   └──┬────────────────┬──┘
      │                │
      ▼                ▼
 ┌──────────┐    ┌──────────┐
 │  prod    │    │  staging │
 │  стек    │    │  стек    │
 │ (nginx + │    │ (nginx + │
 │  php-fpm)│    │  php-fpm)│
 │ postgres │    │ postgres │
 │ redis    │    │ redis    │
 └──────────┘    └──────────┘
 /srv/guard-prod  /srv/guard-staging

 Внешняя docker-сеть «guard-edge»
   ↔ объединяет Caddy с обоими nginx (по alias prod-nginx / staging-nginx)
 Внутренние сети каждого стека изолированы (своя app-сеть).
```

| Стенд      | Apex домен            | Tenant поддомены           | Назначение                        |
|------------|-----------------------|----------------------------|-----------------------------------|
| production | `otziv.space`         | `<slug>.otziv.space`       | боевые клиенты                    |
| staging    | `staging.otziv.space` | `<slug>.staging.otziv.space` | ваши тесты платежей/бота          |

База, очереди, контейнеры, диски — **полностью раздельные** (`COMPOSE_PROJECT_NAME=guard-prod` vs `guard-staging`).
Боты, Tinkoff-терминалы, SMTP — **разные** (свои токены/секреты в `backend/.env`).

---

## 0. Что нужно от вас перед началом

### Сервер
- VDS с публичным IP (Ubuntu 22.04 / Debian 12, ≥ 2 vCPU / 4 GB RAM).
- Открытые порты: 80, 443 (TCP+UDP — для HTTP/3).
- Установлены: `git`, `docker` ≥ 24, `docker compose v2`, `make`.

```bash
apt update && apt install -y git make ca-certificates curl
curl -fsSL https://get.docker.com | sh
```

### Домены (DNS)
A-записи на IP сервера:

| Запись                       | Тип | Значение              |
|------------------------------|-----|-----------------------|
| `otziv.space`                | A   | `<IP>`                |
| `staging.otziv.space`        | A   | `<IP>`                |
| `*.otziv.space`              | A   | `<IP>`                |
| `*.staging.otziv.space`      | A   | `<IP>`                |

> **Wildcard-A** нужны, чтобы клиентский поддомен `kafe.otziv.space` доезжал до сервера. На сертификаты они не влияют — Caddy выпускает индивидуальный сертификат на каждый поддомен по факту первого обращения (on-demand TLS).

### Внешние сервисы (заведите заранее два набора — для prod и для staging)
- **Telegram bot** — для каждого стенда свой бот. У BotFather: `/newbot`, сохраните токен.
- **Tinkoff Acquiring** — два терминала (боевой и тестовый). Сохраните `TerminalKey` + `Password`.
- **SMTP** — для рассылки писем (e-mail-фолбэк, critical-error).
- **Yandex SmartCaptcha** (опционально для MVP) — две пары ключей.

---

## 1. Разворачиваем edge-прокси (один раз)

Edge — это Caddy, который держит 80/443, выпускает Let's Encrypt сертификаты и раскидывает трафик по стекам. Поднимается **один раз** и обслуживает оба стенда.

```bash
mkdir -p /srv && cd /srv
git clone <repo-url> guard-edge
cd guard-edge

# Готовим конфиг прокси
cp deploy/proxy/.env.example deploy/proxy/.env
nano deploy/proxy/.env
#   DOMAIN=otziv.space
#   STAGING_DOMAIN=staging.otziv.space
#   ACME_EMAIL=admin@otziv.space

# Создаём внешнюю docker-сеть и поднимаем Caddy
make proxy-net
make proxy-up
make proxy-logs    # должны увидеть "serving initial configuration"
```

Caddy будет ждать nginx-ов из обоих стеков. Пока их нет — будет логировать «no such host». Это нормально, поправится после шагов 2 и 3.

> **Зачем мы клонировали репозиторий целиком в `/srv/guard-edge`?** Чтобы получить `deploy/proxy/`. Каталог `/srv/guard-edge` используется ТОЛЬКО как удобное место для конфига прокси — приложение здесь не запускается.

---

## 2. Разворачиваем production-стек

```bash
cd /srv
git clone <repo-url> guard-prod
cd guard-prod

# Готовим конфиги
bash scripts/init-env.sh production
#   создаст backend/.env и .env (корневой, для compose)

nano backend/.env
# Обязательно заполнить:
#   APP_KEY=                          ← оставьте пустым, заполнится make prod-key
#   DB_PASSWORD=<сильный пароль>
#   MAIL_HOST=, MAIL_USERNAME=, MAIL_PASSWORD=, ADMIN_ALERT_EMAIL=
#   TELEGRAM_BOT_TOKEN=<боевой бот>
#   TELEGRAM_BOT_USERNAME=
#   TINKOFF_TERMINAL_KEY=<боевой терминал>
#   TINKOFF_SECRET_KEY=
#   FOUNDER_TELEGRAM_USERNAME=
#   (опц.) YANDEX_CAPTCHA_CLIENT_KEY / SERVER_KEY

# ВАЖНО: из РФ VDS api.telegram.org заблокирован Роскомнадзором.
# Поднимите свой relay вне РФ (готовый стек: deploy/telegram-proxy/) и пропишите:
#   TELEGRAM_API_URL=https://tg.your-domain.com
#   TELEGRAM_PROXY_SECRET=<секрет, общий с relay-сервером>
# Подробно: docs/TELEGRAM_RU.md

# Если на этом же VDS будет staging — оставьте TLS_ALLOWED_DOMAINS как в шаблоне.

# Сборка и запуск
make prod-up
make prod-key            # сгенерит APP_KEY и пропишет в backend/.env
make prod-down && make prod-up   # перезапуск с новым ключом

# Привязываем Telegram webhook
make prod-shell
  php artisan nutgram:hook:set https://otziv.space/api/webhooks/telegram
  exit
```

После `make prod-up` должно подняться:
- `guard-prod-postgres-1`, `guard-prod-redis-1`
- `guard-prod-app-1` (PHP-FPM)
- `guard-prod-worker-1` (`queue:work`)
- `guard-prod-scheduler-1` (`schedule:work`)
- `guard-prod-nginx` (container_name явный) — подключён к сети `guard-edge`

Проверки:

```bash
make prod-logs
curl -sI https://otziv.space/up        # 200 OK (Caddy → prod-nginx → /up)
```

---

## 3. Разворачиваем staging-стек

Тот же сервер, отдельная папка, отдельный набор контейнеров.

```bash
cd /srv
git clone <repo-url> guard-staging
cd guard-staging

bash scripts/init-env.sh staging

nano backend/.env
#   То же самое, что в prod, но:
#   APP_ENV=staging
#   APP_DEBUG=true            ← удобно для дебага
#   DB_PASSWORD=<другой пароль>
#   TELEGRAM_BOT_TOKEN=<тестовый бот>
#   TINKOFF_TERMINAL_KEY=<тестовый терминал>
#   ADMIN_ALERT_EMAIL=        ← можно оставить пустым

make staging-up
make staging-key
make staging-down && make staging-up

make staging-shell
  php artisan nutgram:hook:set https://staging.otziv.space/api/webhooks/telegram
  exit

curl -sI https://staging.otziv.space/up   # 200 OK
```

Готово — на одном VDS работают **обе** среды:

```
docker ps --format 'table {{.Names}}\t{{.Image}}'
```
покажет `guard-edge-caddy`, `guard-prod-*` (6 контейнеров) и `guard-staging-*` (6 контейнеров).

---

## 4. Тестируем платёжный поток (staging)

Tinkoff боевой/тестовый — два разных терминала, два набора секретов в `backend/.env`. На staging должен быть **тестовый** терминал — оплаты идут в песочнице, реальных списаний нет.

1. Включите webhook в личном кабинете Tinkoff (тестовый терминал):
   - **Notification URL:** `https://staging.otziv.space/api/webhooks/tinkoff`
   - **Success URL:** `https://staging.otziv.space/payment/success`
   - **Fail URL:** `https://staging.otziv.space/payment/fail`

2. В Telegram-боте staging-окружения:
   - `/start` → email → slug (например, `tester`)
   - `/addplace` → название «Тест» → ссылки 2GIS/Яндекс (или «-»)
   - `/pay` → нажмите кнопку → выпадет тестовая форма оплаты Tinkoff
   - После «успешной» оплаты в песочнице Tinkoff пришлёт webhook → подписка продлится → бот напишет «Подписка продлена!»

3. QR-проверка:
   - В боте `/places` → «📍 Тест» → «QR-код» — пришлёт PNG со ссылкой `https://tester.staging.otziv.space/s/<uuid>`.
   - Откройте на телефоне — должна загрузиться форма оценки. Первое обращение на `tester.staging.otziv.space` будет ~1 сек медленнее (Caddy выпустит сертификат).

4. Негативный отзыв:
   - Поставьте 1-3 звезды, оставьте контакт, отправьте.
   - В боте через несколько секунд придёт алерт.
   - В таблице `analytics_action_logs` появится запись.

Прод-стенд — то же самое, но с боевым терминалом и без `?tenant=` в URL.

---

## 5. Ежедневная эксплуатация

```bash
# Логи
make prod-logs            # все основные контейнеры prod
make staging-logs

# Зайти в контейнер
make prod-shell
make staging-shell

# Прогнать миграции (обычно не нужно — entrypoint делает сам при старте app)
make prod-migrate
make staging-migrate

# Обновить из git
cd /srv/guard-prod
git pull
make prod-up              # пересоберёт образы и накатит миграции

# Остановить стек
make prod-down
make staging-down
make proxy-down           # остановит Caddy (доступ снаружи отключится!)
```

---

## 6. Надёжность уведомлений о негативных отзывах

Что произойдёт, если в момент негативного отзыва Telegram-прокси лежит,
SMTP молчит, а MAX ещё не подключён:

1. Отзыв **сохраняется в БД до отправки уведомления** — не теряется ни при каких сбоях канала.
2. Уведомление кладётся в Redis-очередь как job `SendNegativeReviewAlert`.
3. Worker делает **10 попыток с экспоненциальным backoff'ом** (30c → 1ч,
   суммарно ~1.5 часа). Каждая попытка отдельно идёт по цепочке каналов:
   Telegram → MAX → email-fallback. Падение одного канала не блокирует
   остальные.
4. После исчерпания 10 попыток job уходит в таблицу `failed_jobs` с полным
   стектрейсом — данные сохранены, бизнес ничего не потерял.

### Перезапуск упавших уведомлений

```bash
make prod-shell
  # Посмотреть список упавших:
  php artisan reviews:retry-failed-alerts --list

  # Перезапустить все:
  php artisan reviews:retry-failed-alerts

  # Удалить упавшие без перезапуска (например, тестовые):
  php artisan reviews:retry-failed-alerts --purge
```

Команда читает payload каждого failed job, извлекает `reviewId` и снова
дёргает `SendNegativeReviewAlert` (worker подхватит). Безопасно запускать
многократно: уже отправленные алерты НЕ дублируются — каждая job снова
пройдёт по цепочке доставки, и если этот раз отработал — записи из
`failed_jobs` удалятся.

### Чек-лист «ничего не теряем»

- [ ] Worker запущен (`docker ps | grep worker`).
- [ ] `QUEUE_CONNECTION=redis` в `backend/.env`.
- [ ] Регулярный бэкап `postgres_data` (см. ниже) — захватывает отзывы.
- [ ] При восстановлении Telegram-прокси — `php artisan reviews:retry-failed-alerts`.
- [ ] Если есть резервный прокси — пропишите `TELEGRAM_API_URLS` (см. `docs/TELEGRAM_RU.md`).

---

## 7. Бэкапы PostgreSQL

`postgres_data` у каждого стека лежит в Docker-volume. Простейший дамп:

```bash
docker exec guard-prod-postgres-1 \
  pg_dump -U guard guard_reviews | gzip > /srv/backups/prod-$(date +%F).sql.gz
```

Заведите cron + ротацию (`logrotate` или `find -mtime +30 -delete`).
Для быстрого восстановления:

```bash
gunzip -c /srv/backups/prod-2026-05-24.sql.gz | \
  docker exec -i guard-prod-postgres-1 psql -U guard guard_reviews
```

---

## 8. Частые проблемы

| Симптом | Где смотреть | Решение |
|---------|--------------|---------|
| `curl https://otziv.space` отдаёт 502 / connection refused | `make proxy-logs` | Стек не поднят (`make prod-up`) или Caddy не нашёл `prod-nginx` в сети `guard-edge`. Проверь `docker network inspect guard-edge` — должны быть `guard-edge-caddy` и `guard-prod-nginx`. |
| Caddy выпустил сертификат на левый поддомен (rate-limit) | `make proxy-logs` | Проверь, что `TLS_ALLOWED_DOMAINS` в `backend/.env` указан, и `/api/internal/tls-allow?domain=<host>` возвращает 404 для неизвестных. |
| Подписка/`/pay` пишет «недоступно» | `make staging-logs` | В `backend/.env` пустые `TINKOFF_*`. После заполнения — `make staging-down && make staging-up`. |
| Telegram бот молчит | `make staging-logs` (worker) | Не задан `TELEGRAM_BOT_TOKEN`, не привязан webhook (`nutgram:hook:set`), или из РФ — не настроен relay (см. `docs/TELEGRAM_RU.md`). |
| Алерты не приходят в Telegram, но e-mail работает | `make prod-logs worker` | Worker не дойдёт без рабочего токена; e-mail — fallback в `MultiChannelOwnerNotifier`. |
| Поддомен `tester.staging.otziv.space` не открывается | Caddy лог | DNS wildcard не настроен или Caddy получил 404 от `ask`. Проверь `TLS_ALLOWED_DOMAINS` и DNS `*.staging.otziv.space`. |
| Все ссылки `https://` стали `http://` в письмах | `backend/.env` | Не доверяем proxy. Уже исправлено в `bootstrap/app.php` (`trustProxies('*')`); убедитесь, что используете актуальный код. |
| `make prod-up` зависает на «waiting for postgres» | `make prod-logs postgres` | Пароль БД поменяли, а volume старый. Либо вернуть пароль, либо `docker volume rm guard-prod_postgres_data` (⚠ потеряете данные). |

---

## 9. Безопасность: чек-лист

- [ ] `APP_KEY` сгенерирован (`make prod-key`).
- [ ] `APP_DEBUG=false` в production.
- [ ] `DB_PASSWORD` — длинный, разный у prod и staging.
- [ ] `backend/.env` не закоммичен (репозиторий уже игнорирует `.env`).
- [ ] Открыты только 80/443 на VDS, 5432/6379 — нет.
- [ ] Tinkoff webhook secret-key хранится только в `backend/.env`.
- [ ] Тестовый бот ≠ боевой бот (один токен = один webhook).
- [ ] Бэкап `postgres_data` настроен и проверен восстановлением.

---

## 10. Альтернатива: wildcard TLS через DNS-01

Если тенантов будет много (>50 в неделю) — упритесь в Let's Encrypt rate-limit на on-demand. Тогда переключитесь на wildcard сертификат `*.otziv.space` через DNS-01 challenge. Для этого нужен Caddy, собранный с модулем вашего DNS-провайдера. Образ:

```dockerfile
FROM caddy:2-builder AS builder
RUN xcaddy build --with github.com/caddy-dns/cloudflare
FROM caddy:2
COPY --from=builder /usr/bin/caddy /usr/bin/caddy
```

И в Caddyfile:

```
*.otziv.space {
    tls {
        dns cloudflare {env.CLOUDFLARE_API_TOKEN}
    }
    reverse_proxy prod-nginx:80
}
```

Это вне MVP — оставлено на потом.
