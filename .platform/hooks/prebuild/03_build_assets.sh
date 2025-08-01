#!/bin/bash

cd /var/app/staging

# Install npm dependencies
npm install --legacy-peer-deps

# Build assets using Vite
npm run build || (rm -rf node_modules package-lock.json && npm install --legacy-peer-deps && npm run build)

echo "Assets built successfully"