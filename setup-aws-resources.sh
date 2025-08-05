#!/bin/bash

# AWS Resources Setup Script for Ceysaid CRM
# This script creates all necessary AWS resources for deployment

set -e

echo "🚀 Setting up AWS Resources for Ceysaid CRM..."

# Configuration
APP_NAME="ceysaid-dev-app"
ENV_NAME="Ceysaid-dev-env"
REGION="ap-south-1"
S3_BUCKET="ceysaid-deployments-ap-south-1"
DB_INSTANCE_ID="ceysaid-dev-db"
DB_NAME="ceysaid_dev"

echo "📋 Configuration:"
echo "   Application: $APP_NAME"
echo "   Environment: $ENV_NAME"
echo "   Region: $REGION"
echo "   S3 Bucket: $S3_BUCKET"
echo "   Database: $DB_INSTANCE_ID"

# Check if AWS CLI is configured
if ! aws sts get-caller-identity &> /dev/null; then
    echo "❌ AWS CLI is not configured. Please run 'aws configure' first."
    exit 1
fi

# Create S3 bucket for deployments
echo "📦 Creating S3 bucket for deployments..."
aws s3 mb s3://$S3_BUCKET --region $REGION || echo "Bucket already exists"

# Create S3 bucket for application files
echo "📁 Creating S3 bucket for application files..."
aws s3 mb s3://ceysaid-files-$REGION --region $REGION || echo "Bucket already exists"

# Create EB Application
echo "🔧 Creating Elastic Beanstalk Application..."
aws elasticbeanstalk create-application \
    --application-name $APP_NAME \
    --description "Ceysaid CRM Development Environment" \
    --region $REGION || echo "Application already exists"

# Create EB Environment
echo "🌍 Creating Elastic Beanstalk Environment..."
aws elasticbeanstalk create-environment \
    --application-name $APP_NAME \
    --environment-name $ENV_NAME \
    --solution-stack-name "64bit Amazon Linux 2023 v4.0.0 running PHP 8.3" \
    --option-settings \
        Namespace=aws:autoscaling:launchconfiguration,OptionName=IamInstanceProfile,Value=aws-elasticbeanstalk-ec2-role \
        Namespace=aws:ec2:instances,OptionName=InstanceTypes,Value=t3.micro \
        Namespace=aws:autoscaling:asg,OptionName=MinSize,Value=1 \
        Namespace=aws:autoscaling:asg,OptionName=MaxSize,Value=2 \
        Namespace=aws:elasticbeanstalk:environment,OptionName=EnvironmentType,Value=SingleInstance \
    --region $REGION || echo "Environment already exists"

echo "✅ AWS Resources created successfully!"
echo ""
echo "📝 Next steps:"
echo "1. Wait for environment to be ready (check AWS Console)"
echo "2. Configure environment variables in EB Console:"
echo "   - APP_KEY (generate with: php artisan key:generate --show)"
echo "   - Database credentials (RDS)"
echo "   - AWS S3 credentials and bucket name"
echo "3. Set up RDS database if needed"
echo "4. Configure S3 bucket for file storage"
echo ""
echo "🔗 Useful commands:"
echo "   aws elasticbeanstalk describe-environments --environment-names $ENV_NAME --region $REGION"
echo "   aws elasticbeanstalk describe-applications --application-names $APP_NAME --region $REGION"
echo "   aws s3 ls s3://$S3_BUCKET --region $REGION" 