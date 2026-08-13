# ISPLUKA Development State

## Project
- PHP 8.3+ / PostgreSQL / PDO / Composer
- Custom MVC architecture
- Namecheap shared hosting + cPanel + Apache
- GitHub repository: oneemk/ispluka-php
- No VPS migration

## Completed
1. Repository baseline and environment protection
2. MVC/core foundation
3. Database/migration foundation
4. Security foundation
5. Authentication/session/CSRF/RBAC foundation
6. Customer management core
7. Packages and MikroTik router models
8. Billing/invoice engine
9. Payment gateway architecture
10. MikroTik PPPoE/Hotspot automation
11. Cron lock, billing automation, network job worker

## Current state
- Daily billing automation can generate invoices for active services whose next_billing_at is due.
- Overdue invoices are processed by the daily billing runner.
- Network jobs support provision, suspend and restore with retry/backoff.
- PostgreSQL advisory locking prevents duplicate cron execution.

## Next work order
12. Reports + audit log + REST API foundation
13. Complete payment gateway adapters/webhooks and reconciliation
14. Auto suspend/restore policy integration with overdue billing
15. Admin dashboard and mobile-first UI
16. Reseller portal
17. Customer portal UI
18. BTRC/reporting/inventory/POS modules
19. cPanel cron/deployment scripts and production observability
20. Full integration tests, security audit, performance review and release checklist

## Working rule
When the user says "next", "পরবর্তী", "করুন", or similar, inspect this file and the repository before choosing work. Prefer a large coherent production batch rather than tiny changes. Do not move deployment to VPS. Target cPanel/shared hosting.

## Important architecture rules
- Strict tenant isolation on every tenant-owned query.
- Never store plaintext passwords/API secrets; use SecretBox/encrypted fields where secrets must be recoverable.
- Never trust payment callbacks without gateway verification and idempotency.
- Use PDO prepared statements only.
- Keep cron jobs idempotent and protected by locks.
- Network operations must be queued/retried rather than blocking web requests where practical.
- Keep public web root limited to public/.
- Do not commit real .env secrets.

## Last completed batch
Step 29: automation and cron engine.

## Immediate next step
Step 30: Reports + Audit Log + REST API foundation.
