# Railway Deployment Guide - Laravel ITSM

## 🚂 Deployment Steps

### 1. Database Setup
Railway provides PostgreSQL as a service. You'll add it after deployment.

### 2. Deploy to Railway

1. **Go to [railway.app](https://railway.app)**
2. **Sign up/Login** with GitHub
3. **Create New Project** → **Deploy from GitHub repo**
4. **Select your repository**: `ERWANS2003/NERE`
5. **Railway will auto-detect** the Dockerfile and start building

### 3. Add PostgreSQL Database

1. **In your Railway project dashboard**
2. **Click "+ New Service"**
3. **Select "PostgreSQL"**
4. **Railway will provision a database**

### 4. Configure Environment Variables

After deployment, add these environment variables:

#### Go to your app service → Variables tab:

```bash
# Application
APP_NAME=ITSM NERE Mining
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_GENERATED_KEY

# Database (Railway will provide these)
DB_CONNECTION=pgsql
DB_HOST=${{Postgres.PGHOST}}
DB_PORT=${{Postgres.PGPORT}}
DB_DATABASE=${{Postgres.PGDATABASE}}
DB_USERNAME=${{Postgres.PGUSER}}
DB_PASSWORD=${{Postgres.PGPASSWORD}}

# Laravel optimizations
CACHE_DRIVER=file
SESSION_DRIVER=file
LOG_CHANNEL=stderr
QUEUE_CONNECTION=sync
```

### 5. Generate Application Key

Run this locally and add to Railway variables:
```bash
php artisan key:generate --show
```

### 6. Railway Service Variables

Railway automatically provides database connection variables. Use them like this in your Railway environment variables:

- `DB_HOST`: `${{Postgres.PGHOST}}`
- `DB_PORT`: `${{Postgres.PGPORT}}`  
- `DB_DATABASE`: `${{Postgres.PGDATABASE}}`
- `DB_USERNAME`: `${{Postgres.PGUSER}}`
- `DB_PASSWORD`: `${{Postgres.PGPASSWORD}}`

### 7. Custom Domain (Optional)

1. **Go to Settings → Domains**
2. **Add your custom domain**
3. **Update APP_URL** environment variable

## 🎯 Current Status

✅ **Dockerfile optimized** - Fixed Apache MPM issues
✅ **Database migrations** - Auto-run on deployment  
✅ **Error handling** - Robust startup script
✅ **Laravel optimization** - Caching configured

## 🔧 Troubleshooting

### If deployment fails:
1. Check Railway build logs
2. Ensure all environment variables are set
3. Verify database is running

### If app shows 500 error:
1. Set `APP_DEBUG=true` temporarily
2. Check Railway service logs
3. Verify database connection

## 🚀 Deploy Now!

The Dockerfile is now optimized to handle the Apache configuration properly. Railway deployment should work smoothly!