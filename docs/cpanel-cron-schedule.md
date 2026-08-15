# Namecheap cPanel Cron Schedule

The production deployment target is Namecheap Shared Hosting with cPanel Git, PHP 8.3+, PostgreSQL, and cPanel Cron.

Assuming the Git Version Control repository path is:

```text
/home/CPANEL_USER/repositories/ispluka-php
```

Replace `CPANEL_USER` with the actual cPanel account username.

## Database migration

After `.env` and PostgreSQL are configured, run the migration command from the repository root:

```bash
cd /home/CPANEL_USER/repositories/ispluka-php
php scripts/setup/migrate.php
```

The migration runner tracks applied PHP and SQL migrations in `schema_migrations` and runs each migration transactionally. Do not run migrations from a web request.

## Overdue enforcement

Run the overdue eligibility check every day at **12:05 PM** in the hosting account's configured timezone.

```cron
5 12 * * * /usr/local/bin/php /home/CPANEL_USER/repositories/ispluka-php/scripts/cron/overdue-enforcement.php >/dev/null 2>&1
```

The script only evaluates overdue services and queues network jobs. It does not open a MikroTik connection for every customer from the web request. The existing network worker should process pending network jobs separately at a short interval (for example every minute):

```cron
* * * * * /usr/local/bin/php /home/CPANEL_USER/repositories/ispluka-php/scripts/cron/network-worker.php 20 >/dev/null 2>&1
```

If the Namecheap account uses a different PHP CLI path, select the PHP 8.3 binary shown by cPanel's MultiPHP/Terminal configuration.

### Enforcement rule

- overdue + grace period expired + auto-suspend enabled → queue `suspend`
- if the configured MikroTik suspension profile is available, the enforcement layer should use it
- if no suspension profile is available, use the existing Temporary Disable fallback
- network execution must be verified and recorded as Success / Failed / Mismatch
- successful payment removes the overdue condition and the existing auto-restore path can queue `restore`
