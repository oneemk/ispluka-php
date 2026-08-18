# PPPoE Activity Sync Worker

The PPPoE activity collector is executed by `scripts/cron/pppoe-activity.php`.

## What it does

1. Loads every configured tenant router except `inactive` and `maintenance` routers.
2. Connects using the router's configured API or SSH transport.
3. Reads `/ppp/active/print` as the authoritative live snapshot.
4. Marks the router's previously-known PPPoE sessions offline only after the live snapshot is successfully collected.
5. Upserts the sessions returned by MikroTik as online.
6. Marks the router online after a successful snapshot and offline on connection/collection failure.
7. Uses a global worker lock plus a per-router lock so overlapping cron runs cannot reconcile the same router concurrently.

A MikroTik connection failure therefore does **not** erase the previous online session state. Existing sessions are reconciled only after a successful live snapshot.

## cPanel Cron

Run the worker every minute:

```text
* * * * * cd /home/isplzepc/repositories/ispluka-php && php scripts/cron/pppoe-activity.php >> /home/isplzepc/logs/pppoe-activity.log 2>&1
```

If cPanel requires an absolute PHP binary, use the PHP 8.x binary configured for the account instead of `php`.

## Manual test

```bash
cd /home/isplzepc/repositories/ispluka-php
php -l app/Core/Network/PppoeActivityScheduler.php
php -l app/Core/Network/PppoeActivityCollector.php
php -l app/Core/Network/PppoeActivityRunCoordinator.php
php -l scripts/cron/pppoe-activity.php
php scripts/cron/pppoe-activity.php
```

Expected summary format:

```text
PPPoE activity sync completed: routers=N success=X failed=Y skipped=Z
```
