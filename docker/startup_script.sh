#!/bin/bash

# Exit on error
set -e

# Function for logging
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

# Navigate to the Laravel project directory
cd /var/www/laravel

log "Starting Laravel application in production mode"

# Check if application key exists and is not empty
if grep -q "APP_KEY=" .env && grep -q "APP_KEY=base64:" .env; then
    log "Application key exists"
else
    log "Generating application key"
    php artisan key:generate --force
fi

# Check if JWT secret exists and is not empty
if grep -q "^JWT_SECRET=[^[:space:]]*[^[:space:]]" .env; then
    log "JWT secret exists"
else
    log "Generating JWT secret"
    php artisan jwt:secret -f
fi

# Clear all caches first
log "Clearing caches"
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Run database migrations and seeds with force flag
php artisan migrate --force
php artisan db:seed --force

# Cache for production
log "Building production cache"
php artisan config:cache
php artisan route:cache

log "Starting PHP-FPM"
exec php-fpm
