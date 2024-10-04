FROM php:8.1-fpm

# Installieren Sie Abhängigkeiten
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Löschen Sie den apt-cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Installieren Sie PHP-Erweiterungen
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installieren Sie Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Setzen Sie das Arbeitsverzeichnis
WORKDIR /var/www/html

# Kopieren Sie den bestehenden Anwendungscode
COPY . .

# Installieren Sie die Anwendungsabhängigkeiten
RUN composer install

# Ändern Sie die Besitzrechte des Speicherverzeichnisses
RUN chown -R www-data:www-data storage

# Exponieren Sie Port 8000 und starten Sie den PHP-Server
EXPOSE 8000
CMD php artisan serve --host=0.0.0.0 --port=8000
