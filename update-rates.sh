#!/bin/bash

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "Uruchamianie aktualizacji kursów walut..."

cd "$PROJECT_DIR"
php bin/console app:update-rates

echo "Aktualizacja zakończona."
