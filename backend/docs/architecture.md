# Архитектура бэкенда Guard Reviews

Стиль: **прагматичный DDD на Laravel** — Eloquent остаётся, но используется
как деталь реализации (адаптер репозитория), а не как «всё подряд». Никакого
рывка в чистый Hexagonal: фреймворк виден только на границах (controllers,
middleware, jobs, Eloquent-модели в инфраструктуре).

Цели: SOLID, KISS, DRY, читабельность, лёгкая расширяемость новыми
контекстами.

## Принципы

- **The Dependency Rule** — зависимости направлены строго внутрь:
  `Interface → Application → Domain`. Слой Domain не знает о Laravel,
  Eloquent, HTTP, очередях.
- **Один Use Case = одно действие.** Контроллер вызывает ровно один
  use case и переводит ввод/вывод между HTTP и приложением.
- **Один Repository на агрегат**, а не на таблицу.
- **Value Objects** для бизнес-правил, выраженных типом (Stars, ContactInfo,
  IpHash).
- **Domain Events** для последствий действий — слушатели в Application
  раскидывают побочные эффекты (уведомления, журнал).
- **F.I.R.S.T. тесты**: домен покрывается чистыми unit-тестами без БД.

## Bounded Contexts

| Контекст          | Что внутри                                            |
|-------------------|-------------------------------------------------------|
| `Iam`             | Пользователь-владелец, тенант, подписка, тарифы.     |
| `Places`          | Точка (заведение), платформы, статус активности.     |
| `Reviews`         | Негативный отзыв, его статусы, история.              |
| `Analytics`       | Журнал действий (сканы/редиректы/негатив), сводки.   |
| `Payments`        | Транзакции, эквайринг (Тинькофф), пополнение подписки.|
| `Notifications`   | Каналы: Telegram, MAX, e-mail; форматирование.       |
| `TelegramBot`     | Команды/conversations/middleware Nutgram.            |

Каждый контекст самодостаточен. Между контекстами общение через:
- **Application use cases** (синхронно),
- **Domain Events** через Laravel events (асинхронно/слабо связанно).

Cross-context обращение к Eloquent-моделям другого контекста запрещено —
только через публичный use case или query interface.

## Структура директорий

```
app/
├── Domain/                    <- чистая бизнес-логика, без Laravel
│   ├── Reviews/
│   │   ├── Review.php                  (aggregate root)
│   │   ├── ReviewId.php                (VO)
│   │   ├── Stars.php                   (VO)
│   │   ├── ContactInfo.php             (VO)
│   │   ├── ReviewStatus.php            (enum)
│   │   ├── ReviewRepository.php        (interface)
│   │   └── Events/NegativeReviewSubmitted.php
│   ├── Places/...
│   ├── Iam/...
│   └── Shared/
│       ├── IpHash.php                  (VO)
│       └── Clock.php                   (interface)
├── Application/               <- use cases, слушатели, порты
│   ├── Reviews/
│   │   ├── SubmitReview/
│   │   │   ├── SubmitReviewCommand.php
│   │   │   └── SubmitReviewHandler.php
│   │   ├── ChangeReviewStatus/...
│   │   └── Listeners/NotifyOwnerAboutNegativeReview.php
│   ├── Notifications/...
│   └── Shared/Captcha/CaptchaVerifier.php  (порт)
├── Infrastructure/            <- реализации: Eloquent, HTTP, Telegram
│   ├── Persistence/Eloquent/
│   │   ├── Models/ReviewModel.php      (Eloquent\Model)
│   │   ├── Repositories/EloquentReviewRepository.php
│   │   └── Mappers/ReviewMapper.php
│   ├── Captcha/YandexSmartCaptchaVerifier.php
│   ├── Captcha/NullCaptchaVerifier.php
│   ├── Notifications/Telegram/...
│   ├── Notifications/Mail/...
│   ├── Clock/SystemClock.php
│   └── ServiceProviders/<Context>ServiceProvider.php
└── Interface/                 <- entry points
    ├── Http/
    │   ├── Controllers/Public/SubmitReviewController.php
    │   ├── Requests/Public/SubmitReviewRequest.php
    │   ├── Middleware/...
    │   └── Resources/...
    ├── TelegramBot/
    │   ├── Commands/...
    │   ├── Conversations/...
    │   └── Callbacks/...
    └── Console/
```

