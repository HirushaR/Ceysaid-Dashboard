#!/bin/bash

# Install Node.js 18 (required for Vite)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
nvm install 18
nvm use 18
nvm alias default 18

# Make node and npm available globally
ln -sf $NVM_DIR/versions/node/v18*/bin/node /usr/bin/node
ln -sf $NVM_DIR/versions/node/v18*/bin/npm /usr/bin/npm
ln -sf $NVM_DIR/versions/node/v18*/bin/npx /usr/bin/npx

echo "Node.js version: $(node --version)"
echo "NPM version: $(npm --version)"