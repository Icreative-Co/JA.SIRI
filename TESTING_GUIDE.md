# MPesa & Gemini API Integration - Testing & Troubleshooting Guide

## Status Report

### ✅ Working
- **Gemini API (gemini-2.5-flash)**: Successfully tested
  - Model: `gemini-2.5-flash` (confirmed available per your rate limits)
  - Endpoint: `/v1beta/models/gemini-2.5-flash:generateContent`
  - Status: HTTP 200, responding with valid completions
  - Files updated: `api/chat.php`, `test_gemini.php`

- **PHP Syntax**: All files lint-clean (no syntax errors)

- **MPesa Token Generation**: Working
  - Successfully retrieves OAuth token from Safaricom sandbox
  - Credentials in `.env` are being read correctly

### ⚠️  Issue: MPesa STK Push Returns 404

**Problem**: When calling `/mpesa/stkpush/v1/processRequest`, Safaricom returns:
```json
{
  "errorCode": "404.001.01",
  "errorMessage": "Resource not found"
}
```

**Root Causes (check these in order)**:
1. **CALLBACK_URL is localhost** → M-Pesa cannot reach it for callbacks
   - Current: `http://localhost/ICREATIVE.CO/ksa_lipa_mdogo/callback.php`
   - Fix: Use a publicly accessible domain, e.g., `https://yourdomain.com/callback.php`

2. **Shortcode/Credentials mismatch**
   - Verify `MPESA_SHORTCODE` (963350) matches what you registered with Safaricom
   - Verify `MPESA_PASSKEY` is correct from Safaricom
   - Verify `MPESA_CONSUMER_KEY` and `MPESA_CONSUMER_SECRET` have STK Push permissions

3. **Sandbox vs. Live mismatch**
   - Ensure your test credentials are sandbox-enabled
   - `.env` currently set to `ENV=sandbox` ✓

## Test Files Created

1. **test_gemini.php** — Tests Gemini API with your key
   - Run: `php test_gemini.php`
   - Shows: HTTP status and response preview

2. **test_mpesa.php** — Tests M-Pesa token retrieval only
   - Run: `php test_mpesa.php`
   - Shows: Token obtained (masked)

3. **test_stk_push.php** — Tests full STK Push flow
   - Run: `php test_stk_push.php`
   - Currently returns 404 due to Safaricom config issues

4. **debug_stk_push.php** — Analyzes configuration and identifies issues
   - Run: `php debug_stk_push.php`
   - Shows: Payload structure, config status, and warnings

5. **test_mpesa_direct.sh** — Direct bash/curl test (for manual testing)
   - Run: `bash test_mpesa_direct.sh`

## Code Improvements Made

### `mpesa.php`
- Fixed endpoint: `/mpesa/stkpush/v1/processrequest` → `/mpesa/stkpush/v1/processRequest` (capital R)
- Added SSL verification: `CURLOPT_SSL_VERIFYPEER => true`
- Added HTTP error logging: Errors logged to PHP error log
- Added better response handling: Returns structured error on curl failures
- Added response validation: Checks if JSON decode succeeds

### `api/chat.php`
- Updated model: `gemini-1.5-flash-latest` → `gemini-2.5-flash`
- Added curl error handling: Catches curl failures and returns JSON error
- Added HTTP status checking: Returns appropriate error codes (200, 4xx, 5xx)
- Improved error response: Includes HTTP code and API response for debugging

### `checkout.php`
- Removed junk characters from end of file
- Form remains unchanged and functional

### `check.php`
- Removed junk characters from end of file

## Quick Test Commands

```bash
# Test all syntax
find . -name '*.php' -print0 | xargs -0 -n1 php -l

# Test Gemini API
php test_gemini.php

# Test M-Pesa token (no actual payment)
php test_mpesa.php

# Test full STK Push (will attempt real request)
php test_stk_push.php

# Debug M-Pesa configuration
php debug_stk_push.php
```

## Next Steps to Fix MPesa STK Push

### Option 1: Update .env with public callback URL
```
CALLBACK_URL=https://yourdomain.com/ICREATIVE.CO/ksa_lipa_mdogo/callback.php
```

### Option 2: Verify Safaricom credentials
1. Log in to [Safaricom API Portal](https://developer.safaricom.co.ke)
2. Check that your app has:
   - ✓ Sandbox/Live credentials enabled
   - ✓ STK Push permission granted
   - ✓ Correct shortcode linked
3. Copy exact credentials into `.env`

### Option 3: Test with Safaricom's provided test tools
Visit https://developer.safaricom.co.ke/test-mpesa-api and use their sandbox simulator

## File Structure
```
/var/www/html/ICREATIVE.CO/ksa_lipa_mdogo/
├── .env                    (Config - DO NOT COMMIT)
├── mpesa.php              (M-Pesa integration - UPDATED)
├── api/chat.php           (Gemini API - UPDATED)
├── checkout.php           (Checkout form - FIXED)
├── check.php              (Order tracking - FIXED)
├── test_gemini.php        (NEW - Gemini test)
├── test_mpesa.php         (NEW - Token test)
├── test_stk_push.php      (NEW - Full flow test)
├── debug_stk_push.php     (NEW - Config analyzer)
└── test_mpesa_direct.sh   (NEW - Direct curl test)
```

## Security Reminders

✓ Secure `.env` file:
```bash
chmod 600 .env
```

✓ Add to `.gitignore`:
```
.env
*.log
logs/
```

✓ Never commit credentials to git

## Support

If STK Push still doesn't work after updating the callback URL and verifying credentials:
1. Check PHP error logs: `tail -f /var/log/php-error.log`
2. Check callback logs: Look at `callback.php` to see if M-Pesa is reaching it
3. Visit Safaricom API portal to see transaction logs and exact error codes
4. Ensure your domain/IP is not blocked by firewall

---

**Last Updated**: 2025-12-11
**Gemini Model**: gemini-2.5-flash (✓ working)
**MPesa Status**: Token working, STK Push returns 404 (likely config issue)

