#!/bin/bash

cd /var/app/staging

# Install Composer dependencies
/usr/bin/composer.phar install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

echo "Composer dependencies installed successfully"