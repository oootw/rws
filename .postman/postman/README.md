# GuardReviews — Postman API

Документация и коллекция для всех HTTP API проекта.

| Файл | Назначение |
|------|------------|
| `GuardReviews.postman_collection.json` | Коллекция запросов с Postman-тестами |
| `GuardReviews.local.postman_environment.json` | Переменные окружения для local dev |
| `README.md` | Подробное описание контрактов (этот файл) |

## Импорт

1. Postman → **Import** → выберите оба JSON-файла.
2. Активируйте окружение **GuardReviews — Local**.
3. Заполните переменные: `baseUrl`, `tenantSlug`, `placeId`, `loginCode`.

## Общие правила

### Базовый URL

Все API-маршруты Laravel монтируются с префиксом `/api` (кроме web-маршрутов `/`, `/payment/*`, `/owner`).

```
{{baseUrl}} = http://localhost:8000   # local
{{baseUrl}} = https://cafe.otziv.space  # production (tenant subdomain)
```

### Мультитенантность

Большинство маршрутов требуют определения **тенанта** (владельца по поддомену):

| Окружение | Как передать |
|-----------|--------------|
| Production | Host: `{subdomain}.{APP_DOMAIN}` (например `cafe.otziv.space`) |
| local / staging / testing | Заголовок `X-Tenant-Slug: cafe` |

Без тенанта → **404**:

```json
{
  "message": "Аккаунт не найден. Проверьте адрес сайта.",
  "code": "tenant_not_found"
}
```

### Формат ошибок API

Стандартные бизнес-ошибки:

```json
{
  "message": "Человекочитаемое сообщение",
  "code": "machine_readable_code"
}
```

