#!/bin/bash

# Exit on error
set -e

# Function for logging
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

# /var/www/laravel is a PVC-backed, live-edit path at runtime (see dev-infra ADR-0001) and
# starts empty on a fresh volume — seed it from the image's reference copy on first boot
# only. Once seeded, dev edits and pod restarts leave it alone.
if [ ! -f /var/www/laravel/artisan ]; then
    log "Seeding /var/www/laravel from image (first boot on this volume)"
    cp -a /opt/app-src/. /var/www/laravel/
    chown -R www-data:www-data /var/www/laravel
fi

# Navigate to the Laravel project directory
cd /var/www/laravel

log "Starting Laravel application (katze-platform dev service)"

# SSH access via the SSH Bastion (see dev-infra docs/adr/0002-ssh-bastion.md)
log "Starting sshd"
/usr/sbin/sshd

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

log "Starting dev server on :8000"
exec php artisan serve --host=0.0.0.0 --port=8000
