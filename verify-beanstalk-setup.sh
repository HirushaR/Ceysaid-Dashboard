#!/bin/bash

# Verify Elastic Beanstalk Setup Script
# This script checks your current EB configuration

set -e

echo "🔍 Verifying Elastic Beanstalk Setup..."

# Configuration
APP_NAME="ceysaid-dev-app"
ENV_NAME="Ceysaid-dev-env"
REGION="ap-south-1"

echo "📋 Checking configuration:"
echo "   Application: $APP_NAME"
echo "   Environment: $ENV_NAME"
echo "   Region: $REGION"

# Check if AWS CLI is configured
if ! aws sts get-caller-identity &> /dev/null; then
    echo "❌ AWS CLI is not configured locally."
    echo "   This is okay if you're using GitHub Actions for deployment."
    echo "   The deployment will use GitHub Secrets for AWS credentials."
else
    echo "✅ AWS CLI is configured locally."
    
    # Check EB Application
    echo "🔧 Checking EB Application..."
    if aws elasticbeanstalk describe-applications --application-names $APP_NAME --region $REGION &>/dev/null; then
        echo "✅ EB Application '$APP_NAME' exists"
        
        # Get application details
        APP_STATUS=$(aws elasticbeanstalk describe-applications --application-names $APP_NAME --region $REGION --query 'Applications[0].Status' --output text)
        echo "   Status: $APP_STATUS"
    else
        echo "❌ EB Application '$APP_NAME' does not exist"
    fi
    
    # Check EB Environment
    echo "🌍 Checking EB Environment..."
    if aws elasticbeanstalk describe-environments --environment-names $ENV_NAME --region $REGION &>/dev/null; then
        echo "✅ EB Environment '$ENV_NAME' exists"
        
        # Get environment details
        ENV_STATUS=$(aws elasticbeanstalk describe-environments --environment-names $ENV_NAME --region $REGION --query 'Environments[0].Status' --output text)
        ENV_HEALTH=$(aws elasticbeanstalk describe-environments --environment-names $ENV_NAME --region $REGION --query 'Environments[0].Health' --output text)
        ENV_URL=$(aws elasticbeanstalk describe-environments --environment-names $ENV_NAME --region $REGION --query 'Environments[0].CNAME' --output text)
        
        echo "   Status: $ENV_STATUS"
        echo "   Health: $ENV_HEALTH"
        echo "   URL: http://$ENV_URL"
    else
        echo "❌ EB Environment '$ENV_NAME' does not exist"
    fi
    
    # Check S3 Bucket
    echo "📦 Checking S3 Bucket..."
    if aws s3 ls s3://ceysaid-deployments-ap-south-1 --region $REGION &>/dev/null; then
        echo "✅ S3 Bucket 'ceysaid-deployments-ap-south-1' exists"
    else
        echo "❌ S3 Bucket 'ceysaid-deployments-ap-south-1' does not exist"
    fi
fi

echo ""
echo "📝 GitHub Actions Configuration:"
echo "   Make sure these secrets are set in your GitHub repository:"
echo "   - AWS_ACCESS_KEY_ID"
echo "   - AWS_SECRET_ACCESS_KEY"
echo "   - EB_APPLICATION_NAME: $APP_NAME"
echo "   - EB_ENVIRONMENT_NAME: $ENV_NAME"
echo ""
echo "🔗 Useful commands:"
echo "   aws elasticbeanstalk describe-applications --application-names $APP_NAME --region $REGION"
echo "   aws elasticbeanstalk describe-environments --environment-names $ENV_NAME --region $REGION"
echo "   aws s3 ls s3://ceysaid-deployments-ap-south-1 --region $REGION" 