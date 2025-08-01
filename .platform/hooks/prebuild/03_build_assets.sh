#!/bin/bash

cd /var/app/staging

# Install npm dependencies
npm ci --production

# Build assets using Vite
npm run build

echo "Assets built successfully"