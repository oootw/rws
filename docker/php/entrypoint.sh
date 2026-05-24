#!/bin/sh
set -e

cd /var/www/html

fix_permissions() {
    if [ -d storage ]; then
        chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
        chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
    fi
}

wait_for_database() {
    if [ -z "${DB_HOST:-}" ]; then
        return 0
    fi

    echo "Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT:-5432}..."

    until pg_isready -h "${DB_HOST}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-postgres}" -d "${DB_DATABASE:-postgres}" >/dev/null 2>&1; do
        sleep 2
    done

    echo "PostgreSQL is ready."
}

run_migrations() {
    if [ "${RUN_MIGRATIONS:-false}" != "true" ]; then
        return 0
    fi

    echo "Running migrations..."
    php artisan migrate --force --no-interaction

    if [ "${RUN_SEED:-false}" = "true" ]; then
        echo "Running seeders..."
        php artisan db:seed --force --no-interaction
    fi
}

optimize_app() {
    if [ "${APP_ENV:-local}" = "production" ] || [ "${APP_ENV:-local}" = "staging" ]; then
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    fi
}

install_dev_dependencies() {
    if [ "${APP_ENV:-local}" != "local" ]; then
        return 0
    fi

    if [ ! -f vendor/autoload.php ]; then
        echo "Installing Composer dependencies (dev)..."
        composer install --no-interaction --prefer-dist
        chown -R www-data:www-data vendor 2>/dev/null || true
    fi
}

fix_permissions
install_dev_dependencies
wait_for_database
run_migrations

if [ "${1:-}" = "php-fpm" ]; then
    optimize_app
fi

exec "$@"
