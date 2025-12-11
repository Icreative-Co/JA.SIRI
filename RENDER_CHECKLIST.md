# Render Deployment Checklist for ja-siri.onrender.com

## Pre-Deployment (GitHub)

- [ ] Push to `Icreative-Co/JA.SIRI` (use commands from `GIT_SETUP.md`)
- [ ] Verify commits appear on GitHub
- [ ] Confirm `.env` is **NOT** in repo (check `.gitignore` includes it)

## Render Setup

### 1. Create Web Service
- [ ] Go to https://dashboard.render.com
- [ ] Click "New" → "Web Service"
- [ ] Connect GitHub repo: `Icreative-Co/JA.SIRI`
- [ ] Choose branch: `main` (or your branch)
- [ ] Set name: `ja-siri`
- [ ] Select plan: Free tier OK for testing

### 2. Configure Service

#### Build & Deploy
- [ ] Runtime: Docker
- [ ] Dockerfile path: `./Dockerfile`
- [ ] Build command: (leave default)
- [ ] Start command: (leave default for Apache)

#### Environment Variables
Add these in Render Dashboard under "Environment":

```
GEMINI_API_KEY=your_key_here
MPESA_CONSUMER_KEY=your_key
MPESA_CONSUMER_SECRET=your_secret
MPESA_PASSKEY=your_passkey
MPESA_SHORTCODE=174379
ENV=sandbox
CALLBACK_URL=https://ja-siri.onrender.com/callback.php
DATABASE_URL=mysql://user:pass@host:port/dbname?ssl-mode=REQUIRED
DB_HOST=ksa-db-icreative-co.h.aivencloud.com
DB_PORT=11146
DB_NAME=defaultdb
DB_USER=avnadmin
DB_PASS=your_pass
DB_SSL_MODE=REQUIRED
```

#### Instance Settings
- [ ] Plan: Free tier (or paid if needed)
- [ ] Auto-deploy: ON (optional, for auto-redeploy on git push)
- [ ] Health Check: Path: `/` (or `/index.php`)

### 3. Deploy
- [ ] Click "Deploy"
- [ ] Wait for build to complete (3-5 minutes)
- [ ] Check logs for errors

## Post-Deployment Testing

### 1. Basic Site
- [ ] Visit https://ja-siri.onrender.com
- [ ] Should see KSA homepage
- [ ] Check nav, hero, footer load correctly
- [ ] Download buttons work

### 2. Shop Page
- [ ] Visit https://ja-siri.onrender.com/shop.php
- [ ] Products load (placeholder images OK)
- [ ] Add to cart works
- [ ] Download price list works

### 3. AI Chat
- [ ] Click floating robot icon
- [ ] AI modal opens
- [ ] Send a test message
- [ ] Chat responds (should use KSA knowledge base)

### 4. M-Pesa STK Push
- [ ] Go to checkout page, enter:
  - Phone: `254701234567` (test number)
  - Amount: `100` (or any amount)
- [ ] Click "Pay with M-Pesa"
- [ ] Should see STK prompt on phone or success message
- [ ] Check `callback.php` log to verify callback reached

### 5. Database
- [ ] Submit a form that writes to DB (e.g., checkout)
- [ ] Verify data appears in Aiven MySQL
- [ ] If no DB access, check error logs in Render dashboard

## Troubleshooting

### Build Fails
- [ ] Check Render logs: Dashboard → Service → Logs
- [ ] Common: Missing env var, Docker syntax error, port conflict
- [ ] Fix locally with `docker build -t ksa:latest .`, then push and retry

### STK Push Returns 404
- [ ] Verify `CALLBACK_URL` is set to `https://ja-siri.onrender.com/callback.php`
- [ ] Confirm M-Pesa credentials (CONSUMER_KEY, CONSUMER_SECRET, PASSKEY) are correct
- [ ] Check if still using `ENV=sandbox` (recommended for testing)
- [ ] M-Pesa may need callback URL registered separately (contact Safaricom support)

### Database Connection Error
- [ ] Test connection string locally: `php -r "require 'db.php'; echo 'OK';"`
- [ ] Confirm Aiven allowlists Render IP (Aiven Dashboard → VPC / Firewall)
- [ ] Verify DB_* env vars in Render dashboard match your Aiven credentials

### AI Chat Returns Error
- [ ] Check `GEMINI_API_KEY` is set and valid in Render env vars
- [ ] Verify API key has Generative Language API enabled on Google Cloud
- [ ] Check error logs in Render dashboard

## Monitoring & Logs

- **Render Logs**: Dashboard → Service → Logs (real-time)
- **PHP Errors**: Check logs for `error_log()` output
- **M-Pesa**: Check `callback.php` logs for callback receipt (stored locally or in DB)
- **AI**: Check `api/chat.php` error responses

## Next Steps (Post-Deployment)

1. Register callback URL with M-Pesa (may require support ticket to Safaricom)
2. Migrate to live M-Pesa credentials when ready (`ENV=live` in Render dashboard)
3. Configure custom domain if desired (Render settings → Domain)
4. Set up database backups (Aiven provides automated backups)
5. Monitor performance and scale if needed (Render plan upgrade)

---

**Support**: Refer to `RENDER_DEPLOY.md`, `DOCKER_README.md`, `GIT_SETUP.md` for detailed docs.
