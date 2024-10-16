#!/bin/bash

# Navigate to the project directory
cd /var/www

# Navigate to the Laravel project directory
cd laravel

# Check if application key exists and is not empty
if grep -q "APP_KEY=" .env && grep -q "APP_KEY=base64:" .env; then
    echo "Application key already exists. Skipping."
else
    # Generate application key
    echo "No application key found. Generating."
    php artisan key:generate
fi
# Echo the Laravel information like version and environment
php artisan --version
php artisan env

# Run database migrations
php artisan migrate

# Start PHP-FPM
php-fpm