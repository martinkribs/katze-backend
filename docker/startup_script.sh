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

# Check if JWT secret exists and is not empty
if grep -q "^JWT_SECRET=[^[:space:]]*[^[:space:]]" .env; then
    echo "JWT secret already exists. Skipping."
else
    # Generate JWT secret
    echo "No JWT secret found. Generating."
    php artisan jwt:secret -f
fi

# Echo the Laravel information like version and environment
php artisan --version
php artisan env

# Run database migrations
php artisan migrate

# Run database seeders
php artisan db:seed

# Start PHP-FPM
php-fpm