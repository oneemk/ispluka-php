# ISPLUKA Development State

## Project
- PHP 8.3+ / PostgreSQL / PDO / Composer
- Custom MVC architecture
- Namecheap shared hosting + cPanel + Apache
- GitHub repository: oneemk/ispluka-php
- No VPS migration

## Completed
1. Repository baseline/environment protection
2. MVC/core and database migration foundation
3. Security foundation
4. Authentication/session/CSRF/RBAC
5. Customer/service management
6. Packages and MikroTik routers
7. Billing/invoice engine
8. Payment gateway core
9. MikroTik PPPoE/Hotspot automation
10. Cron/billing/network automation
11. Reports + audit log + REST API foundation
12. REST API routing/controllers/authentication foundation + OpenAPI documentation
13. Payment gateway adapter foundation
14. Overdue billing policy + automatic suspend/restore lifecycle
15. Admin dashboard summary service + mobile-first UI foundation
16. Reseller portal data model + dashboard/ledger foundation
17. Reseller authorization + payment collection + commission settlement + responsive UI foundation
18. Customer portal service + authorization + responsive UI foundation
19. BTRC reporting + advanced reports + inventory + POS foundation

## UI terminology
- Database/API may use `grace_days`.
- User-facing label must be **Extra Time**.
- Never display “Grace Period” in the UI.

## Current state
- Billing automation and overdue processing exist.
- Tenant billing policies define Extra Time (stored as grace_days) and auto suspend/restore.
- Overdue services are changed to suspended and a MikroTik suspend job is queued.
- Successful payment settlement attempts customer service restoration when no overdue invoice remains.
- Network worker executes provision/suspend/restore with retry/backoff.
- Dashboard summary exposes customers, active/suspended services, overdue invoices, outstanding amount and current-month collection.
- Reseller profiles can own customers, have commission/credit settings, and maintain a transaction-safe ledger.
- Reseller authorization prevents cross-reseller customer access.
- Reseller payment collection validates ownership, settles invoice payment, and calculates commission into the reseller ledger.
- Customer portal exposes dashboard, services, invoices and payment history with tenant/customer authorization.
- Inventory supports tenant stock, movements, low-stock detection and transactional stock deduction.
- POS supports sales with inventory deduction and optional customer/invoice linkage.
- BTRC report generation stores period-based JSON payloads; advanced reports cover revenue, outstanding invoices and service status.
- Mobile-first backoffice module CSS foundation is present.

## Next work order
20. cPanel cron/deployment/observability
21. Full integration/security/performance tests and release checklist
22. Production hardening, documentation and release candidate

## Important rules
- Strict tenant isolation on every tenant-owned query.
- Never store plaintext recoverable secrets; use SecretBox for router/API credentials.
- PDO prepared statements only.
- Payment callbacks require verification and idempotency.
- Cron jobs must be idempotent and locked.
- Network work should be queued/retried.
- Public web root is public/ only.
- Never commit real .env secrets.
- Reuse existing classes before creating duplicates.
- Deployment target remains Namecheap shared hosting/cPanel; never move to VPS unless explicitly requested.

## Last completed
Step 38: BTRC reporting + advanced reports + inventory + POS foundation.

## Immediate next
Step 39: cPanel cron/deployment/observability.

## Continuation instruction
If the user returns after a long gap and says "next", "পরবর্তী", or "করুন", read this file first and continue from the Immediate next item. Work in a large coherent production batch and update this file after each completed step.
