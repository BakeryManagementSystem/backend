 #!/bin/bash
set -e

echo "Starting Laravel deployment..."

# Wait for database to be ready
echo "Waiting for database connection..."
php artisan db:monitor --max-attempts=30

# Clear and cache config for production
echo "Optimizing Laravel for production..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

echo "Laravel deployment completed successfully!"
