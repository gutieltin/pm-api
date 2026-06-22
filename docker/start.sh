#!/bin/bash

# Create .env file from Render environment variables
cat > /var/www/html/.env << EOF
APP_NAME=ProjectFlow
APP_ENV=${APP_ENV}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG}
APP_URL=${APP_URL}
FRONTEND_URL=${FRONTEND_URL}

LOG_CHANNEL=stderr

DB_CONNECTION=pgsql
DATABASE_URL=${DATABASE_URL}

MAIL_MAILER=${MAIL_MAILER}
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT}
MAIL_USERNAME=${MAIL_USERNAME}
MAIL_PASSWORD=${MAIL_PASSWORD}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS}
MAIL_FROM_NAME=${MAIL_FROM_NAME}

QUEUE_CONNECTION=sync
EOF

# Run migrations
php /var/www/html/artisan migrate --force

# Cache config and routes
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:cache

# Start php-fpm in background
php-fpm -D

# Start nginx in foreground
nginx -g "daemon off;"