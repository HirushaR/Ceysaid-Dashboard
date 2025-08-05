#!/bin/bash

# AWS Elastic Beanstalk Application Setup Script
# This script creates the EB application and environment

set -e

echo "🚀 Setting up AWS Elastic Beanstalk Application..."

# Configuration
APP_NAME="Ceysaid-dev-env"
ENV_NAME="ceysaid-dev-app"
REGION="ap-south-1"
PLATFORM="64bit Amazon Linux 2023 v4.0.0 running PHP 8.3"

echo "📋 Application: $APP_NAME"
echo "🌍 Environment: $ENV_NAME"
echo "📍 Region: $REGION"

# Check if AWS CLI is configured
if ! aws sts get-caller-identity &> /dev/null; then
    echo "❌ AWS CLI is not configured. Please run 'aws configure' first."
    exit 1
fi

# Create EB Application
echo "🔧 Creating Elastic Beanstalk Application..."
aws elasticbeanstalk create-application \
    --application-name $APP_NAME \
    --description "Ceysaid CRM Development Environment" \
    --region $REGION

echo "✅ Application created successfully!"

# Create EB Environment
echo "🌍 Creating Elastic Beanstalk Environment..."
aws elasticbeanstalk create-environment \
    --application-name $APP_NAME \
    --environment-name $ENV_NAME \
    --solution-stack-name "$PLATFORM" \
    --option-settings \
        Namespace=aws:autoscaling:launchconfiguration,OptionName=IamInstanceProfile,Value=aws-elasticbeanstalk-ec2-role \
        Namespace=aws:ec2:instances,OptionName=InstanceTypes,Value=t3.micro \
        Namespace=aws:autoscaling:asg,OptionName=MinSize,Value=1 \
        Namespace=aws:autoscaling:asg,OptionName=MaxSize,Value=2 \
    --region $REGION

echo "✅ Environment created successfully!"
echo ""
echo "📝 Next steps:"
echo "1. Wait for environment to be ready (check AWS Console)"
echo "2. Configure environment variables in EB Console"
echo "3. Set up RDS database if needed"
echo "4. Configure S3 bucket for file storage"
echo ""
echo "🔗 Useful commands:"
echo "   aws elasticbeanstalk describe-environments --environment-names $ENV_NAME --region $REGION"
echo "   aws elasticbeanstalk describe-applications --application-names $APP_NAME --region $REGION" 