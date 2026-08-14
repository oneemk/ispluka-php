# ISPLUKA PHP

Production-ready multi-tenant ISP Billing & Management ERP SaaS.

## Production target

- Namecheap Shared Hosting
- cPanel
- Apache
- PHP 8.3+
- PostgreSQL
- PDO
- SSL/HTTPS
- cPanel Git deployment
- cPanel Cron

This project is designed specifically for shared hosting. VPS, Docker, Node.js, Redis, BullMQ, Nginx, PM2, Supervisor, and other long-running infrastructure are not required.

## Technology contract

- Custom PHP MVC architecture
- Composer dependency management
- PostgreSQL accessed through PDO and prepared statements
- Server-rendered PHP views with HTML/CSS/JavaScript
- Secure PHP sessions for web authentication
- RBAC and strict multi-tenant isolation
- REST API for approved integrations
- Database-backed jobs executed by cPanel Cron

## Roles

- Master Admin
- Admin
- Reseller
- Employee
- Customer

## Current implementation progress

The `feat/mikrotik-reconciliation` branch contains the current MikroTik/PPPoE enforcement work:

- PPPoE activity snapshots and bounded live-metrics API
- Six-month usage reporting
- MikroTik reconciliation/audit UI
- Verified enforcement audit with `success`, `failed`, and `mismatch`
- Manual Enable / Disable / Suspend API
- `suspend` PPP profile policy with Temporary Disable fallback when the profile does not exist
- Previous-profile tracking for safe restore
- Native RouterOS API client with no Composer dependency
- Shared-hosting-safe network job worker
- Automatic overdue enforcement queue

These features are being hardened before production deployment.

## Architecture contract

The application will follow:

Controller -> Service -> Repository -> PDO -> PostgreSQL

Rules:

- Controllers must not contain SQL.
- Views must not perform database queries.
- Business logic belongs in Services.
- Database access belongs in Repositories.
- PDO prepared statements are mandatory.
- Tenant-owned data must always be tenant-scoped.
- `tenant_id` supplied by a client request must never be trusted for authorization.
- The authenticated security context determines the active tenant.
- Secrets must never be hardcoded.
- Production errors must not expose stack traces, credentials, or sensitive data.

## MikroTik enforcement policy

For PPPoE services:

1. Automatic overdue enforcement is evaluated by cPanel Cron.
2. The scheduled enforcement target is **12:05 PM** according to the hosting/account timezone configuration.
3. If RouterOS PPP profile `suspend` exists, overdue suspension sets `profile=suspend` and keeps the secret enabled.
4. If `suspend` does not exist, the safe fallback is Temporary Disable (`disabled=yes`).
5. Every enforcement is read back from MikroTik and recorded as `success`, `failed`, or `mismatch`.
6. Manual Enable, Disable, and Suspend use the same verified execution engine.
7. Restore is allowed only when the previous PPP profile is known; unsafe profile guessing is refused.
8. The service billing state is changed to suspended/active only after the network operation succeeds.

## cPanel Cron jobs

The shared-hosting deployment uses database-backed network jobs. Configure:

- **12:05 PM:** `scripts/cron/overdue-enforcement.php`
- **Every 1–5 minutes:** `scripts/cron/network-worker.php 20`

The worker lock prevents overlapping network workers. The exact absolute PHP path and project path should be generated from the Namecheap cPanel account at deployment time.

## Database

The database layer uses native PHP PDO with the PostgreSQL driver. Connections use exception mode, associative fetches, native prepared statements, and configurable PostgreSQL SSL mode.

Migrations implement an explicit `up()`/`down()` contract and are tracked in the `schema_migrations` table. The current branch also contains billing, router, customer-service, network-job, PPPoE activity, usage, reconciliation, import, and enforcement-log migrations.

## Git workflow

The canonical repository is `oneemk/ispluka-php`.

`main` is the production branch. Development proceeds in small, reviewable steps. Secrets and runtime data must never be committed.

## Configuration

Copy `.env.example` to `.env` in local or production environments and provide environment-specific values. Never commit `.env` or real credentials.

## Deployment target

The production deployment target is Namecheap cPanel Shared Hosting with Apache, PHP 8.3+, PostgreSQL, SSL, and cPanel Cron. The application must remain compatible with these constraints throughout development.

## Status

Core application bootstrap, database foundation, billing/customer/service foundations, and the current MikroTik/PPPoE enforcement layer are implemented on the development branch. Production deployment and final end-to-end verification remain pending.
