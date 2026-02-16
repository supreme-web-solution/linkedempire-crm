# Mail setup (Forge / DigitalOcean)

## Why SMTP times out

DigitalOcean (and Laravel Forge on DO) often **blocks outbound SMTP ports 25, 465, and 587**. So your cPanel server (`mail.xteract.com:465`) can be unreachable from the app server → connection timeout. This is not a Laravel or domain/DNS issue.

## Option 1: Try cPanel on port 587 (already set)

Your `.env` is set to use **port 587 with TLS** instead of 465 with SSL. Some networks allow 587 when 465 is blocked.

- If it still times out, use Option 2.

## Option 2: Use Resend (recommended)

Resend sends over HTTP (no SMTP ports), so it works from Forge/DO. You keep **the same from-address** (`linkedempire@xteract.com`).

### Steps

1. **Sign up** at [resend.com](https://resend.com) (free tier available).
2. **Add and verify your domain**  
   In Resend: Domains → Add Domain → add `xteract.com` (or the domain you send from). Add the DNS records they show (SPF, DKIM, etc.) in cPanel or your DNS.
3. **Create an API key**  
   Resend → API Keys → Create. Copy the key (starts with `re_`).
4. **Update `.env` on the server**

   Switch to Resend and set your key:

   ```env
   MAIL_MAILER=resend
   RESEND_KEY=re_your_actual_key_here
   MAIL_FROM_ADDRESS=linkedempire@xteract.com
   MAIL_FROM_NAME="${APP_NAME}"
   ```

   Comment out or remove the SMTP block (MAIL_HOST, MAIL_PORT, etc.) for mail, or leave them; only `MAIL_MAILER=resend` and `RESEND_KEY` are used for sending.

5. **Clear config cache**

   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

6. **Test**  
   Hit your test route (e.g. `/test-user-creation-email`) or send a welcome email from the app.

Emails will still be **from** `linkedempire@xteract.com`; only the delivery path (Resend’s API instead of cPanel SMTP) changes.

## If you must use cPanel SMTP

Ask your cPanel host if they offer **port 2525** (alternative submission). If yes:

```env
MAIL_PORT=2525
MAIL_ENCRYPTION=tls
```

If 2525 is not available and 465/587 are blocked on your app server, you need a transactional provider (Resend, SendGrid, Mailgun, etc.) or a server that does not block those ports.
