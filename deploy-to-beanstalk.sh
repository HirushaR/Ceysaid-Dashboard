#!/bin/bash

# AWS Elastic Beanstalk Deployment Script for Laravel Application
# This script prepares and deploys the application to AWS Elastic Beanstalk

set -e

echo "🚀 Starting AWS Elastic Beanstalk deployment preparation..."

# Configuration
APP_NAME="ceysaid-app"
ENV_NAME="ceysaid-production"
REGION="us-east-1"
PLATFORM="64bit Amazon Linux 2023 v4.0.0 running PHP 8.3"

echo "📋 Application: $APP_NAME"
echo "🌍 Environment: $ENV_NAME"
echo "📍 Region: $REGION"

# Check if EB CLI is installed
if ! command -v eb &> /dev/null; then
    echo "❌ EB CLI is not installed. Please install it first:"
    echo "pip install awsebcli"
    exit 1
fi

# Check if AWS CLI is configured
if ! aws sts get-caller-identity &> /dev/null; then
    echo "❌ AWS CLI is not configured. Please run 'aws configure' first."
    exit 1
fi

# Create deployment directory
DEPLOY_DIR="beanstalk-deploy"
rm -rf $DEPLOY_DIR
mkdir $DEPLOY_DIR

echo "📦 Copying application files..."

# Copy application files (excluding development files)
rsync -av --progress . $DEPLOY_DIR/ \
    --exclude=node_modules \
    --exclude=.git \
    --exclude=vendor \
    --exclude=.env \
    --exclude=.env.* \
    --exclude=storage/logs \
    --exclude=storage/framework/cache \
    --exclude=storage/framework/sessions \
    --exclude=storage/framework/views \
    --exclude=tests \
    --exclude=.phpunit.result.cache \
    --exclude=beanstalk-deploy

cd $DEPLOY_DIR

# Create .env file for production
cat > .env << EOF
APP_NAME="Ceysaid CRM"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=

LOG_CHANNEL=errorlog
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

CACHE_STORE=file
FILESYSTEM_DISK=s3
SESSION_DRIVER=file
SESSION_LIFETIME=120

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_URL=

MAIL_MAILER=log
EOF

echo "✅ Environment file created (remember to set variables in EB Console)"

# Initialize EB if not already initialized
if [ ! -f .elasticbeanstalk/config.yml ]; then
    echo "🔧 Initializing Elastic Beanstalk..."
    eb init $APP_NAME --platform "$PLATFORM" --region $REGION
fi

echo "🎯 Deployment package prepared in $DEPLOY_DIR/"
echo ""
echo "📝 Next steps:"
echo "1. cd $DEPLOY_DIR"
echo "2. Set environment variables in EB Console:"
echo "   - APP_KEY (generate with: php artisan key:generate --show)"
echo "   - Database credentials (RDS)"
echo "   - AWS S3 credentials and bucket name"
echo "3. Create environment: eb create $ENV_NAME"
echo "4. Deploy: eb deploy"
echo ""
echo "🔗 Useful commands:"
echo "   eb open          - Open application in browser"
echo "   eb logs          - View application logs"
echo "   eb ssh           - SSH into instance"
echo "   eb status        - Check environment status"

echo ""
echo "⚠️  Important: Configure RDS database and S3 bucket before deployment!"
echo "✅ Deployment preparation completed!"