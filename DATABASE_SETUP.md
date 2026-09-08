# PostgreSQL Database Setup for Vercel Deployment

## Option 1: Neon PostgreSQL (Recommended - Free Tier)

### Step 1: Create Neon Account
1. Go to [neon.tech](https://neon.tech)
2. Click "Sign up" and create a free account
3. Verify your email address

### Step 2: Create Database Project
1. After logging in, click **"New Project"**
2. Choose:
   - **Project name**: `itsm-nere-mining`
   - **Database name**: `neondb` (default)
   - **Region**: Choose closest to your users
   - **PostgreSQL version**: 17 (latest)
3. Click **"Create Project"**

### Step 3: Get Connection Details
1. On the project dashboard, click **"Connect"**
2. Select **"Pooled connection"** (recommended for serverless)
3. Copy the connection string (looks like):
   ```
   postgresql://username:password@hostname/neondb?sslmode=require
   ```

### Step 4: Parse Connection String for Vercel
From the connection string `postgresql://username:password@hostname:5432/neondb?sslmode=require`, extract:

```
DB_HOST=hostname (e.g., ep-xxx.us-east-1.aws.neon.tech)
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=username
DB_PASSWORD=password
DB_SSLMODE=require
```

### Step 5: Add to Vercel Environment Variables
Go to your Vercel project → Settings → Environment Variables and add:

```
DB_CONNECTION=pgsql
DB_HOST=your-neon-hostname
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=your-neon-username
DB_PASSWORD=your-neon-password
DB_SSLMODE=require
```

## Option 2: Supabase PostgreSQL (Alternative)

### Step 1: Create Supabase Project
1. Go to [supabase.com](https://supabase.com)
2. Sign up and create new project
3. Choose project name and password

### Step 2: Get Connection Details
1. Go to **Settings** → **Database**
2. Scroll down to **Connection Parameters**
3. Copy the connection details

### Step 3: Add to Vercel
Use the same environment variable format as Neon above.

## Quick Setup (No Signup Required)

For instant testing, you can use Neon's claimable database:

```bash
curl -s https://pg.neon.dev/
```

This gives you:
- Instant PostgreSQL database
- 72-hour expiration (claimable for longer)
- Perfect for testing Vercel deployment

## Database Migration

After setting up the database and deploying to Vercel:

### Method 1: Automatic (Recommended)
Your Laravel app will run migrations automatically on first deployment via the `api/index.php` entrypoint.

### Method 2: Manual (If needed)
If migrations don't run automatically:

```bash
# Install Vercel CLI
npm i -g vercel

# Login to Vercel
vercel login

# Link your project
vercel link

# Run migrations
vercel exec -- php artisan migrate --force
```

## Database Schema Verification

Your ITSM application will create these tables:
- `users` - User accounts and authentication
- `tickets` - Support tickets and incidents
- `departements` - Company departments
- `assets` - IT asset management
- `roles` & `permissions` - User access control
- `audit_logs` - System activity tracking
- And 25+ other ITSM-related tables

## Neon Features for ITSM

- ✅ **Autoscaling**: Scales to zero when not used
- ✅ **Branching**: Create dev/staging database branches
- ✅ **Connection Pooling**: Perfect for serverless
- ✅ **SSL/TLS**: Secure connections by default
- ✅ **Backups**: Point-in-time recovery included
- ✅ **Free Tier**: 0.5GB storage, 1GB data transfer/month

## Troubleshooting

### Connection Issues:
1. Verify all environment variables are set correctly
2. Check that `DB_SSLMODE=require` is set
3. Ensure using pooled connection string from Neon

### Migration Issues:
1. Check Vercel function logs for detailed errors
2. Verify database user has proper permissions
3. Try running migrations manually via Vercel CLI

### Performance:
- Neon automatically optimizes for Laravel
- Connection pooling handles serverless scaling
- No additional configuration needed