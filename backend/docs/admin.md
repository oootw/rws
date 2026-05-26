# Админ-панель Guard Reviews

Документ-руководство по dev-админке (Filament v5 на `/admin`).
Высокоуровневый план и история фаз — `backend/админка-план.md`.

---

## 1. Доступ

- URL: `/admin` на основном домене (`config/guardreviews.php → admin.path`).
- Гард: `admin` (`config/auth.php`). Один супер-админ хранится в `.env`
  (`ADMIN_EMAIL` + `ADMIN_PASSWORD_HASH`), таблицы `admin_users` нет.
- Создать/обновить пароль:
  ```bash
  php artisan admin:password
  # либо готовая строка для .env:
  php artisan admin:password --show-env
  ```
  После обновления `.env` — `php artisan config:clear`.

### Защита edge

В `.env` (`config/guardreviews.php → admin`):

| Переменная           | Назначение                                         | Дефолт   |
|----------------------|----------------------------------------------------|----------|
| `ADMIN_PANEL_PATH`   | path под который монтируется панель                | `admin`  |
| `ADMIN_EMAIL`        | login                                              | —        |
| `ADMIN_PASSWORD_HASH`| bcrypt-хеш пароля                                  | —        |
| `ADMIN_NAME`         | отображаемое имя                                   | `Developer` |
| `ADMIN_ALLOWED_IPS`  | CSV IP/CIDR allow-list (пусто = отключено)         | пусто    |
| `THROTTLE_ADMIN`     | rate-limit `<attempts>,<minutes>`                  | `60,1`   |

`RestrictAdminToAllowedIps` + `ThrottleRequests` подключены в
`AdminPanelProvider::panel()` к middleware панели. На staging/prod
рекомендуется дополнительно прикрыть `/admin` basic-auth'ом на уровне
reverse-proxy (Caddy).

---

## 2. Архитектурный контракт

**Filament Resource = Interface-слой**, такой же тонкий как HTTP-контроллер.

- **Чтение** (`table()`, `infolist()`) — напрямую через Eloquent (read-model).
- **Запись** (любые мутации) — **только через Application use case**.
  Eloquent::save() в обход домена запрещён.
- **Исключение**: конфигурационные сущности без сложных инвариантов (Tariff) —
  CRUD напрямую через дефолтные `CreateRecord`/`EditRecord`; use case заводим
  только под инварианты (например, `SetDefaultTariff` гарантирует «ровно один
  is_default»).
