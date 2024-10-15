# Use PHP 8.3 FPM image as the base image
FROM php:8.3-fpm

# Set the working directory in the container
WORKDIR /var/www

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libsodium-dev \
    libsodium23 \
    libxml2-dev \
    zip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) zip pdo pdo_mysql gd sodium simplexml

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Create Laravel project
RUN composer create-project --prefer-dist laravel/laravel:^10.0 laravel

# Copy existing application directory contents
COPY laravel /var/www/laravel
COPY environment /var/www/laravel

# Set permissions
RUN chown -R www-data:www-data /var/www/laravel \
    && chmod -R 775 /var/www/laravel/storage

# Remove the default Nginx index page
RUN rm -rf /var/www/html

# Create a symbolic link from the Nginx document root to the Laravel public directory
RUN ln -s /var/www/laravel/public /var/www/html

# Create a logs directory
RUN mkdir /var/www/logs

# Install project dependencies
RUN cd /var/www/laravel && composer install --no-interaction --no-dev --prefer-dist

# Expose port 9000
EXPOSE 9000

# Copy startup script into the image and make it executable
COPY docker/startup_script.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Set the command to run the startup script
CMD ["/usr/local/bin/start.sh"]
