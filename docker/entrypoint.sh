#!/bin/sh
set -e

echo "Waiting for database at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
until php -r "
try {
    new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'mysql') . ';port=' . (getenv('DB_PORT') ?: '3306'), getenv('DB_USER') ?: 'root', getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
} catch (Exception \$e) {
    exit(1);
}
"; do
  echo "  database not ready yet, retrying in 2s..."
  sleep 2
done

echo "Applying database migrations..."
php /var/www/html/docker/run-migrations.php

echo "Starting Apache..."
exec "$@"
