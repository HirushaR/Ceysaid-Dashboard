# AWS Elastic Beanstalk Setup Guide (No CLI Required)

This guide will walk you through setting up AWS Elastic Beanstalk for your Laravel application using only the AWS Console and GitHub Actions for automated deployment.

## 📋 Prerequisites

- AWS Account with appropriate permissions
- GitHub repository with your Laravel application
- Domain name (optional, for custom domain)

## 🚀 Step-by-Step Setup

### Step 1: Create AWS Resources

#### 1.1 Create S3 Bucket for File Storage

1. **Go to S3 Console**
   - Navigate to: https://console.aws.amazon.com/s3/
   - Click "Create bucket"

2. **Configure Bucket**
   ```
   Bucket name: ceysaid-files-[your-region]
   Region: Choose your preferred region (e.g., us-east-1)
   Block all public access: ✅ (Keep checked)
   ```

3. **Set Bucket Policy** (for file uploads)
   ```json
   {
     "Version": "2012-10-17",
     "Statement": [
       {
         "Sid": "AllowElasticBeanstalkAccess",
         "Effect": "Allow",
         "Principal": {
           "Service": "elasticbeanstalk.amazonaws.com"
         },
         "Action": [
           "s3:GetObject",
           "s3:GetObjectVersion"
         ],
         "Resource": "arn:aws:s3:::ceysaid-files-[your-region]/*"
       }
     ]
   }
   ```

#### 1.2 Create RDS MySQL Database

1. **Go to RDS Console**
   - Navigate to: https://console.aws.amazon.com/rds/
   - Click "Create database"

2. **Configure Database**
   ```
   Engine type: MySQL
   Version: MySQL 8.0.35
   Template: Free tier (for testing) or Production
   
   Settings:
   - DB instance identifier: ceysaid-db
   - Master username: admin
   - Master password: [strong-password]
   
       Instance configuration:
    - Instance class: db.m5.large (free tier) or db.m6g.large
   
   Storage:
   - Allocated storage: 20 GB
   - Enable storage autoscaling: ✅
   
   Connectivity:
   - VPC: Default VPC
   - Public access: ✅ Yes
   - VPC security group: Create new (ceysaid-db-sg)
   - Availability Zone: No preference
   - Database port: 3306
   
   Database authentication:
   - Password authentication: ✅
   
   Additional configuration:
   - Initial database name: ceysaid_production
   - Enable automated backups: ✅
   - Backup retention period: 7 days
   ```

3. **Configure Security Group**
   - Go to EC2 Console → Security Groups
   - Find the created security group
   - Add inbound rule:
     ```
     Type: MySQL/Aurora
     Port: 3306
     Source: 0.0.0.0/0 (or specific IP)
     ```

#### 1.3 Create IAM User for GitHub Actions

1. **Go to IAM Console**
   - Navigate to: https://console.aws.amazon.com/iam/
   - Click "Users" → "Create user"

2. **Configure User**
   ```
   User name: github-actions-ceysaid
   Access type: Programmatic access
   ```

3. **Attach Policies**
   - Click "Attach existing policies directly"
   - Search and attach:
     - `AWSElasticBeanstalkFullAccess`
     - `AmazonS3FullAccess`
     - `AmazonRDSFullAccess`

4. **Save Credentials**
   - Download the CSV file with Access Key ID and Secret Access Key
   - Keep this secure for GitHub Secrets

### Step 2: Create Elastic Beanstalk Application

#### 2.1 Create Application

1. **Go to Elastic Beanstalk Console**
   - Navigate to: https://console.aws.amazon.com/elasticbeanstalk/
   - Click "Create application"

2. **Configure Application**
   ```
   Application name: ceysaid-app
   Platform: PHP
   Platform branch: PHP 8.2 running on 64bit Amazon Linux 2023
   Platform version: 4.0.0 (latest)
   ```

#### 2.2 Create Environment

1. **Environment Information**
   ```
   Environment name: ceysaid-production
   Domain: [auto-generated or custom]
   ```

2. **Platform Configuration**
   ```
   Platform: PHP 8.2
   Platform version: 4.0.0
   ```

  3. **Configure Instance**
     ```
     Instance type: t3.small (or t3.micro for testing)
     Single instance (free tier eligible)
     ```
     
   **Note:** For the database instance in RDS, use:
   - `db.m5.large` for free tier eligible
   - `db.m6g.large` for better performance (not free tier)

4. **Configure Security**
   ```
   EC2 key pair: Create new (ceysaid-key)
   IAM instance profile: aws-elasticbeanstalk-ec2-role
   ```

5. **Configure Networking**
   ```
   VPC: Default VPC
   Public IP: ✅ Enabled
   Instance subnets: Select all
   ```

  6. **Configure Database**
     ```
     Database: Include a database instance
     Engine: mysql
     Instance: db.m5.large
     Username: admin
     Password: [same as RDS]
     ```

### Step 3: Configure Environment Variables

1. **Go to Environment Configuration**
   - In your EB environment, click "Configuration"
   - Click "Edit" under "Software"