Ошибки валидации Laravel (422):

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": ["Сообщение об ошибке"]
  }
}
```

### Коды ошибок (`ApiErrorCode`)

| code | HTTP | Описание |
|------|------|----------|
| `tenant_not_found` | 404 | Неизвестный поддомен / нет заголовка X-Tenant-Slug |
| `place_not_found` | 404 | Точка не найдена, выключена или чужая |
| `review_not_found` | 404 | Отзыв не найден или не принадлежит владельцу |
| `subscription_expired` | 403 | Подписка не активна (публичные маршруты) |
| `platform_not_found` | 422 | Площадка не настроена у точки |
| `login_code_invalid` | 422 | Код не найден, истёк или уже использован |
| `login_code_expired` | 422 | Race: истёк между SELECT и consume |
| `login_code_already_consumed` | 422 | Race: параллельный consume |
| `session_tenant_mismatch` | 403 | Код выдан другому тенанту |

### Owner-аутентификация (Sanctum SPA)

Owner API использует **cookie-сессию**, не Bearer token:

1. `GET /sanctum/csrf-cookie` — получить `XSRF-TOKEN` cookie
2. `POST /api/owner/auth/exchange` — обменять код из Telegram на сессию
3. Все мутирующие запросы (`POST`, `PATCH`, `DELETE`) — заголовок `X-XSRF-TOKEN` (значение из cookie, URL-decoded)
4. Заголовок `X-Requested-With: XMLHttpRequest` — как в owner SPA (коллекция подставляет автоматически)

Коллекция подставляет `X-Tenant-Slug`, `Accept`, `X-Requested-With` и `X-XSRF-TOKEN` автоматически в pre-request script (только для `/api/*` маршрутов).

---

## 07 Internal & Health

### GET `/` — Health Check

**Auth:** нет  
**Tenant:** нет

**Ответ 200:**

```json
{
  "isHealthy": true
}
```

---

### GET `/up` — Laravel Health

**Auth:** нет  
**Tenant:** нет

Стандартный health-check Laravel (конфиг `bootstrap/app.php`).

---

### GET `/api/internal/tls-allow` — TLS Allow (Caddy)

**Auth:** нет  
**Tenant:** нет

Caddy on-demand TLS: перед выпуском сертификата проверяет, разрешён ли домен.

**Query:**

| Параметр | Тип | Обязательный | Описание |
|----------|-----|--------------|----------|
| `domain` | string | да | Проверяемый host |

**Ответ 200:** plain text `ok`  
**Ответ 404:** plain text `not allowed`

**Пример:**

```
GET /api/internal/tls-allow?domain=cafe.guardreviews.test
→ 200 ok

GET /api/internal/tls-allow?domain=evil.example.com
→ 404 not allowed
```

Разрешённые домены: `TLS_ALLOWED_DOMAINS` (csv) или `APP_DOMAIN`.

---

## 06 Webhooks

### POST `/api/webhooks/tinkoff` — Tinkoff Payment Notification

**Auth:** подпись `Token` в теле  
**Tenant:** нет  
**Content-Type:** `application/json`

Webhook подтверждения/отклонения платежа от Tinkoff Acquiring.

**Тело (основные поля):**

| Поле | Тип | Описание |
|------|-----|----------|
| `TerminalKey` | string | Ключ терминала |
| `OrderId` | string | UUID транзакции в нашей БД |
| `Success` | bool | Успех операции |
| `Status` | string | `CONFIRMED`, `REJECTED`, `AUTHORIZED`, … |
| `PaymentId` | int | ID платежа Tinkoff |
| `Amount` | int | Сумма в копейках |
| `ErrorCode` | string | `"0"` при успехе |
| `Token` | string | HMAC-SHA256 подпись |

**Ответ 200:** plain text `OK`  
**Ответ 400:** plain text `INVALID`

**Пример (подпись должна быть валидной):**

```json
{
  "TerminalKey": "TestTerminal",
  "OrderId": "550e8400-e29b-41d4-a716-446655440000",
  "Success": true,
  "Status": "CONFIRMED",
  "PaymentId": 123456,
  "Amount": 99000,
  "ErrorCode": "0",
  "Token": "72dd466f8ace0a37a1f740ce5fb78101712bc0665d91a8108c7c8a0ccd426db2"
}
```

При `CONFIRMED` + `Success: true` — продлевается подписка владельца.

---

### POST `/api/webhooks/telegram` — Telegram Bot Update

**Auth:** нет (Nutgram обрабатывает внутри)  
**Tenant:** нет  
**Content-Type:** `application/json`

Стандартный [Telegram Bot API Update](https://core.telegram.org/bots/api#update) object.

**Ответ 200:** plain text `OK`

**Пример:**

```json
{
  "update_id": 1,
  "message": {
    "message_id": 1,
    "date": 1700000000,
    "chat": { "id": 1001, "type": "private" },
    "text": "/login"
  }
}
```

---

## 01 Public API

Префикс: `/api/public`  
Middleware: `tenant`, `resolve.public.place`  
Большинство маршрутов: `subscription.active` (активная подписка)

`{place}` — UUID точки.

---

### GET `/api/public/places/{place}` — Данные точки

**Auth:** нет  
**Tenant:** да  
**Подписка:** активная

**Ответ 200:**

```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Cafe Uyut",
    "background_image_url": null,
    "platforms": [
      {
        "type": "2gis",
        "url": "https://2gis.ru/firm/example",
        "label": "2GIS"
      }
    ],
    "subscription_active": true,
    "captcha_client_key": "yandex-smart-captcha-client-key",
    "privacy_url": "https://cafe.otziv.space/privacy"
  }
}
```

**Ошибки:**

| HTTP | code | Когда |
|------|------|-------|
| 403 | `subscription_expired` | Подписка истекла |
| 404 | `place_not_found` | Точка выключена, чужая или не существует |
| 404 | `tenant_not_found` | Неверный тенант |

---

### POST `/api/public/places/{place}/scan` — Запись сканирования

**Auth:** нет  
**Tenant:** да  
**Подписка:** активная  
**Тело:** пустое

Фиксирует analytics-событие `Scanned`.

**Ответ 200:**

```json
{ "ok": true }
```

---

### POST `/api/public/places/{place}/redirect` — Переход на площадку

**Auth:** нет  
**Tenant:** да  
**Подписка:** активная

**Тело:**

| Поле | Тип | Обязательный | Описание |
|------|-----|--------------|----------|
| `platform_type` | string | да | Одно из `platforms[].type` точки |

Допустимые типы площадок: `2gis`, `yandex`, `custom`.

**Ответ 200:**

```json
{
  "ok": true,
  "url": "https://2gis.ru/firm/example"
}
```

**Пример запроса:**

```json
{ "platform_type": "2gis" }
```

**Ошибки:**

| HTTP | Тип | Когда |
|------|-----|-------|
| 422 | validation | `platform_type` не из списка точки |
| 422 | `platform_not_found` | Площадка не найдена в доменной модели |

---

### POST `/api/public/places/{place}/reviews` — Отправка отзыва

**Auth:** нет  
**Tenant:** да  
**Подписка:** активная  
**Throttle:** 10 запросов / минуту

**Тело:**

| Поле | Тип | Обязательный | Описание |
|------|-----|--------------|----------|
| `stars` | int | да | 1–3 (негативный отзыв) |
| `text` | string | да | max 5000 символов |
| `contact` | string | да | max 255 (телефон, email) |
| `consent_accepted` | bool | да | Должен быть `true` |
| `captcha_token` | string | да | Токен Yandex Smart Captcha (`YANDEX_CAPTCHA_SERVER_KEY` в .env) |

**Local dev:** если `YANDEX_CAPTCHA_SERVER_KEY` пуст, `test-token` проходит через NullCaptchaVerifier.

**Ответ 200:**

```json
{ "ok": true }
```

**Пример запроса:**

```json
{
  "stars": 2,
  "text": "Долго ждали заказ",
  "contact": "+79990001122",
  "consent_accepted": true,
  "captcha_token": "dD0x..."
}
```

**Ошибки 422 (validation):**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "captcha_token": ["Не удалось пройти проверку капчи."]
  }
}
```

---

### POST `/api/public/places/{place}/critical-error` — Критическая ошибка

**Auth:** нет  
**Tenant:** да  
**Подписка:** **не требуется** (доступен при истёкшей подписке)

**Тело:**

| Поле | Тип | Обязательный | Описание |
|------|-----|--------------|----------|
| `context` | string | да | max 255, контекст ошибки |

**Ответ 200:**

```json
{ "ok": true }
```

**Пример:**

```json
{ "context": "no_platforms" }
```

Отправляет email-алерт владельцу и администратору.

---

### GET `/sanctum/csrf-cookie` — CSRF Cookie (Sanctum)

**Auth:** нет  
**Tenant:** нет

Первый шаг owner SPA/Postman flow. Устанавливает cookie `XSRF-TOKEN`.

**Ответ 204:** No Content

---

## 00 Setup — Owner Auth

Префикс: `/api/owner`  
Middleware: Sanctum stateful, cookies, session, CSRF, `tenant`, `tenant-owns-session`

---

### POST `/api/owner/auth/exchange` — Обмен кода входа

**Auth:** нет (создаёт сессию)  
**Throttle:** 10 req/min

**Тело:**

| Поле | Тип | Обязательный | Описание |
|------|-----|--------------|----------|
| `code` | string | да | Ровно 6 цифр |

**Ответ 200:**

```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Иван",
    "email": "owner@example.com",
    "subdomain": "cafe",
    "telegram_connected": true
  }
}
```

**Пример запроса:**

```json
{ "code": "123456" }
```

**Ошибки:**

| HTTP | code | Когда |
|------|------|-------|
| 404 | `tenant_not_found` | Нет `X-Tenant-Slug` / неизвестный поддомен |
| 419 | — | Нет CSRF-cookie / заголовка `X-XSRF-TOKEN` |
| 422 | `login_code_invalid` | Код не найден, истёк или **уже использован** (consumed фильтруется в репозитории) |
| 422 | `login_code_already_consumed` | Только race: два параллельных exchange одного кода |
| 422 | `login_code_expired` | Только race: код истёк между SELECT и consume |
| 403 | `session_tenant_mismatch` | Код принадлежит другому тенанту |

---

### GET `/api/owner/me` — Текущий владелец

**Auth:** cookie-сессия (`auth:owner`)

**Ответ 200:** тот же `data`, что и после exchange.

**Ответ 401:**

```json
{ "message": "Unauthenticated." }
```

---

### POST `/api/owner/auth/logout` — Выход

**Auth:** cookie-сессия

**Тело:** пустое

**Ответ 200:**

```json
{
  "data": { "logged_out": true }
}
```

---

## 02 Owner — Dashboard

### GET `/api/owner/dashboard` — KPI

**Auth:** cookie-сессия

**Ответ 200:**

```json
{
  "data": {
    "scans": 42,
    "reviews": 15,
    "negative": 8,
    "redirects": 30,
    "places_count": 3,
    "daily_series": [
      { "date": "2025-05-21", "scans": 5, "reviews": 2 },
      { "date": "2025-05-22", "scans": 7, "reviews": 1 },
      { "date": "2025-05-23", "scans": 3, "reviews": 0 },
      { "date": "2025-05-24", "scans": 6, "reviews": 3 },
      { "date": "2025-05-25", "scans": 8, "reviews": 2 },
      { "date": "2025-05-26", "scans": 4, "reviews": 1 },
      { "date": "2025-05-27", "scans": 9, "reviews": 6 }
    ]
  }
}
```

`daily_series` — всегда 7 элементов (последние 7 дней).

---

## 03 Owner — Places

### GET `/api/owner/places` — Список точек

**Auth:** cookie-сессия

**Ответ 200:**

```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "title": "Уютное кафе",
      "platforms_count": 2,
      "is_active": true
    }
  ]
}
```

---

### GET `/api/owner/places/charge-preview` — Предпросмотр доплаты

**Auth:** cookie-сессия

Показывает прорату при добавлении новой точки.

**Ответ 200:**

```json
{
  "data": {
    "prorata_amount": 14000,
    "days_left": 14,
    "monthly_delta": 30000,
    "requires_payment": true
  }
}
```

Суммы в **копейках**.

---

### POST `/api/owner/places` — Создание точки

**Auth:** cookie-сессия

**Тело:**

| Поле | Тип | Обязательный | Описание |
|------|-----|--------------|----------|
| `title` | string | да | max 255 |
| `background_image_url` | string\|null | нет | URL, max 2048 |
| `platforms` | array | нет | Список площадок |
| `platforms[].type` | enum | да* | `2gis`, `yandex`, `custom` |
| `platforms[].url` | string | да* | URL, max 2048 |
| `platforms[].label` | string | да* | max 120 |

**Ответ 201:**

```json
{
  "data": { "id": "550e8400-e29b-41d4-a716-446655440000" },
  "charge": {
    "prorata_amount": 0,
    "days_left": 30,
    "monthly_delta": 0,
    "requires_payment": false
  }
}
```

**Пример запроса:**

```json
{
  "title": "Уютное кафе",
  "background_image_url": null,
  "platforms": [
    {
      "type": "2gis",
      "url": "https://2gis.ru/firm/test",
      "label": "2GIS"
    }
  ]
}
```

---

### GET `/api/owner/places/{place}` — Детали точки

**Auth:** cookie-сессия

**Ответ 200:**

```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Уютное кафе",
    "is_active": true,
    "background_image_url": null,
    "scan_url": "https://cafe.otziv.space/s/550e8400-e29b-41d4-a716-446655440000",
    "qr_png_url": "/api/owner/places/550e8400-e29b-41d4-a716-446655440000/qr.png",
    "platforms": [
      { "type": "2gis", "url": "https://2gis.ru/firm/test", "label": "2GIS" }
    ]
  }
}
```

**Ошибка 404:** `place_not_found`

> **Примечание:** поле `qr_png_url` возвращается в JSON, но маршрут `GET /api/owner/places/{id}/qr.png` в `routes/api.php` пока не зарегистрирован.

---

### PATCH `/api/owner/places/{place}` — Обновление

**Auth:** cookie-сессия  
**Тело:** как POST (create)

**Ответ 200:**

```json
{
  "data": { "id": "550e8400-e29b-41d4-a716-446655440000" }
}
```

---

### POST `/api/owner/places/{place}/toggle` — Вкл/выкл

**Auth:** cookie-сессия

**Тело:**

| Поле | Тип | Обязательный |
|------|-----|--------------|
| `active` | bool | да |

**Ответ 200:**

```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "is_active": false
  }
}
```

---

### DELETE `/api/owner/places/{place}` — Удаление

**Auth:** cookie-сессия  
**Тело:** пустое

**Ответ 200:**

```json
{
  "data": { "deleted": true }
}
```

---

## 04 Owner — Reviews

### GET `/api/owner/reviews` — Список отзывов

**Auth:** cookie-сессия

**Query:**

| Параметр | Тип | По умолчанию | Описание |
|----------|-----|--------------|----------|
| `status` | enum | — | `new`, `in_progress`, `resolved`, `archived` |
| `place_id` | uuid | — | Фильтр по точке |
| `from` | date | — | Начало периода |
| `until` | date | — | Конец (≥ from) |
| `page` | int | 1 | Страница |
| `per_page` | int | 20 | max 100 |

**Ответ 200:**

```json
{
  "data": [
    {
      "id": "660e8400-e29b-41d4-a716-446655440001",
      "place_id": "550e8400-e29b-41d4-a716-446655440000",
      "place_title": "Уютное кафе",
      "stars": 2,
      "status": "new",
      "contact": "+79990001122",
      "text": "Долго ждали заказ",
      "created_at": "2025-05-27T10:30:00+00:00"
    }
  ],
  "meta": {
    "total": 15,
    "page": 1,
    "per_page": 20,
    "last_page": 1
  }
}
```

**Пример:** `GET /api/owner/reviews?status=resolved&page=1&per_page=10`

---

### PATCH `/api/owner/reviews/{review}/status` — Смена статуса

**Auth:** cookie-сессия

**Тело:**

| Поле | Тип | Обязательный | Значения |
|------|-----|--------------|----------|
| `status` | enum | да | `new`, `in_progress`, `resolved`, `archived` |

**Ответ 200:**

```json
{
  "data": {
    "id": "660e8400-e29b-41d4-a716-446655440001",
    "status": "resolved"
  }
}
```

**Пример запроса:**

```json
{ "status": "resolved" }
```

**Ошибка 404:** `review_not_found`

---

## 05 Owner — Subscription & Payments

### GET `/api/owner/subscription` — Сводка подписки

**Auth:** cookie-сессия

**Ответ 200:**

```json
{
  "data": {
    "tariff_id": "770e8400-e29b-41d4-a716-446655440002",
    "tariff_title": "Pro",
    "ends_at": "2025-06-15T00:00:00+00:00",
    "days_left": 19,
    "is_active": true,
    "places_used": 2,
    "places_limit": 5,
    "next_charge_amount": 129000
  }
}
```

Суммы в **копейках**. `ends_at` — ISO 8601 (DATE_ATOM) или `null`.

---

### POST `/api/owner/subscription/init-payment` — Инициализация оплаты

**Auth:** cookie-сессия  
**Тело:** пустое

Создаёт транзакцию и запрос в Tinkoff Acquiring.

**Ответ 200:**

```json
{
  "data": {
    "payment_url": "https://securepay.tinkoff.ru/..."
  }
}
```

**Ответ 422** (эквайер недоступен):

```json
{
  "message": "Оплата временно недоступна..."
}
```

---

### GET `/api/owner/payments` — История платежей

**Auth:** cookie-сессия

**Query:**

| Параметр | Тип | По умолчанию |
|----------|-----|--------------|
| `page` | int | 1 |
| `per_page` | int | 20 (max 100) |

**Ответ 200:**

```json
{
  "data": [
    {
      "id": "880e8400-e29b-41d4-a716-446655440003",
      "amount": 99000,
      "status": "success",
      "external_id": "123456789",
      "tariff_title": "Pro",
      "created_at": "2025-05-01T12:00:00+00:00"
    }
  ],
  "meta": {
    "total": 3,
    "page": 1,
    "per_page": 20,
    "last_page": 1
  }
}
```

Статусы платежа: `pending`, `success`, `failed`, `refunded`.

---

## 08 Web Pages

Не JSON API — plain text ответы.

### GET `/payment/success`

**Ответ 200** (`text/plain; charset=UTF-8`):

```
Оплата прошла успешно.

Подписка будет активирована в течение минуты. Вернитесь в Telegram-бот и проверьте /subscription.
```

### GET `/payment/fail`

**Ответ 200** (`text/plain; charset=UTF-8`):

```
Оплата не завершена.

Попробуйте снова через команду /pay в Telegram-боте.
```

---

## 09 Legacy

### GET `/api/user` — Sanctum User

**Auth:** `auth:sanctum` (Bearer token или web cookie)

Стандартный Laravel Sanctum маршрут. В проекте owner-панель использует guard `owner`, не этот эндпоинт.

**Ответ 200:** объект User  
**Ответ 401:** Unauthenticated

---

## Postman-тесты

Каждый запрос в коллекции содержит тесты (`pm.test`), повторяющие ключевые проверки из PHPUnit Feature-тестов:

| Блок | Что проверяется |
|------|-----------------|
| Setup | CSRF cookie, структура OwnerMeView, logout |
| Public | subscription_active, ok/url, validation |
| Dashboard | KPI keys, daily_series.length === 7 |
| Places | CRUD status codes, charge structure, deleted |
| Reviews | meta pagination, review fields, status change |
| Subscription | subscription fields, payment_url |
| Webhooks | OK/INVALID response body |
| Internal | isHealthy, tls allow ok/not allowed |
| Web Pages | text/plain, русский текст |

### Запуск коллекции

```
Collection Runner → GuardReviews API → Run
```

Рекомендуемый порядок:

1. **07 Internal & Health → Health Check** (без auth)
2. **00 Setup → Get CSRF Cookie → Exchange Login Code**
3. **03 Owner → List Places** (сохранит `placeId`)
4. **01 Public → Get Place** (с тем же `placeId`)
5. Остальные блоки по необходимости

### Переменные окружения

| Переменная | Описание |
|------------|----------|
| `baseUrl` | Базовый URL сервера |
| `tenantSlug` | Поддомен владельца (для X-Tenant-Slug) |
| `placeId` | UUID точки (авто из List/Create Places) |
| `reviewId` | UUID отзыва (авто из List Reviews) |
| `loginCode` | 6-значный код из Telegram `/login` |
| `tinkoffTerminalKey` | TerminalKey для webhook-тестов |
| `tinkoffSecretKey` | Secret для подписи Token |

---

## Сводная таблица маршрутов

| Метод | Путь | Auth | Tenant | Блок |
|-------|------|------|--------|------|
| GET | `/sanctum/csrf-cookie` | — | — | Owner Auth (setup) |
| GET | `/` | — | — | Health |
| GET | `/up` | — | — | Health |
| GET | `/api/internal/tls-allow` | — | — | Internal |
| POST | `/api/webhooks/telegram` | — | — | Webhooks |
| POST | `/api/webhooks/tinkoff` | Token | — | Webhooks |
| GET | `/api/public/places/{place}` | — | ✓ | Public |
| POST | `/api/public/places/{place}/scan` | — | ✓ | Public |
| POST | `/api/public/places/{place}/redirect` | — | ✓ | Public |
| POST | `/api/public/places/{place}/reviews` | — | ✓ | Public |
| POST | `/api/public/places/{place}/critical-error` | — | ✓ | Public |
| POST | `/api/owner/auth/exchange` | — | ✓ | Owner Auth |
| GET | `/api/owner/me` | session | ✓ | Owner Auth |
| POST | `/api/owner/auth/logout` | session | ✓ | Owner Auth |
| GET | `/api/owner/dashboard` | session | ✓ | Dashboard |
| GET | `/api/owner/places` | session | ✓ | Places |
| GET | `/api/owner/places/charge-preview` | session | ✓ | Places |
| POST | `/api/owner/places` | session | ✓ | Places |
| GET | `/api/owner/places/{place}` | session | ✓ | Places |
| PATCH | `/api/owner/places/{place}` | session | ✓ | Places |
| POST | `/api/owner/places/{place}/toggle` | session | ✓ | Places |
| DELETE | `/api/owner/places/{place}` | session | ✓ | Places |
| GET | `/api/owner/reviews` | session | ✓ | Reviews |
| PATCH | `/api/owner/reviews/{review}/status` | session | ✓ | Reviews |
| GET | `/api/owner/subscription` | session | ✓ | Subscription |
| POST | `/api/owner/subscription/init-payment` | session | ✓ | Subscription |
| GET | `/api/owner/payments` | session | ✓ | Subscription |
| GET | `/payment/success` | — | — | Web Pages |
| GET | `/payment/fail` | — | — | Web Pages |
| GET | `/api/user` | sanctum | — | Legacy |
