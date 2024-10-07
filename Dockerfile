FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy codebase
COPY . .

# Install Composer dependencies
RUN composer install --no-interaction --no-dev --prefer-dist

# Generate Application Key
RUN php artisan key:generate

# Run Migrations
RUN php artisan migrate --force

# Optimize Application
RUN php artisan optimize

# Change owner
RUN chown -R www-data:www-data storage bootstrap/cache

# Expose port 8000 and start Laravel development server
EXPOSE 8000
CMD php artisan serve --host=0.0.0.0 --port=8000
