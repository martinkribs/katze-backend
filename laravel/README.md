# Katze-Backend

Dieses Projekt ist ein Laravel-basiertes Backend mit Docker-Unterstützung.

## Voraussetzungen

- Docker
- Docker Compose

## Installation

1. Klonen Sie das Repository:
   ```
   git clone https://github.com/martinkribs/katze-backend.git
   cd katze-backend
   ```

2. Kopieren Sie die `.env.example`-Datei zu `.env` und passen Sie die Einstellungen an:
   ```
   cp .env.example .env
   ```

3. Starten Sie die Docker-Container:
   ```
   docker-compose up -d
   ```

4. Installieren Sie die PHP-Abhängigkeiten:
   ```
   docker-compose exec app composer install
   ```

5. Generieren Sie den Application Key:
   ```
   docker-compose exec app php artisan key:generate
   ```

6. Führen Sie die Migrationen aus:
   ```
   docker-compose exec app php artisan migrate
   ```

## Verwendung

Die API ist unter `http://localhost:8000` erreichbar.
