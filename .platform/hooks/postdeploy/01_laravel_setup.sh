#!/bin/bash

cd /var/app/current

# Set proper permissions
chown -R webapp:webapp /var/app/current
chmod -R 755 /var/app/current
chmod -R 775 /var/app/current/storage
chmod -R 775 /var/app/current/bootstrap/cache

# Create symlink for storage
php artisan storage:link --force

# Generate application key if not exists
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Cache configuration for better performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
php artisan migrate --force

# Clear and cache everything for production
php artisan optimize

echo "Laravel application setup completed"