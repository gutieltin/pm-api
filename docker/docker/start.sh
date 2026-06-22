#!/bin/bash

# Generate app key if not set
php /var/www/html/artisan key:generate --force

# Run migrations
php /var/www/html/artisan migrate --force

# Cache config and routes
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:cache

# Start php-fpm in background
php-fpm -D

# Start nginx in foreground
nginx -g "daemon off;"