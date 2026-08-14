# Namecheap cPanel Cron Schedule

## Overdue enforcement

Run the overdue eligibility check every day at **12:05 PM** in the hosting account's configured timezone.

```cron
5 12 * * * /usr/local/bin/php /home/CPANEL_USER/ispluka/scripts/cron/overdue-enforcement.php >/dev/null 2>&1
```

Replace `CPANEL_USER` and the project path with the actual Namecheap cPanel values.

The script only evaluates overdue services and queues network jobs. It does not open a MikroTik connection for every customer from the web request. The existing network worker should process pending network jobs separately at a short interval (for example every minute):

```cron
* * * * * /usr/local/bin/php /home/CPANEL_USER/ispluka/scripts/cron/network-worker.php >/dev/null 2>&1
```

If the Namecheap account uses a different PHP CLI path, select the PHP 8.3 binary shown by cPanel's MultiPHP/Terminal configuration.

### Enforcement rule

- overdue + grace period expired + auto-suspend enabled → queue `suspend`
- if the configured MikroTik suspension profile is available, the enforcement layer should use it
- if no suspension profile is available, use the existing Temporary Disable fallback
- network execution must be verified and recorded as Success / Failed / Mismatch
- successful payment removes the overdue condition and the existing auto-restore path can queue `restore`
