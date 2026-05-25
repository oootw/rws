#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND="$ROOT/backend"
COMPOSE=(docker compose -f "$ROOT/docker-compose.base.yml" -f "$ROOT/docker-compose.local.yml")

# phpunit.xml env — в Docker .env перебивает их без явной передачи
DOCKER_TEST_ENV='APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync'

MIN=""
HTML_DIR=""
USE_DOCKER=""

usage() {
    cat <<EOF
Usage: $(basename "$0") [OPTIONS]

Запускает тесты backend и выводит процент покрытия кода (app/).

Options:
  --min N       Минимальный порог покрытия (%); завершится с ошибкой, если ниже
  --html [DIR]  HTML-отчёт в backend/DIR (по умолчанию: coverage)
  --docker      Запуск в контейнере app (нужен make local-up)
  -h, --help    Справка

Примеры:
  $(basename "$0")
  $(basename "$0") --min 80
  $(basename "$0") --html
  make test-coverage MIN=80
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --min)
            MIN="$2"
            shift 2
            ;;
        --html)
            if [[ $# -ge 2 && "$2" != --* ]]; then
                HTML_DIR="$2"
                shift 2
            else
                HTML_DIR="coverage"
                shift
            fi
            ;;
        --docker)
            USE_DOCKER=1
            shift
            ;;
        -h | --help)
            usage
            exit 0
            ;;
        *)
            echo "Неизвестный аргумент: $1" >&2
            usage >&2
            exit 1
            ;;
    esac
done

php_has_coverage_driver() {
    php -m 2>/dev/null | grep -qiE '^(pcov|xdebug)$'
}

docker_app_running() {
    "${COMPOSE[@]}" ps app --status running -q 2>/dev/null | grep -q .
}

build_pest_args() {
    local args=(
        --coverage
        --coverage-text
        --only-summary-for-coverage-text
    )
    if [[ -n "$MIN" ]]; then
        args+=(--min="$MIN")
    fi
    if [[ -n "$HTML_DIR" ]]; then
        args+=(--coverage-html="$HTML_DIR")
    fi
    echo "${args[@]}"
}

run_pest() {
    local php_bin="$1"
    shift
    local -a php_opts=("$@")
    local -a pest_args
    read -r -a pest_args <<<"$(build_pest_args)"

    cd "$BACKEND"
    "$php_bin" "${php_opts[@]}" artisan config:clear --ansi --no-interaction
    "$php_bin" "${php_opts[@]}" ./vendor/bin/pest "${pest_args[@]}"
}

run_local() {
    local -a php_opts=()
    if php -m 2>/dev/null | grep -qi '^pcov$'; then
        php_opts=(-d pcov.enabled=1)
    else
        export XDEBUG_MODE=coverage
    fi
    run_pest php "${php_opts[@]}"
}

ensure_docker_vendor() {
    "${COMPOSE[@]}" exec -T -w /var/www/html app sh -c \
        '[ -f vendor/autoload.php ] || composer install --no-interaction --prefer-dist'
}

run_docker() {
    if ! docker_app_running; then
        echo "Контейнер app не запущен. Поднимите: make local-up" >&2
        exit 1
    fi

    local -a pest_args
    read -r -a pest_args <<<"$(build_pest_args)"

    ensure_docker_vendor

    local pest_quoted=""
    local arg
    for arg in "${pest_args[@]}"; do
        pest_quoted+=" $(printf '%q' "$arg")"
    done

    "${COMPOSE[@]}" exec -T -w /var/www/html app sh -c \
        "${DOCKER_TEST_ENV} php -d pcov.enabled=1 artisan config:clear --ansi --no-interaction \
        && ${DOCKER_TEST_ENV} php -d pcov.enabled=1 ./vendor/bin/pest${pest_quoted}"
}

if [[ -n "$USE_DOCKER" ]]; then
    run_docker
elif php_has_coverage_driver; then
    run_local
elif docker_app_running; then
    echo "→ PCOV/Xdebug не найден локально, запуск в Docker…"
    run_docker
else
    cat >&2 <<EOF
Ошибка: нет драйвера покрытия (PCOV или Xdebug).

Локально:
  pecl install pcov
  # или пакет ОС, например: apk add php84-pecl-pcov

Docker (рекомендуется):
  make local-up
  $(basename "$0") --docker
EOF
    exit 1
fi
