# Use PHP 8.3 FPM image as the base image
FROM php:8.3-fpm

# Set the working directory in the container
WORKDIR /var/www/laravel

# Install production dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libsodium-dev \
    libsodium23 \
    libonig-dev \
    zip

# Install php extensions
RUN docker-php-ext-install -j$(nproc) \
        zip \
        pdo \
        pdo_mysql \
        sodium \
        mbstring

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Copy the entire Laravel application
COPY laravel /var/www/laravel

# Copy .env example file if .env doesn't exist
COPY laravel/.env.example /var/www/laravel/.env

# Set permissions and install dependencies
RUN chown -R www-data:www-data /var/www/laravel \
    && chmod -R 775 /var/www/laravel/storage \
    && chmod -R 775 /var/www/laravel/bootstrap/cache \
    && composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader \
    && composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/laravel

# Create logs directory
RUN mkdir -p /var/www/logs \
    && chown -R www-data:www-data /var/www/logs

# Remove composer and other build dependencies
RUN rm -rf /usr/local/bin/composer \
    && rm -rf /var/www/laravel/.git \
    && rm -rf /var/www/laravel/tests \
    && rm -rf /var/www/laravel/storage/framework/cache/* \
    && rm -rf /var/www/laravel/storage/framework/views/* \
    && rm -rf /var/www/laravel/storage/framework/sessions/*

# Configure PHP-FPM for production
RUN echo "pm.max_children = 50" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 5" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 35" >> /usr/local/etc/php-fpm.d/www.conf

# Expose port 9000
EXPOSE 9000

# Copy startup script into the image and make it executable
COPY docker/startup_script.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Set the command to run the startup script
CMD ["/usr/local/bin/start.sh"]
