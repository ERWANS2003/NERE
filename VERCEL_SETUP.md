# Vercel Environment Variables Setup

## Required Environment Variables

### Step 1: Generate Laravel Application Key
Run this command locally to generate a new application key:
```bash
php artisan key:generate --show
```

### Step 2: Set Environment Variables in Vercel

Go to your Vercel project dashboard → Settings → Environment Variables and add:

#### Application Settings
```
APP_NAME=ITSM NERE Mining
APP_ENV=production  
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-project-name.vercel.app
```

#### Database Settings (Neon PostgreSQL - see next step)
```
DB_CONNECTION=pgsql
DB_HOST=your-neon-hostname
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=your-neon-username
DB_PASSWORD=your-neon-password
DB_SSLMODE=require
```

#### Serverless Optimizations
```
CACHE_DRIVER=array
SESSION_DRIVER=cookie
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=error
VIEW_COMPILED_PATH=/tmp
CACHE_PREFIX=vercel_
```

#### Optional: Email Configuration
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=your-resend-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME=ITSM NERE Mining
```

## Database Setup Options

### Option 1: Neon (Recommended)
1. Go to [neon.tech](https://neon.tech) and create a free account
2. Create a new project
3. Copy the connection details to Vercel environment variables
4. Neon provides: hostname, database name, username, password

### Option 2: Supabase
1. Go to [supabase.com](https://supabase.com) and create a free account  
2. Create a new project
3. Go to Settings → Database
4. Copy connection details to Vercel environment variables

### Option 3: Aiven PostgreSQL
1. Go to [aiven.io](https://aiven.io) and create a free account
2. Create a PostgreSQL service
3. Copy connection details to Vercel environment variables

## Deployment Steps

1. **Connect Repository**: Connect your GitHub repository to Vercel
2. **Configure Build**: Vercel will auto-detect the `vercel.json` configuration
3. **Set Environment Variables**: Add all variables listed above
4. **Deploy**: Trigger deployment
5. **Run Migrations**: After first successful deployment, you may need to run migrations manually

## Post-Deployment Commands

If you need to run Laravel commands after deployment, you can use Vercel CLI:

```bash
# Install Vercel CLI
npm i -g vercel

# Login to Vercel
vercel login

# Run artisan commands (if needed)
vercel exec -- php artisan migrate --force
```

## Troubleshooting

### Common Issues:
1. **500 Error**: Check Vercel function logs for PHP errors
2. **Database Connection**: Verify all database environment variables
3. **Session Issues**: Ensure SESSION_DRIVER=cookie and SESSION_ENCRYPT=true
4. **Asset Loading**: Check that CSS/JS files are being served from /public

### Debug Mode:
Temporarily set `APP_DEBUG=true` in Vercel environment variables to see detailed error messages, then set back to `false` for production.