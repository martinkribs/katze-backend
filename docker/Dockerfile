# php:8.3-fpm base — we don't actually run php-fpm (see startup_script.sh, which runs
# `php artisan serve` instead), but this image bundles the standalone `php` CLI too and
# is already verified working, so kept as-is rather than switching to php:8.3-cli.
FROM php:8.3-fpm

# Set the working directory in the container
WORKDIR /var/www/laravel

# Install production dependencies
# openssh-server: this container is a katze-platform `service` — direct SSH access via the
# SSH Bastion is part of the model (see dev-infra docs/adr/0001-workspace-service-split.md).
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libsodium-dev \
    libsodium23 \
    libonig-dev \
    libpq-dev \
    openssh-server \
    zip

# Install php extensions
RUN docker-php-ext-install -j$(nproc) \
        zip \
        pdo \
        pdo_pgsql \
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

# Set up storage directory structure
RUN mkdir -p /var/www/laravel/storage/app/public \
    && mkdir -p /var/www/laravel/storage/framework/{cache,sessions,testing,views} \
    && mkdir -p /var/www/laravel/storage/logs \
    && chmod -R 775 /var/www/laravel/storage \
    && chown -R www-data:www-data /var/www/laravel/storage

# Set permissions and install dependencies
RUN chmod -R 775 /var/www/laravel/bootstrap/cache \
    && composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader \
    && composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/laravel

# Remove composer and other build dependencies
RUN rm -rf /usr/local/bin/composer \
    && rm -rf /var/www/laravel/.git \
    && rm -rf /var/www/laravel/tests

# /var/www/laravel becomes a PVC-backed live-edit path at runtime (see dev-infra
# ADR-0001 / devspaces/*/api-deployment.yaml, mountPath /var/www/laravel) — the PVC starts
# empty, so the built app is relocated to this immutable reference copy, and
# startup_script.sh seeds /var/www/laravel from it on first boot only (subsequent boots
# and dev edits persist across restarts since the PVC is never re-seeded once non-empty).
RUN mv /var/www/laravel /opt/app-src

# SSH setup — host keys generated at build time, sshd runs in the foreground alongside
# the app (see startup_script.sh). Client key trust comes from the SSH Bastion's sync
# sidecar; the exact bastion<->upstream trust chain is still unverified, see
# dev-infra docs/adr/0002-ssh-bastion.md.
RUN mkdir -p /var/run/sshd && ssh-keygen -A

# Expose the dev server (php artisan serve) and SSH
EXPOSE 8000 22

# Copy startup script into the image and make it executable
COPY docker/startup_script.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Set the command to run the startup script
CMD ["/usr/local/bin/start.sh"]
