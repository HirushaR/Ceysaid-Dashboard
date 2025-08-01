# GitHub Secrets Setup Guide

## Required GitHub Secrets

To deploy to AWS Elastic Beanstalk, you need to configure the following secrets in your GitHub repository:

### 1. Go to GitHub Repository Settings
- Navigate to your repository
- Go to **Settings** → **Secrets and variables** → **Actions**

### 2. Add the following secrets:

#### **AWS_ACCESS_KEY_ID**
- Value: Your AWS Access Key ID
- Source: AWS IAM User credentials

#### **AWS_SECRET_ACCESS_KEY**
- Value: Your AWS Secret Access Key
- Source: AWS IAM User credentials

#### **EB_APPLICATION_NAME**
- Value: `ceysaid-app`
- Description: Your Elastic Beanstalk application name

#### **EB_ENVIRONMENT_NAME**
- Value: `ceysaid-production`
- Description: Your Elastic Beanstalk environment name

### 3. How to get AWS credentials:

#### **Create IAM User for GitHub Actions:**
1. Go to AWS IAM Console
2. Create a new user: `github-actions-ceysaid`
3. Attach policies:
   - `AWSElasticBeanstalkFullAccess`
   - `AmazonS3FullAccess`
   - `AmazonRDSFullAccess`
4. Create access keys
5. Download the CSV file with credentials

### 4. Test the setup:
- Push to main branch
- Check GitHub Actions tab
- Verify deployment success

### 5. Troubleshooting:
- If secrets are missing, the workflow will fail
- Check AWS credentials are correct
- Verify Elastic Beanstalk application exists
- Ensure S3 bucket permissions are correct
