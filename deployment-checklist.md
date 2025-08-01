# AWS Elastic Beanstalk Deployment Checklist

## ✅ Pre-Deployment Checklist

### AWS Account Setup
- [ ] AWS Account created and verified
- [ ] Root user MFA enabled
- [ ] IAM user created for programmatic access
- [ ] Billing alerts configured

### AWS Resources
- [ ] S3 bucket created for file storage
- [ ] S3 bucket policy configured
- [ ] RDS MySQL database created
- [ ] RDS security group configured
- [ ] IAM user for GitHub Actions created
- [ ] IAM policies attached (EB, S3, RDS access)

### Application Preparation
- [ ] Laravel application tested locally
- [ ] Database migrations tested
- [ ] Environment variables documented
- [ ] S3 file upload functionality tested
- [ ] Application key generated
- [ ] Production assets built

### GitHub Repository
- [ ] Repository created and code pushed
- [ ] GitHub Secrets configured:
  - [ ] `AWS_ACCESS_KEY_ID`
  - [ ] `AWS_SECRET_ACCESS_KEY`
  - [ ] `AWS_REGION`
  - [ ] `EB_APPLICATION_NAME`
  - [ ] `EB_ENVIRONMENT_NAME`
- [ ] GitHub Actions workflow added

## 🚀 Deployment Steps

### Step 1: Create Elastic Beanstalk Application
- [ ] Go to EB Console
- [ ] Create application: `ceysaid-app`
- [ ] Platform: PHP 8.2 on Amazon Linux 2023
- [ ] Platform version: 4.0.0

### Step 2: Create Environment
- [ ] Environment name: `ceysaid-production`
- [ ] Domain: Auto-generated or custom
- [ ] Instance type: t3.small (or t3.micro for testing)
- [ ] Database instance: db.m5.large (or db.m6g.large for better performance)
- [ ] Single instance deployment
- [ ] EC2 key pair created
- [ ] IAM instance profile: aws-elasticbeanstalk-ec2-role

### Step 3: Configure Environment Variables
- [ ] APP_NAME: "Ceysaid CRM"
- [ ] APP_ENV: production
- [ ] APP_DEBUG: false
- [ ] APP_KEY: [generated key]
- [ ] APP_URL: [your-eb-domain]
- [ ] LOG_CHANNEL: errorlog
- [ ] LOG_LEVEL: error
- [ ] DB_CONNECTION: mysql
- [ ] DB_HOST: [rds-endpoint]
- [ ] DB_PORT: 3306
- [ ] DB_DATABASE: ceysaid_production
- [ ] DB_USERNAME: admin
- [ ] DB_PASSWORD: [your-password]
- [ ] CACHE_DRIVER: file
- [ ] FILESYSTEM_DISK: s3
- [ ] SESSION_DRIVER: file
- [ ] SESSION_LIFETIME: 120
- [ ] AWS_ACCESS_KEY_ID: [your-access-key]
- [ ] AWS_SECRET_ACCESS_KEY: [your-secret-key]
- [ ] AWS_DEFAULT_REGION: [your-region]
- [ ] AWS_BUCKET: [your-s3-bucket]
- [ ] AWS_URL: [your-s3-url]
- [ ] MAIL_MAILER: log

### Step 4: Initial Deployment
- [ ] Create deployment ZIP (excluding node_modules, vendor, .git)
- [ ] Upload to EB Console
- [ ] Monitor deployment progress
- [ ] Check deployment logs

### Step 5: Post-Deployment Configuration
- [ ] SSH into instance
- [ ] Run database migrations: `php artisan migrate --force`
- [ ] Create storage link: `php artisan storage:link`
- [ ] Set proper permissions
- [ ] Clear and cache configuration: `php artisan optimize`

### Step 6: GitHub Actions Deployment
- [ ] Push code to main branch
- [ ] Monitor GitHub Actions workflow
- [ ] Verify deployment success
- [ ] Check application health

## 🔧 Configuration Files Verification

### .ebextensions/
- [ ] `01_packages.config` - Required packages
- [ ] `02_php_settings.config` - PHP configuration
- [ ] `03_environment.config` - Environment template
- [ ] `04_cron_jobs.config` - Laravel scheduler

### .platform/
- [ ] `nginx/conf.d/laravel.conf` - Nginx configuration
- [ ] `hooks/prebuild/01_install_node.sh` - Node.js installation
- [ ] `hooks/prebuild/02_install_composer_dependencies.sh` - Composer deps
- [ ] `hooks/prebuild/03_build_assets.sh` - Asset compilation
- [ ] `hooks/postdeploy/01_laravel_setup.sh` - Laravel setup

## 🧪 Testing Checklist