- **Production-safety**: действия с побочными эффектами наружу (отправка
  тестовых сообщений, рассылка, ребродкаст webhook'ов) — `->visible(fn () =>
  ! app()->isProduction())`.

---

## 3. Аудит admin-действий

Каждое нажатие на Filament Action пишется в таблицу `admin_action_logs`
слушателем `LogAdminActionListener` (подписан на `ActionCalled` событие
Filament в `AdminAuditServiceProvider`).

Записывается: email админа, имя action'а, FQCN ресурса/страницы, ID записи
(если есть), аргументы action'а (payload), IP, User-Agent, timestamp.

Сбой записи лога **не валит** admin-action — слушатель ловит `Throwable`.
Сами записи доступны read-only в `Аудит админа` (`AdminActionLogResource`)
с фильтрами по периоду/действию/админу.

DDD-цепочка (как обычно):

- Domain: `App\Domain\Admin\AdminActionLog{,Id,IdGenerator,Repository}`.
- Application: `App\Application\Admin\RecordAdminAction\{Command,Handler}`.
- Infrastructure: `App\Infrastructure\Persistence\Eloquent\Admin\*`
  + биндинги в `AdminServiceProvider`.
- Interface: слушатель в `App\Interface\Filament\Audit\LogAdminActionListener`,
  регистрация в `AdminAuditServiceProvider`.

---

## 4. Карта Resource'ов

| Resource | Модель | Особенности |
|---|---|---|
| `Owners/OwnerResource` | `User` | Все мутации через use cases. Bulk-actions: extend subscription, change tariff (bulk-delete намеренно не сделан). |
| `Places/PlaceResource` | `Place` | Repeater платформ, QR preview/download, bulk activate/deactivate. |
| `Reviews/ReviewResource` | `Review` | Без Create. Edit = только статус. Delete с textarea-reason пишет аудит в `action_logs` (тип `AdminDeletedReview`). |
| `Tariffs/TariffResource` | `Tariff` | CRUD напрямую через Eloquent. Action `set_default` — единственное через use case. |
| `PaymentTransactions/PaymentTransactionResource` | `PaymentTransaction` | Read-only. `refire_webhook` скрыт на prod; `mark_failed` только для Pending. |
| `ActionLogs/ActionLogResource` | `ActionLog` | Read-only, журнал действий посетителей и удалений отзывов админом. |
| `NotificationDeliveries/NotificationDeliveryResource` | `NotificationDelivery` | Read-only, журнал отправок уведомлений. |
| `AdminActionLogs/AdminActionLogResource` | `AdminActionLog` | Read-only, аудит admin-действий. |

Custom Pages: `FailedJobsPage`, `EnvViewerPage`, `TelegramBotPage`.

---

## 5. Как добавить новую фичу

См. чек-лист из «п. 7 — Чек-лист новой фичи в админке» в `backend/админка-план.md`
и общий рецепт фичи в `backend/саммари.md → п. 10`.

Кратко:

1. **Domain**: расширь агрегат/Repository (`delete`, `findAll`, ...).
2. **Application**: новая папка `<UseCase>/` (`Command` + `Handler`).
3. **Infrastructure**: реализация портов + биндинг в `<Context>ServiceProvider`.
4. **Filament Resource**:
   - `$model = <EloquentModel>::class`.
   - `table()` / `form()` / `infolist()` — конфиг.
   - `Actions/<Context>ActionFactory.php` — custom-actions.
   - `Pages/Create*.php` + `Edit*.php` — переопределить `handleRecordCreation`
     / `handleRecordUpdate` → use case (исключение: Tariff-подобные сущности).
   - Production-safety: `->visible(fn () => ! app()->isProduction())`
     для действий с внешним эффектом.
5. **Тесты**:
   - Unit на handler.
   - Feature на Resource (HTTP smoke + интеграция handlers поверх Eloquent).
   - Livewire-фильтры через `Filament::setCurrentPanel('admin')`
     + `Livewire::test(ListXxx::class)`.
6. **Pint**: `./vendor/bin/pint <new-files>`.

---

## 6. Тесты

Прогон в Docker (PHP 8.4):

```bash
docker run --rm -v $(pwd)/backend:/app -w /app php:8.4-cli-alpine sh -c \
  "apk add --no-cache icu-libs icu-dev libzip-dev zip > /dev/null 2>&1 && \
   docker-php-ext-install intl > /dev/null 2>&1 && \
   php artisan test 2>&1"
```

Только admin:

```bash
... php artisan test tests/Feature/Admin \
                    tests/Unit/Application/Iam \
                    tests/Unit/Application/Places \
                    tests/Unit/Application/Reviews \
                    tests/Unit/Application/Payments \
                    tests/Unit/Application/Admin
```

Для Livewire-тестов таблиц/фильтров обязательно:

```php
use Filament\Facades\Filament;

beforeEach(function (): void {
    // actingAs admin ...
    Filament::setCurrentPanel('admin');
    Filament::setServingStatus(true);
});
```

---

## 7. Локализация

`APP_LOCALE=ru` (см. `.env.example`). Filament v5 включает русский перевод
бандлом — публиковать не нужно. Проектные строки — `lang/ru/admin.php`,
используются через `__('admin.<key>')`.

---

## 8. Подводные камни

Сводка — `backend/админка-план.md → п. 3`. Главные:

- Filament v5 (НЕ v3) — Laravel 13 совместим только с v5.
- Repeater в форме сохраняет ключи как UUID; приводи к `list` через
  `normalizeRepeaterPlatforms`.
- `TernaryFilter` использует `->queries(true:..., false:...)`, не
  `->query(closure)`.
- Custom guard `AdminUser` — нужны stub-методы `getKey()`,
  `getAttributeValue()`, `getRememberToken()`/`setRememberToken()`.
- `tariffs.is_default = true` — ищется через колонку (не по title).
  `TariffFactory` намеренно не ставит default; используй `TariffSeeder` либо
  явный `is_default => true`.
