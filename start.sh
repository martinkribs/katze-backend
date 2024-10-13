#!/bin/bash

# Führe die Datenbankmigration durch
php artisan migrate --force

# Generiere den App-Schlüssel
php artisan key:generate
