#!/bin/bash
set -e

if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY environment variable is not set."
    exit 1
fi

echo "Caching config, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force

echo "Starting application..."
exec "$@"
