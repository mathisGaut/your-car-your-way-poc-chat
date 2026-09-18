#!/bin/sh
set -e

cat > .env <<EOF
APP_NAME="${APP_NAME:-Your Car Your Way Chat POC}"
APP_ENV=${APP_ENV:-local}
APP_KEY=${APP_KEY:-base64:i/nYjegOeMVp0SofWutgluJGwQlEIpaGMeh3oGfPSTM=}
APP_DEBUG=${APP_DEBUG:-true}
APP_URL=${APP_URL:-http://localhost:8000}
DB_CONNECTION=${DB_CONNECTION:-pgsql}
DB_HOST=${DB_HOST:-postgres}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-ycyw_chat}
DB_USERNAME=${DB_USERNAME:-ycyw}
DB_PASSWORD=${DB_PASSWORD:-ycyw}
SESSION_DRIVER=${SESSION_DRIVER:-file}
CACHE_STORE=${CACHE_STORE:-file}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}
BROADCAST_CONNECTION=${BROADCAST_CONNECTION:-reverb}
REVERB_APP_ID=${REVERB_APP_ID:-ycyw-chat}
REVERB_APP_KEY=${REVERB_APP_KEY:-ycyw-chat-key}
REVERB_APP_SECRET=${REVERB_APP_SECRET:-ycyw-chat-secret}
REVERB_HOST=${REVERB_HOST:-localhost}
REVERB_PORT=${REVERB_PORT:-8080}
REVERB_SCHEME=${REVERB_SCHEME:-http}
REVERB_SERVER_HOST=${REVERB_SERVER_HOST:-0.0.0.0}
REVERB_SERVER_PORT=${REVERB_SERVER_PORT:-8080}
EOF

rm -f database/database.sqlite

echo "Waiting for PostgreSQL..."
until php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
  sleep 1
done

php artisan migrate --force

if [ "$(php artisan tinker --execute="echo App\\Models\\User::query()->count();")" = "0" ]; then
  php artisan db:seed --force
fi

php artisan reverb:start --host=0.0.0.0 --port=8080 &

exec php artisan serve --host=0.0.0.0 --port=8000