### Application Testing
- [ ] Homepage loads correctly
- [ ] Database connection working
- [ ] File uploads to S3 working
- [ ] User authentication working
- [ ] Admin panel (Filament) accessible
- [ ] CRUD operations working
- [ ] Email functionality (if configured)

### Performance Testing
- [ ] Page load times acceptable
- [ ] Database queries optimized
- [ ] Static assets loading correctly
- [ ] File upload performance good

### Security Testing
- [ ] HTTPS redirect working
- [ ] Environment variables not exposed
- [ ] File permissions correct
- [ ] Database credentials secure

## 📊 Monitoring Setup

### CloudWatch Alarms
- [ ] CPU utilization > 80%
- [ ] Memory utilization > 80%
- [ ] Disk space > 85%
- [ ] Application errors > 0

### Log Monitoring
- [ ] EB logs streaming enabled
- [ ] Laravel logs accessible
- [ ] Nginx logs monitored
- [ ] PHP-FPM logs checked

### Health Checks
- [ ] Application health endpoint
- [ ] Database connectivity check
- [ ] S3 connectivity check
- [ ] Response time monitoring

## 🔒 Security Verification

### Network Security
- [ ] VPC configured correctly
- [ ] Security groups restrictive
- [ ] RDS not publicly accessible
- [ ] S3 bucket private

### Access Control
- [ ] IAM roles used instead of keys
- [ ] Least privilege access
- [ ] MFA enabled for users
- [ ] Access logs enabled

### Data Protection
- [ ] Encryption at rest enabled
- [ ] Encryption in transit enabled
- [ ] Database backups configured
- [ ] S3 versioning enabled

## 📈 Scaling Configuration

### Auto Scaling
- [ ] Minimum instances: 1
- [ ] Maximum instances: 4
- [ ] Scale up: CPU > 70%
- [ ] Scale down: CPU < 30%
- [ ] Cooldown periods set

### Database Scaling
- [ ] Read replicas (if needed)
- [ ] Connection pooling
- [ ] Query optimization
- [ ] Storage autoscaling

### Caching
- [ ] OPcache enabled
- [ ] Static asset caching
- [ ] Database query caching
- [ ] Session storage optimized

## 🚨 Troubleshooting Guide

### Common Issues
- [ ] Deployment failures - Check EB logs
- [ ] Database connection - Verify security groups
- [ ] File uploads - Check S3 permissions
- [ ] Performance issues - Monitor CloudWatch
- [ ] Permission errors - SSH and fix permissions

### Useful Commands
```bash
# SSH into instance
eb ssh

# View logs
eb logs

# Check status
eb status

# View application logs
tail -f /var/app/current/storage/logs/laravel.log

# Fix permissions
sudo chown -R webapp:webapp /var/app/current
sudo chmod -R 755 /var/app/current
sudo chmod -R 775 /var/app/current/storage
sudo chmod -R 775 /var/app/current/bootstrap/cache

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Optimize for production
php artisan optimize
```

## 📋 Post-Deployment Tasks

### Documentation
- [ ] Deployment guide updated
- [ ] Environment variables documented
- [ ] Troubleshooting guide created
- [ ] Team access configured

### Monitoring
- [ ] CloudWatch dashboards created
- [ ] Alerting configured
- [ ] Log retention set
- [ ] Performance baselines established

### Backup Strategy
- [ ] Database backup schedule
- [ ] Application backup strategy
- [ ] Recovery procedures tested
- [ ] Disaster recovery plan

### Maintenance
- [ ] Update schedule planned
- [ ] Security patch process
- [ ] Performance monitoring
- [ ] Cost optimization review

## ✅ Final Verification

### Application Health
- [ ] All pages loading correctly
- [ ] Database operations working
- [ ] File uploads functioning
- [ ] User authentication working
- [ ] Admin panel accessible
- [ ] No errors in logs

### Performance
- [ ] Page load times < 3 seconds
- [ ] Database response times acceptable
- [ ] File upload performance good
- [ ] Memory usage stable

### Security
- [ ] No sensitive data exposed
- [ ] HTTPS working correctly
- [ ] Permissions properly set
- [ ] Access logs enabled

### Monitoring
- [ ] Alarms configured
- [ ] Logs streaming
- [ ] Health checks passing
- [ ] Metrics visible in CloudWatch

## 🎯 Success Criteria

- [ ] Application deployed successfully
- [ ] All functionality working
- [ ] Performance acceptable
- [ ] Security measures in place
- [ ] Monitoring configured
- [ ] Team can access and manage
- [ ] Documentation complete
- [ ] Backup strategy implemented

---

**Deployment Status:** ⏳ Pending  
**Last Updated:** [Date]  
**Next Review:** [Date + 1 month] 