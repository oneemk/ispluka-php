# Repository Facts & Safe Development Map

> **Purpose:** This document records facts verified from the repository and the live cPanel checkout so future work does not rely on guesses.
>
> **Rule:** Read this document together with `docs/AI_PROJECT_CONTEXT.md` before making changes. If code and this document disagree, inspect the actual code/database first and then update this document.

## 1. Repository / production checkout

GitHub repository:

```text
oneemk/ispluka-php
```

Live cPanel repository checkout verified on 2026-08-25:

```text
/home/isplzepc/repositories/ispluka-php
```

The directory:

```text
/home/isplzepc/ispluka-php
```

was empty and is **not** the Git repository.

A separate repository exists at:

```text
/home/isplzepc/repositories/ispluka-php-broken
```

Do not use or modify that repository unless explicitly required.

Remote:

```text
git@github.com:oneemk/ispluka-php.git
```

## 2. Current deployment branch verified

The live checkout was switched to:

```text
feat/customer-router-suspend-detection
```

It tracks:

```text
origin/feat/customer-router-suspend-detection
```

The branch was updated through commit:

```text
ad6148e
feat: add CLI migration runner for cPanel deployment
```

The previous live branch was:

```text
feat/hotspot-step23-live-api
```

A backup branch was created before switching:

```text
backup-before-pppoe-suspend-deploy-20260825-114748
```

## 3. Important server-only file

The live checkout contains an untracked file:

```text
error_log
```

Do not blindly add or delete it. It is server-generated and was intentionally left untracked during deployment work.

## 4. Environment / database

The live repository has:

```text
.env
```

with these verified non-secret settings:

```text
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=isplzepc_ispluka
DB_USERNAME=isplzepc_ispluka_user
```

Do **not** document or commit the database password.

The PostgreSQL database backup successfully created on 2026-08-25 is:

```text
/home/isplzepc/ispluka-php-backup-20260825-115315.dump
```

Size at creation: approximately 219 KB.

A previous failed dump created a 0-byte file and must not be treated as a valid backup:

```text
/home/isplzepc/ispluka-php-backup-20260825-115232.dump
```

## 5. Project structure verified

Top-level application structure currently includes:

```text
app/
bootstrap/
config/
database/
docs/
public/
resources/
routes/
scripts/
tests/
vendor/
composer.json
.env
```

## 6. Database / migration architecture

Database migrations live in:

```text
database/migrations/
```

Migration contract:

```text
database/migrations/MigrationInterface.php
```

Migration runner:

```text
database/migrations/MigrationRunner.php
```

Migration tracking table:

```text
schema_migrations
```

`MigrationRunner`:

- creates `schema_migrations` if needed;
- keys migrations by filename;
- skips already-applied migration names;
- assigns a new batch number;
- runs each migration inside a transaction;
- records the migration only after successful commit.

The repository now has the cPanel CLI entrypoint:

```text
scripts/setup/migrate.php
```

It loads `.env`, connects to PostgreSQL, discovers migration files, and invokes `MigrationRunner`.

**Never invent another migration command. Inspect this entrypoint and the runner first.**

## 7. Verified production migration history

The live database's `schema_migrations` table was inspected on 2026-08-25.

Existing migrations include the core schema, customer services, routers, billing, network jobs, Hotspot modules, PPPoE activity/enforcement SQL migrations, router reconciliation, and other historical migrations.

The latest verified applied migration before the new PPPoE suspend-profile migration was:

```text
20260813_000019_reconcile_routers_schema.php
batch=11
```

Do not assume all files in `database/migrations/` are applied. Always inspect `schema_migrations` before changing production schema.

## 8. PPPoE suspend-profile migration status

The target branch contains:

```text
database/migrations/20260825_000001_pppoe_suspend_profiles.php
```

However, on the live database verified on 2026-08-25:

```text
pppoe_suspend_profiles = NOT FOUND
```

Therefore the migration has **not yet been confirmed as applied** to production.

Do not confuse this database table with a MikroTik PPP profile. They are different layers:

```text
ERP PostgreSQL table
    pppoe_suspend_profiles

vs.

MikroTik RouterOS PPP profile
    configured on each router
```

