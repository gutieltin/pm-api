#!/bin/bash

# Parse DATABASE_URL into individual components for Laravel
if [ -n "$DATABASE_URL" ]; then
    # Extract components from postgresql://user:password@host:port/dbname
    DB_USER=$(echo $DATABASE_URL | sed 's/.*:\/\/\([^:]*\):.*/\1/')
    DB_PASS=$(echo $DATABASE_URL | sed 's/.*:\/\/[^:]*:\([^@]*\)@.*/\1/')
    DB_HOST=$(echo $DATABASE_URL | sed 's/.*@\([^:\/]*\).*/\1/')
    DB_PORT=$(echo $DATABASE_URL | sed 's/.*:\([0-9]*\)\/.*/\1/')
    DB_NAME=$(echo $DATABASE_URL | sed 's/.*\/\([^?]*\).*/\1/')
fi

# Create .env file
cat > /var/www/html/.env << EOF
APP_NAME=ProjectFlow
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL}
FRONTEND_URL=${FRONTEND_URL}

LOG_CHANNEL=stderr

DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

MAIL_MAILER=${MAIL_MAILER:-smtp}
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT:-2525}
MAIL_USERNAME=${MAIL_USERNAME}
MAIL_PASSWORD=${MAIL_PASSWORD}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS}
MAIL_FROM_NAME=${MAIL_FROM_NAME}

QUEUE_CONNECTION=sync
SESSION_DRIVER=file
CACHE_STORE=file
EOF

echo "Database config: host=${DB_HOST} port=${DB_PORT} db=${DB_NAME} user=${DB_USER}"

# Run migrations
php /var/www/html/artisan migrate --force

# Cache config and routes
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:cache

# Start php-fpm in background
php-fpm -D

# Start nginx in foreground
nginx -g "daemon off;"