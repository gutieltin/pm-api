#!/bin/bash

# Parse DATABASE_URL into individual components
# Format: postgresql://user:password@host:port/dbname
if [ -n "$DATABASE_URL" ]; then
    # Remove the protocol prefix
    WITHOUT_PROTO=$(echo $DATABASE_URL | sed 's/postgresql:\/\///')
    
    # Extract user
    DB_USER=$(echo $WITHOUT_PROTO | cut -d':' -f1)
    
    # Extract password (between first : and @)
    DB_PASS=$(echo $WITHOUT_PROTO | sed 's/[^:]*:\([^@]*\)@.*/\1/')
    
    # Extract host (between @ and :port)
    DB_HOST=$(echo $WITHOUT_PROTO | sed 's/.*@\([^:]*\):.*/\1/')
    
    # Extract port (between : and /)
    DB_PORT=$(echo $WITHOUT_PROTO | sed 's/.*:\([0-9]*\)\/.*/\1/')
    
    # Extract database name (after last /)
    DB_NAME=$(echo $WITHOUT_PROTO | sed 's/.*\/\(.*\)/\1/')
fi

echo "Database config: host=${DB_HOST} port=${DB_PORT} db=${DB_NAME} user=${DB_USER}"

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

# Run