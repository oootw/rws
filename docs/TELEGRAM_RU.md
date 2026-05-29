# Telegram бот с российского VDS

## TL;DR

- **Бэкенд готов.** Все исходящие запросы к Telegram идут через `TELEGRAM_API_URL`.
  Заголовок `X-Proxy-Secret` уже прокидывается из `TELEGRAM_PROXY_SECRET`
  через Guzzle-клиент Nutgram'а (`config/nutgram.php`).
- **Нужен relay вне РФ.** В РФ блокируется TCP к подсетям Telegram
  (`149.154.160.0/20`, `91.108.4.0/22` и др.), поэтому `sendMessage`/`getFile`
  напрямую не пройдут. Любой дешёвый VPS вне РФ (KZ, EU, US — от $1/мес) решает.
- **Webhook (вход) работает без прокси.** Telegram сам ходит к вашему IP —
  Роскомнадзор инвалидную сторону не блокирует.

## Как это работает

```
                                  ┌────────────────────────┐
  Telegram → POST /webhooks/...   │  Ваш VDS в РФ           │
  (входящие — без прокси) ──────► │  guard-prod / staging   │
                                  │                         │
                                  │  Nutgram → исходящие    │
                                  │  $TELEGRAM_API_URL      │
                                  │   + X-Proxy-Secret      │
                                  └───────────┬─────────────┘
                                              │
                                              ▼
                                  ┌────────────────────────┐
                                  │  Relay VPS вне РФ       │
                                  │  Caddy +                │
                                  │   reverse_proxy         │
                                  │   api.telegram.org      │
                                  └───────────┬─────────────┘
                                              │
                                              ▼
                                       api.telegram.org
```

## Шаг 1. Заведите VPS вне РФ под relay

