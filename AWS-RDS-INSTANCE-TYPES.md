# AWS RDS Instance Types Reference

## Current Available Instance Types (2024)

### General Purpose (M-series)
- **db.m5.large** - 2 vCPU, 8 GB RAM (Free tier eligible)
- **db.m5.xlarge** - 4 vCPU, 16 GB RAM
- **db.m5.2xlarge** - 8 vCPU, 32 GB RAM
- **db.m6g.large** - 2 vCPU, 8 GB RAM (Graviton2, better performance)
- **db.m6g.xlarge** - 4 vCPU, 16 GB RAM
- **db.m6g.2xlarge** - 8 vCPU, 32 GB RAM

### Memory Optimized (R-series)
- **db.r5.large** - 2 vCPU, 16 GB RAM
- **db.r5.xlarge** - 4 vCPU, 32 GB RAM
- **db.r5.2xlarge** - 8 vCPU, 64 GB RAM
- **db.r6g.large** - 2 vCPU, 16 GB RAM (Graviton2)
- **db.r6g.xlarge** - 4 vCPU, 32 GB RAM

### Burstable Performance (T-series)
- **db.t3.micro** - 2 vCPU, 1 GB RAM (Free tier eligible)
- **db.t3.small** - 2 vCPU, 2 GB RAM
- **db.t3.medium** - 2 vCPU, 4 GB RAM
- **db.t3.large** - 2 vCPU, 8 GB RAM

## Recommendations for Laravel Applications

### Development/Testing
- **db.t3.micro** - Free tier, good for development
- **db.t3.small** - Low cost, suitable for small applications

### Production (Small to Medium)
- **db.m5.large** - Good balance of CPU and memory
- **db.m6g.large** - Better performance with Graviton2 processor

### Production (Medium to Large)
- **db.m5.xlarge** - More resources for higher traffic
- **db.r5.large** - More memory for complex queries

### High Performance
- **db.m6g.xlarge** - High performance with Graviton2
- **db.r5.xlarge** - High memory for large datasets

## Cost Comparison (US East-1, On-Demand)

| Instance Type | vCPU | RAM | Hourly Cost | Monthly Cost |
|---------------|------|-----|-------------|--------------|
| db.t3.micro   | 2    | 1GB | $0.017      | ~$12.50     |
| db.t3.small   | 2    | 2GB | $0.034      | ~$25.00     |
| db.m5.large   | 2    | 8GB | $0.171      | ~$125.00    |
| db.m6g.large  | 2    | 8GB | $0.154      | ~$112.50    |
| db.m5.xlarge  | 4    | 16GB| $0.342      | ~$250.00    |
| db.r5.large   | 2    | 16GB| $0.285      | ~$208.00    |

## Free Tier Eligibility

### AWS Free Tier (12 months)
- **db.t3.micro** - 750 hours/month
- **db.m5.large** - 750 hours/month

### Always Free
- **db.t3.micro** - Limited usage

## Selection Guide

### For Your Laravel Application

1. **Development/Testing:**
   ```
   Instance: db.t3.micro
   Reason: Free tier eligible, sufficient for development
   ```

2. **Production (Low Traffic):**
   ```
   Instance: db.m5.large
   Reason: Good performance, free tier eligible
   ```

3. **Production (Medium Traffic):**
   ```
   Instance: db.m6g.large
   Reason: Better performance with Graviton2
   ```

4. **Production (High Traffic):**
   ```
   Instance: db.m5.xlarge or db.r5.large
   Reason: More resources for scaling
   ```

## Important Notes

- **Graviton2 processors** (m6g, r6g) offer better price/performance
- **Free tier** is limited to 20GB storage
- **Storage** is charged separately from compute
- **Multi-AZ** deployment doubles the cost
- **Backup storage** is charged separately

## Migration Path

1. **Start with:** db.t3.micro (free tier)
2. **Scale to:** db.m5.large (when traffic increases)
3. **Optimize with:** db.m6g.large (for better performance)
4. **Scale up to:** db.m5.xlarge or db.r5.large (for high traffic) 