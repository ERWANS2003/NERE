# Complete Vercel Deployment Guide for Laravel ITSM

## 🚀 Quick Deploy Checklist

- [ ] Database setup complete (Neon/Supabase)
- [ ] Environment variables configured
- [ ] GitHub repository connected
- [ ] Vercel project created
- [ ] Initial deployment successful
- [ ] Database migrations completed
- [ ] Application accessible

## Step 1: Pre-Deployment Setup

### 1.1 Generate Application Key
```bash
# Run this locally to generate your Laravel application key
php artisan key:generate --show
```
Copy the generated key (e.g., `base64:xxx...`) - you'll need this for Vercel environment variables.

### 1.2 Test Database Connection (Optional but Recommended)
```bash
# Create a .env.local file with your Neon database credentials
# Then test the connection
php test-db-connection.php
```

### 1.3 Commit All Changes
```bash
git add .
git commit -m "Add Vercel deployment configuration"
git push origin main
```

## Step 2: Create Neon Database

### 2.1 Quick Setup
1. Go to [neon.tech](https://neon.tech) and sign up (free)
2. Create new project: `itsm-nere-mining`
3. Copy the **pooled connection string**
4. Parse credentials from the connection string

**Example connection string:**
```
postgresql://username:password@ep-xxx-123.us-east-1.aws.neon.tech:5432/neondb?sslmode=require
```

**Extracted credentials:**
```
DB_HOST=ep-xxx-123.us-east-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=username
DB_PASSWORD=password
```

## Step 3: Deploy to Vercel

### 3.1 Connect GitHub Repository
1. Go to [vercel.com](https://vercel.com) and sign up/login
2. Click **"New Project"**
3. Import from GitHub: select your `itsm-nere-mining` repository
4. Vercel will auto-detect the `vercel.json` configuration

### 3.2 Configure Environment Variables
Before deploying, add these environment variables in Vercel:

**Go to Project Settings → Environment Variables**

#### Required Variables:
```bash
# Application
APP_NAME=ITSM NERE Mining
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_FROM_STEP_1
APP_DEBUG=false
APP_URL=https://your-project-name.vercel.app

# Database (from Neon setup)
DB_CONNECTION=pgsql
DB_HOST=your-neon-hostname
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=your-neon-username
DB_PASSWORD=your-neon-password
DB_SSLMODE=require

# Serverless Optimizations
CACHE_DRIVER=array
SESSION_DRIVER=cookie
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=error
VIEW_COMPILED_PATH=/tmp
CACHE_PREFIX=vercel_

# File Storage
FILESYSTEM_DISK=local
```

### 3.3 Deploy
1. Click **"Deploy"** in Vercel
2. Wait for build to complete (5-10 minutes)
3. Check build logs for any errors

## Step 4: Post-Deployment Verification

### 4.1 Check Application Status
1. Visit your Vercel app URL (e.g., `https://itsm-nere-mining.vercel.app`)
2. You should see the Laravel welcome page or login screen

### 4.2 Verify Database Connection
- Check Vercel function logs for any database errors
- Migrations should run automatically on first request

### 4.3 Test Core Functionality
1. **Login Page**: Should load without errors
2. **Database**: Check if migrations created tables in Neon dashboard
3. **Assets**: Verify CSS/JS files load correctly
4. **Forms**: Test user registration/login if enabled

## Step 5: Troubleshooting Common Issues

### 5.1 Build Failures

**Issue**: `composer install` fails
**Solution**: Check if all required PHP extensions are available in Vercel

**Issue**: `npm run build` fails  
**Solution**: Ensure `package.json` has correct build scripts

### 5.2 Runtime Errors

**Issue**: 500 Internal Server Error
**Solutions**:
1. Check Vercel function logs: Settings → Functions → View Details
2. Temporarily set `APP_DEBUG=true` to see detailed errors
3. Verify all environment variables are set correctly

**Issue**: Database connection failed
**Solutions**:
1. Verify database credentials in Vercel environment variables
2. Check if Neon database is active and accessible
3. Ensure `DB_SSLMODE=require` is set

**Issue**: Session/Authentication problems
**Solutions**:
1. Ensure `SESSION_DRIVER=cookie` and `SESSION_ENCRYPT=true`
2. Verify `APP_KEY` is set and properly formatted
3. Check that cookies work in your browser

### 5.3 Performance Issues

**Issue**: Slow response times
**Solutions**:
1. Verify using pooled connection string from Neon
2. Check `CACHE_DRIVER=array` is set
3. Monitor Neon database performance metrics

## Step 6: Production Optimization

### 6.1 Custom Domain (Optional)
1. In Vercel project settings → Domains
2. Add your custom domain
3. Update `APP_URL` environment variable

### 6.2 Environment Branches
Create different environments for development:
1. Create `staging` branch in GitHub
2. Connect to new Vercel project for staging
3. Use different database branch in Neon

### 6.3 Monitoring
1. **Vercel Analytics**: Enable in project settings
2. **Error Tracking**: Monitor function logs
3. **Database**: Use Neon monitoring dashboard

## Step 7: Maintenance Commands

### 7.1 Manual Migrations (If Needed)
```bash
# Install Vercel CLI
npm i -g vercel

# Login and link project
vercel login
vercel link

# Run Laravel commands
vercel exec -- php artisan migrate --force
vercel exec -- php artisan config:cache
```

### 7.2 Database Management
- **Backup**: Automatic in Neon (point-in-time recovery)
- **Scaling**: Neon auto-scales based on usage
- **Branching**: Create dev/staging database branches

## 🎉 Success Checklist

- [ ] ✅ Vercel deployment completed without errors
- [ ] ✅ Application loads at Vercel URL
- [ ] ✅ Database connection working (check Neon dashboard)
- [ ] ✅ Laravel migrations completed (32 tables created)
- [ ] ✅ Login page accessible
- [ ] ✅ Static assets (CSS/JS) loading correctly
- [ ] ✅ No errors in Vercel function logs
- [ ] ✅ HTTPS working with valid certificate

## 📞 Support Resources

- **Vercel Docs**: [vercel.com/docs](https://vercel.com/docs)
- **Neon Docs**: [neon.tech/docs](https://neon.tech/docs)
- **Laravel Deployment**: [laravel.com/docs/deployment](https://laravel.com/docs/deployment)

## 🔄 Updates and Redeployment

For future updates:
1. Make changes locally
2. Commit and push to GitHub
3. Vercel auto-deploys on push to main branch
4. Monitor deployment in Vercel dashboard

Your Laravel ITSM application is now running on Vercel with serverless PHP and managed PostgreSQL! 🎊