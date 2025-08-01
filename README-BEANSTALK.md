# AWS Elastic Beanstalk Deployment Guide

This guide will help you deploy your Laravel application to AWS Elastic Beanstalk.

## Prerequisites

1. **AWS Account** - You need an active AWS account
2. **AWS CLI** - Install and configure AWS CLI
3. **EB CLI** - Install Elastic Beanstalk CLI
4. **RDS Database** - MySQL database instance
5. **S3 Bucket** - For file storage

## Quick Start

### 1. Install Required Tools

```bash
# Install AWS CLI
curl "https://awscli.amazonaws.com/AWSCLIV2.pkg" -o "AWSCLIV2.pkg"
sudo installer -pkg AWSCLIV2.pkg -target /

# Install EB CLI
pip install awsebcli

# Configure AWS
aws configure
```

### 2. Prepare Your Environment

```bash
# Run the deployment preparation script
./deploy-to-beanstalk.sh
```

### 3. Set Up AWS Resources

#### Create RDS Database
1. Go to AWS RDS Console
2. Create a MySQL 8.0 database
3. Note the endpoint, database name, username, and password

#### Create S3 Bucket
1. Go to AWS S3 Console
2. Create a bucket for file storage
3. Configure CORS if needed

### 4. Configure Environment Variables

In the Elastic Beanstalk Console:

**Required Variables:**
- `APP_KEY` - Generate with: `php artisan key:generate --show`
- `DB_HOST` - Your RDS endpoint
- `DB_DATABASE` - Database name
- `DB_USERNAME` - Database username
- `DB_PASSWORD` - Database password
- `AWS_ACCESS_KEY_ID` - Your AWS access key
- `AWS_SECRET_ACCESS_KEY` - Your AWS secret key
- `AWS_BUCKET` - Your S3 bucket name
- `APP_URL` - Your application URL

**Optional Variables:**
- `APP_NAME` - Application name
- `LOG_LEVEL` - Logging level (error, warning, info, debug)
- `CACHE_DRIVER` - Cache driver (file, redis, memcached)
- `SESSION_DRIVER` - Session driver (file, redis, database)

### 5. Deploy

```bash
cd beanstalk-deploy

# Create environment (first time only)
eb create ceysaid-production

# Deploy updates
eb deploy
```

## Configuration Files

### `.ebextensions/`
- `01_packages.config` - Install required packages
- `02_php_settings.config` - PHP configuration
- `03_environment.config` - Environment variables
- `04_cron_jobs.config` - Scheduled tasks

### `.platform/`
- `nginx/conf.d/laravel.conf` - Nginx configuration
- `hooks/prebuild/` - Pre-build scripts
- `hooks/postdeploy/` - Post-deployment scripts

## File Structure

```
.ebextensions/
├── 01_packages.config
├── 02_php_settings.config
├── 03_environment.config
└── 04_cron_jobs.config

.platform/
├── nginx/
│   └── conf.d/
│       └── laravel.conf
└── hooks/
    ├── prebuild/
    │   ├── 01_install_node.sh
    │   ├── 02_install_composer_dependencies.sh
    │   └── 03_build_assets.sh
    └── postdeploy/
        └── 01_laravel_setup.sh
```

## Troubleshooting

### Common Issues

1. **Permission Errors**
   ```bash
   # SSH into instance
   eb ssh
   
   # Fix permissions
   sudo chown -R webapp:webapp /var/app/current
   sudo chmod -R 755 /var/app/current
   ```

2. **Database Connection Issues**
   - Check RDS security group allows EB instances
   - Verify database credentials in environment variables

3. **File Upload Issues**
   - Check S3 bucket permissions
   - Verify AWS credentials

4. **Asset Build Failures**
   - Check Node.js installation in prebuild hooks
   - Verify npm dependencies

### Useful Commands

```bash
# View logs
eb logs

# SSH into instance
eb ssh

# Open application
eb open

# Check status
eb status

# List environments
eb list
```

## Security Considerations

1. **Environment Variables** - Never commit sensitive data to version control
2. **Database Security** - Use RDS with proper security groups
3. **S3 Permissions** - Configure bucket policies appropriately
4. **HTTPS** - Enable SSL/TLS for production

## Performance Optimization

1. **OPcache** - Enabled in PHP configuration
2. **Asset Caching** - Static assets cached for 1 year
3. **Database Indexing** - Ensure proper database indexes
4. **CDN** - Consider using CloudFront for static assets

## Monitoring

1. **CloudWatch** - Monitor application metrics
2. **Logs** - Use `eb logs` to view application logs
3. **Health Checks** - Configure proper health check endpoints

## Cost Optimization

1. **Instance Types** - Choose appropriate instance sizes
2. **Auto Scaling** - Configure based on traffic patterns
3. **RDS** - Use appropriate database instance types
4. **S3** - Use lifecycle policies for cost management

## Support

For issues specific to your application, check:
1. Laravel logs in `/var/app/current/storage/logs/`
2. Nginx logs in `/var/log/nginx/`
3. PHP-FPM logs in `/var/log/php-fpm/`
4. Elastic Beanstalk logs via `eb logs` 