2. **Add Environment Variables**
   ```
   APP_NAME: Ceysaid CRM
   APP_ENV: production
   APP_DEBUG: false
   APP_URL: http://[your-eb-domain].elasticbeanstalk.com
   
   LOG_CHANNEL: errorlog
   LOG_LEVEL: error
   
   DB_CONNECTION: mysql
   DB_HOST: [your-rds-endpoint]
   DB_PORT: 3306
   DB_DATABASE: ceysaid_production
   DB_USERNAME: admin
   DB_PASSWORD: [your-db-password]
   
   CACHE_DRIVER: file
   FILESYSTEM_DISK: s3
   SESSION_DRIVER: file
   SESSION_LIFETIME: 120
   
   AWS_ACCESS_KEY_ID: [your-access-key]
   AWS_SECRET_ACCESS_KEY: [your-secret-key]
   AWS_DEFAULT_REGION: [your-region]
   AWS_BUCKET: ceysaid-files-[your-region]
   AWS_URL: https://ceysaid-files-[your-region].s3.[your-region].amazonaws.com
   
   MAIL_MAILER: log
   ```

3. **Generate APP_KEY**
   ```bash
   # Run this locally
   php artisan key:generate --show
   ```
   Copy the output and add as `APP_KEY` environment variable.

### Step 4: Configure GitHub Secrets

1. **Go to GitHub Repository**
   - Navigate to your repository
   - Go to Settings → Secrets and variables → Actions

2. **Add Repository Secrets**
   ```
   AWS_ACCESS_KEY_ID: [from IAM user]
   AWS_SECRET_ACCESS_KEY: [from IAM user]
   AWS_REGION: [your-region]
   EB_APPLICATION_NAME: ceysaid-app
   EB_ENVIRONMENT_NAME: ceysaid-production
   ```

### Step 5: Deploy Application

#### 5.1 Initial Deployment

1. **Upload Application**
   - In EB Console, click "Upload and deploy"
   - Create a ZIP file of your application (excluding node_modules, vendor, .git)
   - Upload the ZIP file

2. **Monitor Deployment**
   - Watch the deployment progress
   - Check logs if there are issues

#### 5.2 Configure GitHub Actions

1. **Push to Main Branch**
   ```bash
   git add .
   git commit -m "Initial deployment setup"
   git push origin main
   ```

2. **Monitor GitHub Actions**
   - Go to Actions tab in GitHub
   - Watch the deployment workflow

### Step 6: Post-Deployment Configuration

#### 6.1 Run Database Migrations

1. **SSH into Instance**
   - In EB Console, click "SSH" to connect to instance
   - Run: `php artisan migrate --force`

#### 6.2 Configure Storage

1. **Create Storage Link**
   ```bash
   php artisan storage:link
   ```

#### 6.3 Set Permissions

1. **Fix Permissions**
   ```bash
   sudo chown -R webapp:webapp /var/app/current
   sudo chmod -R 755 /var/app/current
   sudo chmod -R 775 /var/app/current/storage
   sudo chmod -R 775 /var/app/current/bootstrap/cache
   ```

### Step 7: Monitoring and Maintenance

#### 7.1 Set Up Monitoring

1. **CloudWatch Alarms**
   - CPU utilization > 80%
   - Memory utilization > 80%
   - Disk space > 85%

2. **Log Monitoring**
   - Enable log streaming
   - Set up log retention

#### 7.2 Backup Strategy

1. **Database Backups**
   - RDS automated backups
   - Manual snapshots for major changes

2. **Application Backups**
   - S3 bucket versioning
   - Cross-region replication

## 🔧 Troubleshooting

### Common Issues

1. **Deployment Failures**
   - Check EB logs in console
   - Verify environment variables
   - Check file permissions

2. **Database Connection Issues**
   - Verify RDS security group
   - Check database credentials
   - Ensure RDS is accessible

3. **File Upload Issues**
   - Verify S3 bucket permissions
   - Check AWS credentials
   - Ensure bucket exists

4. **Performance Issues**
   - Monitor CloudWatch metrics
   - Check instance type
   - Optimize database queries

### Useful Commands

```bash
# SSH into EB instance
eb ssh

# View logs
eb logs

# Check environment status
eb status

# View application logs
tail -f /var/app/current/storage/logs/laravel.log
```

## 📊 Cost Optimization

1. **Instance Types**
   - Use t3.micro for development
   - Scale up based on traffic

2. **Auto Scaling**
   - Configure based on CPU/memory
   - Set minimum/maximum instances

3. **Database**
   - Use appropriate instance types
   - Enable storage autoscaling

## 🔒 Security Best Practices

1. **Network Security**
   - Use VPC with private subnets
   - Configure security groups properly

2. **Data Protection**
   - Enable encryption at rest
   - Use HTTPS for all traffic

3. **Access Control**
   - Use IAM roles instead of access keys
   - Implement least privilege access

## 📈 Scaling Considerations

1. **Horizontal Scaling**
   - Configure auto scaling groups
   - Use load balancer

2. **Database Scaling**
   - Consider read replicas
   - Implement connection pooling

3. **Caching**
   - Use ElastiCache for Redis
   - Implement CDN for static assets

## 🎯 Next Steps

1. **Custom Domain**
   - Configure Route 53
   - Set up SSL certificate

2. **CI/CD Pipeline**
   - Configure GitHub Actions
   - Set up staging environment

3. **Monitoring**
   - Set up CloudWatch dashboards
   - Configure alerting

4. **Backup Strategy**
   - Implement automated backups
   - Test recovery procedures 