## Правила слоёв

### Domain
- Только PHP + value objects + сторонние библиотеки без I/O.
- `declare(strict_types=1);`, классы `final readonly` где возможно.
- Никаких `use Illuminate\…`, никаких `now()`, `config()`, фасадов.
- Время — через `Clock` (порт).
- Идентификаторы — VO (`ReviewId`, `PlaceId`, …) поверх UUID-строки.
- Бизнес-инварианты проверяются в конструкторах VO/в фабриках агрегата.

### Application
- Use case = handler с единственным публичным методом (`handle`).
- Принимает Command/Query DTO (immutable), возвращает DTO или void.
- Не знает HTTP/CLI/Telegram. Знает доменные интерфейсы.
- Транзакции — через явный порт `TransactionRunner` (адаптер на DB::transaction).
- Слушатели доменных событий живут здесь.

### Infrastructure
- Eloquent-модель — деталь персистентности (`Infrastructure\Persistence\Eloquent\Models`).
- Маппер переводит Eloquent ↔ доменный объект. Никто извне Eloquent-модель не видит.
- Адаптеры HTTP-клиентов, очередей, бот-SDK.
- Регистрация биндингов — в `*ServiceProvider`.

### Interface
- Тонкие контроллеры: разобрать запрос → собрать Command → вызвать handler → отдать ответ.
- FormRequest валидирует поля и приводит в типы; не лезет в БД сам.
- Resource/JsonResponse форматирует выход.
- Middleware — только cross-cutting (тенант, подписка, throttle).

## Naming

- VO: существительное (`Stars`, `ContactInfo`).
- Use case command: `<Verb><Aggregate>Command`, handler: `<Verb><Aggregate>Handler`.
- События: прошедшее время (`NegativeReviewSubmitted`, `ReviewStatusChanged`).
- Репозиторий: `<Aggregate>Repository` (интерфейс), `Eloquent<Aggregate>Repository` (адаптер).
- Контроллер: одно действие → `<Verb><Aggregate>Controller` (`__invoke`).

## Тестирование

- `tests/Unit/Domain/...` — pure PHP, без `RefreshDatabase`.
- `tests/Unit/Application/...` — с in-memory fake-репозиториями.
- `tests/Feature/...` — HTTP/Telegram/DB, фокус на сценариях,
  а не на деталях слоёв.

## Миграция текущего кода (план)

1. ✅ **Reviews** — пилот.
2. ✅ **Analytics + Notifications** — журнал действий, OwnerNotifier с цепочкой каналов, use cases уведомлений, рефакторинг jobs.
3. ✅ **Places** — агрегат Place + Platforms, ResolvePublicPlace/GetPublicPlaceView/RegisterPlace/ListOwnerPlaces/GetPlaceForOwner, middleware и контроллеры переключены на domain Place.
4. ✅ **Iam (Tenant + Subscription)** — Owner aggregate + Subscription VO, ResolveTenantBySubdomain/GetOwnerById/GetOwnerByTelegram/RegisterOwner/ExtendSubscription/CalculateSubscriptionAmount; middleware и Telegram-бот переключены на domain Owner; добавлен ChangeReviewStatus use case в Reviews.
5. ✅ **Payments** — PaymentTransaction aggregate + Money/PaymentStatus VO, InitSubscriptionPayment/HandlePaymentNotification use cases, порты AcquirerGateway + PaymentNotificationParser (адаптер — Tinkoff), TransactionRunner; webhook и `/pay` переключены на use cases.
6. ✅ **TelegramBot** — команды, conversations, callbacks и middleware Nutgram переехали в `App\Interface\TelegramBot\*`; TelegramWebhookController — в `App\Interface\Http\Controllers\Webhook`; добавлен read-model `ListRecentReviewsForOwner` (`RecentReviewsReader` port + `EloquentRecentReviewsReader`) — бот больше не дёргает Eloquent напрямую.

После каждого шага: тесты зелёные, старые public-API контракты неизменны.

Миграция завершена: все семь bounded contexts работают через Domain/Application/Infrastructure/Interface, Eloquent остался только в Infrastructure (адаптеры репозиториев, мапперы, read-models).