Любой провайдер. Минимальные требования: 512 МБ RAM, публичный IP, открытые 80/443.
Подойдёт что угодно: [Aeza](https://aeza.net) (CZ/NL), [HOSTKEY](https://hostkey.com) (FI/NL),
[Vultr](https://vultr.com), [Hetzner](https://hetzner.com), [Contabo](https://contabo.com).

Заведите A-запись своего домена (или поддомена) на этот VPS, например:
`tg.your-domain.com → <IP relay-VPS>`.

## Шаг 2. Поднимите relay (готовый docker-compose в репо)

В репозитории есть `deploy/telegram-proxy/` — это самостоятельный мини-стек
(Caddy с TLS от Let's Encrypt + reverse-proxy на `api.telegram.org`).

```bash
# На relay-VPS:
git clone <repo-url> tg-proxy
cd tg-proxy/deploy/telegram-proxy

cp .env.example .env
nano .env
#   PROXY_DOMAIN=tg.your-domain.com
#   ACME_EMAIL=admin@your-domain.com
#   TG_PROXY_SHARED_SECRET=$(openssl rand -hex 32)   ← запомните эту строку

docker compose up -d
docker compose logs -f caddy
# Должны увидеть "successfully obtained certificate" — TLS выпущен.
```

Проверка:

```bash
curl https://tg.your-domain.com/bot123:fake/getMe
# Должно вернуть {"ok":false,"error_code":401,"description":"Unauthorized"}
# (правильный ответ Telegram'а — он не узнал токен, но связь работает)
```

Если без `X-Proxy-Secret` отвечает `forbidden` (403) — секрет проверяется,
все хорошо. С правильным заголовком — пробрасывает.

```bash
curl -H "X-Proxy-Secret: <ваш-секрет>" https://tg.your-domain.com/bot123:fake/getMe
# 401 Unauthorized от самого Telegram = relay работает.
```

## Шаг 3. Пропишите relay на основном сервере

В `backend/.env` обоих стендов (prod и staging):

```ini
TELEGRAM_API_URL=https://tg.your-domain.com
TELEGRAM_PROXY_SECRET=<тот же секрет, что в proxy/.env>
```

Затем:

```bash
make prod-down && make prod-up           # или staging
```

Webhook URL остаётся прежним (свой домен) — Telegram пишет к вам сам, прокси
для входящих не нужен:

```bash
make prod-shell
  php artisan nutgram:hook:set https://otziv.space/api/webhooks/telegram
```

## Шаг 4. Проверка

В Telegram-боте напишите `/start`. Если в логах `make prod-logs worker` (или `app`):
- `sendMessage` отдаёт **200** → relay работает, ответ дошёл.
- `Connection timed out` → проверьте `TELEGRAM_API_URL` (доходит до relay?) и
  `TELEGRAM_PROXY_SECRET` (совпадает с тем, что в relay'е?).
- `403 forbidden` от Caddy → секрет не совпадает.
- `401 Unauthorized` от Telegram → токен бота неверный.

## Несколько прокси с автоматическим переключением

Если у вас есть запасной канал (вдруг один из прокси отвалится — например, в КЗ
тоже периодически бывают проблемы), пропишите оба URL через запятую:

```ini
TELEGRAM_API_URLS="https://tg-primary.example.com,https://tg-backup.example.com,https://tg-emergency.example.com"
```

Что произойдёт:
- Первый URL — primary. Все запросы идут на него.
- При **сетевой ошибке** (connect timeout, EOF, отсутствие ответа) или ответе
  **502/503/504** — клиент молча перепишет хост на следующий URL и повторит ТОТ
  ЖЕ запрос. В логах появится warning о фолбэке.
- **4xx ответы** Telegram (401, 400, 429) — НЕ повторяются: это смысловые
  ошибки Telegram, повтор через другой прокси даст то же самое.
- Если все URL упали — исключение пробрасывается наверх. Job уйдёт на
  следующую попытку (см. backoff в `SendNegativeReviewAlert`) и при следующем
  запуске опять начнёт с primary.

`TELEGRAM_API_URL` при заданном `TELEGRAM_API_URLS` игнорируется — первый URL
из списка используется как primary.

В `X-Proxy-Secret` отправляется одно и то же значение `TELEGRAM_PROXY_SECRET`
во все прокси — настройте одинаковый секрет на всех relay'ях, либо отключите
проверку на части из них (если firewall'ом allowlist'ите IP основного сервера).

## Альтернативные пути (без своего relay-VPS)

### Cloudflare Workers (бесплатно, до 100 000 req/день)

Создайте Worker с таким кодом — он будет прозрачно проксировать `api.telegram.org`:

```js
export default {
  async fetch(req) {
    if (req.headers.get('x-proxy-secret') !== 'YOUR_SECRET') {
      return new Response('forbidden', { status: 403 });
    }
    const url = new URL(req.url);
    url.host = 'api.telegram.org';
    url.protocol = 'https:';
    return fetch(new Request(url, req));
  },
};
```

Привяжите свой домен (`tg.your-domain.com`) к Worker'у в настройках Cloudflare —
получите бесплатный HTTPS-relay. Прописываете в `TELEGRAM_API_URL` так же.

> ⚠ Cloudflare принадлежит компании в США. Юридически — внешний посредник,
> технически — стабильно, проверено. Подходит для MVP и тестового
> прогона. Для прод-нагрузок лучше всё-таки свой VPS.

### MTProxy / nginx stream

Если вы уже держите MTProxy для пользователей Telegram, **он не подходит** —
MTProxy работает только с клиентскими MTProto-подключениями, не с Bot API
(HTTP). Для Bot API всегда нужен HTTPS-relay.

## Что НЕ работает в РФ даже с relay

| Функция | Состояние |
|---------|-----------|
| `sendMessage`, `sendPhoto`, `editMessage*`, и т.д. | ✓ работает |
| Скачивание файлов через `getFile` + `https://.../file/...` | ✓ работает (Caddyfile уже проксирует `/file/*`) |
| Long polling (`getUpdates`) | ✓ работает |
| Установка webhook (`setWebhook`) — мы делаем это через relay | ✓ работает |
| Webhook (входящие от Telegram → к нам) | ✓ работает напрямую, прокси не нужен |
| Голосовые/видео-звонки между ботом и пользователем | n/a, мы их не используем |

## Чек-лист

- [ ] VPS вне РФ поднят, домен `tg.your-domain.com` указывает на него.
- [ ] `deploy/telegram-proxy/docker-compose.yml` развёрнут на этом VPS, TLS выпущен.
- [ ] `curl -H "X-Proxy-Secret: ..." https://tg.your-domain.com/bot123:fake/getMe`
      возвращает 401 от Telegram (значит связь есть).
- [ ] В `backend/.env` обоих стендов прописаны `TELEGRAM_API_URL` и `TELEGRAM_PROXY_SECRET`.
- [ ] Стеки перезапущены (`make prod-down && make prod-up` / `make staging-*`).
- [ ] `nutgram:hook:set` выполнен с https-URL вашего основного домена.
- [ ] Бот отвечает на `/start` — значит исходящий поток работает.

---

## Привязка общего Telegram-чата (для владельца)

Фича `shared_telegram_chat` (тариф) позволяет владельцу подключить **групповой
чат** — туда бот шлёт те же алерты о негативных отзывах, что уходят владельцу
в личку. Удобно команде поддержки/менеджерам: уведомление одно — видят все.

### Шаги для владельца

1. Зайти в **Owner-панель → Профиль**.
2. Найти карточку **«Общий Telegram-чат»** (видна только на тарифах с фичей;
   на остальных тарифах вместо карточки — апсэлл «Доступно в платных тарифах»).
3. Нажать **«Привязать Telegram-чат»**. Откроется ссылка вида
   `https://t.me/<bot>?startgroup=<token>` — Telegram сам спросит, в какую
   группу добавить бота.
4. Выбрать группу и подтвердить добавление. Бот появится в группе и пришлёт
   подтверждение «Чат привязан». Чат появится в списке на странице профиля
   (может занять несколько секунд — нужно обновить страницу).
5. С этого момента в группу начнут приходить алерты о негативных отзывах,
   параллельно с личкой владельца и Web Push.

### Технические детали

- Токен в ссылке — одноразовый, TTL 10 минут (см.
  `guardreviews.chat_link.ttl_seconds`). Если не успели — нажмите кнопку
  ещё раз, получите новый.
- Если бот уже в группе и владелец повторно нажал кнопку — привязка
  обновится без дубликата (идемпотентно по `(owner, chat_id)`).
- Отвязать чат: кнопка **«Отвязать»** напротив строки. Бот остаётся в группе,
  но алерты туда перестают идти; бота можно удалить из группы вручную.
- Если бота добавили в группу **без** deep-link (вручную), он пришлёт
  подсказку: «привяжите через панель владельца» — это путь восстановления.
- Доставка: алерт считается доставленным, если **хотя бы один** таргет
  (личка владельца / любой из групповых чатов) принял сообщение. Если все
  упали — фоллбэк на email (как и раньше).

См. эпик A в `backend/docs/shared-telegram-chat-plan.md`.
