Render deployment notes for `ksa_lipa_mdogo`

Overview
--------
This document explains how to deploy the site to Render. It assumes you'll set secrets (API keys, DB credentials) in Render's Dashboard or via the Render CLI.

1) Dockerfile (recommended)
- Create a `Dockerfile` at repo root that builds a PHP image, installs extensions (pdo_mysql, curl), and starts a server (php-fpm + nginx or built-in server for small sites).

Example minimal Dockerfile (not for heavy production):

```
FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]
```

2) Env vars
- Add these to Render service settings (do not commit into repo):
  - `GEMINI_API_KEY`
  - `MPESA_CONSUMER_KEY`
  - `MPESA_CONSUMER_SECRET`
  - `MPESA_PASSKEY`
  - `MPESA_SHORTCODE`
  - `DATABASE_URL` or `DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS`
  - `CALLBACK_URL` (public reachable URL)

3) Database
- The site uses PDO (`db.php`). For Render you can use your Aiven MySQL endpoint. Use `DATABASE_URL` or individual DB_* vars.
- Ensure Render service can reach the DB host (Aiven allows IPs or network peering). If not, confirm firewall/allowlist.

4) SSL / CALLBACK_URL
- M-Pesa and other services require a publicly reachable HTTPS `CALLBACK_URL`. If testing, use ngrok and set the public HTTPS ngrok URL as `CALLBACK_URL`.

5) Static assets
- `assets/removed_files` contains archived docs. Keep them ignored by git.

6) Health checks
- Configure Render health check at `/` or `/health`.

7) Post-deploy checklist
- Verify `.env` is not deployed to git; use Render env vars.
- Confirm `mpesa.php` uses `ENV` = `sandbox` for testing, or `live` when ready (and correct credentials).

Commands
--------
- Deploy via Render dashboard (connect GitHub/GitLab) or use `render.yaml` to define the service.

Security notes
--------------
- Do not store secrets in the repo. Use Render's environment configuration.
- Use `DATABASE_URL` with SSL mode `REQUIRED` for Aiven.
- Rotate API keys before production if they were ever shared.

