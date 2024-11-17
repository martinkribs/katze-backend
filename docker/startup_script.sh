#!/bin/bash

# Navigate to the Laravel project directory
cd /var/www/laravel

# Check if application key exists and is not empty
if grep -q "APP_KEY=" .env && grep -q "APP_KEY=base64:" .env; then
    echo "Application key already exists. Skipping."
else
    # Generate application key
    echo "No application key found. Generating."
    php artisan key:generate --force
fi

# Check if JWT secret exists and is not empty
if grep -q "^JWT_SECRET=[^[:space:]]*[^[:space:]]" .env; then
    echo "JWT secret already exists. Skipping."
else
    # Generate JWT secret
    echo "No JWT secret found. Generating."
    php artisan jwt:secret -f
fi

# Echo the Laravel information
php artisan --version

# Clear all caches first
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations and seeds with force flag
php artisan migrate --force
php artisan db:seed --force

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Start PHP-FPM
php-fpm