## 9. Existing migration warning / historical safety

`docs/AI_PROJECT_CONTEXT.md` already records previous migration failures and explicitly requires inspection of migration files, `MigrationRunner`, migration history, current PostgreSQL schema, and production data before schema changes.

Known historical errors include:

```text
MigrationInterfaceInterfaceInterface
routers_status_check
```

Therefore:

- do not blindly rerun every migration;
- do not delete historical migrations;
- do not reset production database;
- do not drop production tables;
- prefer forward-compatible migrations;
- inspect actual production state before applying changes.

## 10. PPPoE vs Hotspot boundary

For the current customer suspension work:

```text
PPPoE = in scope
Hotspot = separate Hotspot module
```

Do not add Hotspot suspension behavior to the PPPoE customer workflow.

## 11. Required PPPoE suspension behavior

Verified product requirement:

1. Customer service is PPPoE.
2. Customer creation uses a tenant-scoped Router selector; the backend receives the router ID, not a user-entered router identifier.
3. Suspension must inspect the configured suspend-profile capability for the selected MikroTik router.
4. If a usable suspend profile exists, suspend by moving the customer's PPP secret to that suspend profile.
5. If no suspend profile is configured/available, disable the PPP secret instead.
6. On successful payment, restore a suspended customer to the **original PPP profile** and re-enable the PPP secret when it had been disabled.
7. The system must not invent MikroTik API field names, profile names, or operations. Existing RouterOS provisioning/enforcement code is the source of truth.
8. A configured suspend profile is intended to provide a suspension page / no normal Internet access rather than usable Internet access.

## 12. Suspend Profile dashboard module

Required location:

```text
Dashboard -> Tools -> Suspend Profile
```

Purpose:

- create/manage the ERP-side suspend-profile configuration;
- associate it with the appropriate tenant/router context;
- use real MikroTik capability/configuration detection;
- never show fake/unsupported controls.

## 13. Customer router selection

Current requirement:

```text
Router -> Select Router dropdown
```

The dropdown must show only the tenant's active/available MikroTik routers.

The frontend must send the selected backend router ID. It must not ask the customer/admin to type a router ID manually.

## 14. Verified migration command result

On 2026-08-25:

```bash
php -l scripts/setup/migrate.php
```

returned:

```text
No syntax errors detected in scripts/setup/migrate.php
```

The migration entrypoint was then executed without a visible error, but the subsequent database inspection showed that `pppoe_suspend_profiles` was still absent. Therefore **do not claim the migration was applied** until the discovery/execution path is inspected and the table is verified directly in PostgreSQL.

## 15. cPanel PHP CLI warning

The live environment produced:

```text
Unsuccessful stat on filename containing newline at /var/cpanel/ea4/ea_php_cli.pm line 87.
```

during inline `php -r` commands.

This warning is from the cPanel PHP CLI wrapper and is not, by itself, proof of an application or migration failure.

## 16. Safe workflow for future AI/code changes

Before changing code:

1. Read `docs/AI_PROJECT_CONTEXT.md`.
2. Read this `docs/REPOSITORY_FACTS.md`.
3. Inspect all relevant files in the current branch.
4. Inspect existing migrations and `schema_migrations`.
5. Inspect actual service/provisioning/enforcement implementations.
6. Never guess an API field, database column, RouterOS command, profile name, or route.
7. Make the smallest forward-compatible change.
8. Run syntax/tests before deployment.
9. Take a database backup before production schema changes.
10. Verify the actual production database after migration.
11. Verify real MikroTik behavior only after backend/database checks pass.

## 17. Current next task

Before applying the PPPoE suspend-profile migration, inspect:

```text
scripts/setup/migrate.php
database/migrations/20260825_000001_pppoe_suspend_profiles.php
database/migrations/MigrationRunner.php
```

Determine why the new migration was not applied/recorded, fix the discovery/execution path without altering historical migrations, then verify:

```text
pppoe_suspend_profiles
```

exists in PostgreSQL.

Only after that should real MikroTik suspend-profile creation/detection and customer suspend/restore tests proceed.
