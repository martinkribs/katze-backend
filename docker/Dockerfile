# Use PHP 8.3 FPM image as the base image (Update this to 8.3 when it's available)
FROM php:8.3-fpm
# Set the working directory in the container
WORKDIR /var/www
# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libsodium-dev libsodium23 \
    libxml2-dev \
    zip \
    && docker-php-ext-install zip pdo pdo_mysql gd sodium simplexml

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install laravel via composer
RUN cd /var/www/ && composer create-project --prefer-dist laravel/laravel laravel
COPY laravel/* /var/www/laravel/
COPY environment/* /var/www/laravel/

# Remove the default Nginx index page
RUN rm -rf /var/www/html
# Create a symbolic link from the Nginx document root to the Laravel public directory
RUN ln -s /var/www/laravel/public /var/www/html

# Create a logs directory
RUN mkdir /var/www/logs

# Change the user to www-data
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN cd /var/www/laravel && composer install

# Expose port 9000 and start php-fpm server
EXPOSE 9000
# Copy startup script into the image and make it executable
COPY docker/startup_script.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh


CMD ["/usr/local/bin/start.sh"]