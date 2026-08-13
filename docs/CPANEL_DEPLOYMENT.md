# Namecheap cPanel Production Deployment

## Target

PHP 8.3, Apache, PostgreSQL, Composer, HTTPS. No VPS dependency.

## Deployment layout

- Git repository: application source
- cPanel document root: `public/`
- Application code/config/database/scripts: outside the public document root when the hosting account permits it
- `.env`: server-only, never committed
- `storage/`: server-writable and outside the public document root

## Deployment

1. Create the PostgreSQL database/user in cPanel.
2. Configure PHP 8.3 and required extensions: PDO_PGSQL, OpenSSL, cURL, Mbstring, JSON.
3. Clone/deploy the repository through cPanel Git Version Control.
4. Run `composer install --no-dev --optimize-autoloader` in the application directory.
5. Copy `.env.example` to `.env` and set production secrets.
6. Run the migration command supplied by the application's migration runner.
7. Point the domain/subdomain document root to `public/`.
8. Enable SSL/HTTPS and force HTTPS at the hosting layer.
9. Configure cron jobs using the cPanel PHP binary, for example `php /home/ACCOUNT/app/scripts/cron/billing.php`.
10. Run the health check and verify logs.

## Cron recommendation

- Billing: every 5 minutes
- Worker: every minute
- Cleanup/maintenance: daily

Use the exact PHP binary path shown by cPanel/hosting; do not assume `/usr/bin/php`.

## Secrets

Never commit `.env`, database passwords, gateway secrets, MikroTik credentials, private keys, or production logs.

## Rollback

Keep the previous release commit identified before deployment. If validation fails, switch the cPanel Git deployment to the previous known-good commit and restore the database only when a migration rollback is explicitly safe.
