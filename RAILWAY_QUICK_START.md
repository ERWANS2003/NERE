# 🚀 Railway Deployment - Quick Start (5 Minutes)

## TL;DR

```bash
# 1. Commit and push
git add .
git commit -m "Add Railway deployment files"
git push origin main

# 2. Go to railway.app → Create project → Select your GitHub repo → Deploy

# 3. Add PostgreSQL database from Railway dashboard

# 4. Set environment variables (see RAILWAY_DEPLOYMENT.md for full list)

# 5. Done! Your app will be live in ~10 minutes
```

---

## What Changed in Your Project

✅ **Dockerfile** - Docker configuration for Railway (Apache + PHP 8.3 + PostgreSQL)
✅ **.dockerignore** - Optimize Docker build size
✅ **railway.json** - Railway-specific configuration
✅ **RAILWAY_DEPLOYMENT.md** - Detailed deployment guide

---

## Environment Variables You MUST Set

After creating your Railway project:

| Variable | Value |
|----------|-------|
| `APP_NAME` | ITSM Nere Mining |
| `APP_ENV` | production |
| `APP_DEBUG` | false |
| `APP_KEY` | (auto-generated or use `php artisan key:generate --show`) |
| `APP_URL` | Your Railway app URL |

**Database:** Railway automatically provides `DATABASE_URL` when you link PostgreSQL

---

## Verification Checklist

- [ ] Code pushed to GitHub
- [ ] Railway project created
- [ ] PostgreSQL database added
- [ ] Environment variables set
- [ ] Deployment completed (check logs)
- [ ] App loads at Railway URL
- [ ] Login page appears

---

## Need Help?

See **RAILWAY_DEPLOYMENT.md** for:
- Step-by-step walkthrough
- Troubleshooting guide
- Railway CLI commands
- Free tier limits

