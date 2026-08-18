# ISPLUKA Development State

## Project
- PHP 8.3+ / PostgreSQL / PDO / Composer
- Custom MVC architecture
- Namecheap shared hosting + cPanel + LiteSpeed-compatible public entry
- Repository: `oneemk/ispluka-php`
- Active branch: `feat/mikrotik-reconciliation`
- Canonical server repository path: `/home/isplzepc/repositories/ispluka-php`
- Public application path: `/home/isplzepc/repositories/ispluka-php/public`
- cPanel document root: `/home/isplzepc/public_html`
- Git repository: `/home/isplzepc/repositories/ispluka-php/.git`
- No VPS migration

## Completed / implemented foundation
1. Repository/environment protection and custom MVC/database foundation
2. Authentication, sessions, CSRF and RBAC
3. Customer/service management
4. Packages, billing, invoices, payments and subscription foundation
5. MikroTik PPPoE automation, API/SSH connectivity and encrypted router credentials
6. Cron/network worker, overdue suspend/restore and verified enforcement outcomes
7. PPPoE activity, bounded live metrics, six-month usage, reconciliation and enforcement audit
8. Customer import/reconciliation foundation
9. Reseller and customer portals
10. BTRC, reports, inventory and POS foundation
11. Dashboard operational overview and bilingual responsive UI
12. Hotspot architecture/data boundaries and independent billing isolation
13. Hotspot flexible validity and first-login absolute expiry engine
14. Hotspot router-time pre-flight and operation guard
15. Real tenant-aware RouterOS Hotspot gateway
16. Hotspot API/controller/bootstrap wiring
17. Hotspot operational read APIs and network-operation audit logging
18. Hotspot database profile/user CRUD and activation API
19. RouterOS active-session → database synchronization foundation
20. Hotspot mutation audit coverage for database and router-backed actions
21. PATCH/DELETE request routing support and request-body parsing for API operations
22. Hotspot validity-duration smoke coverage
23. Hotspot controlled operational resource gateway/service foundation
24. Hotspot IP binding, walled-garden and address-list read/create/delete API routes
25. Hotspot traffic aggregation API and database login-history API
26. RouterOS Hotspot-focused log read API with audit coverage

## Dashboard state
- Dashboard remains the authenticated primary landing page at `GET /`.
- Dashboard now follows the documented ISP ERP business order: quick actions → business snapshot → network health → collection/service health → recent collections/customers.
- Business Snapshot displays the required customer, service, billing and collection metrics without duplicating the same KPI in multiple unrelated sections.
- Network Health is separated from financial KPIs and surfaces total/online/offline MikroTik routers with direct Network Audit and router-management links.
- Collection Overview retains the six-month completed-payment trend supplied by `DashboardService`.
- Recent Collections and Recent Customers remain available with compact operational rows and clear links.
- Dashboard is responsive for desktop, tablet and mobile layouts and keeps the existing sidebar/header architecture.
- Dashboard language switching now covers the reorganized labels in both English and বাংলা.
- Dashboard uses the existing `DashboardService` data contract; no database migration or new dashboard data source was introduced.
- Existing customer, collection, network and subscription navigation remains intact.

## Verified Hotspot state
- Hotspot profiles support arbitrary duration expressions through `ValidityDuration`.
- First-login activation is transactionally locked and idempotent: repeated activation cannot extend `expires_at`.
- Hotspot user passwords are stored encrypted using the existing `SecretBox`; plaintext credentials are only used for the immediate RouterOS provisioning request.
- Profile create/update/delete is tenant-scoped; profiles with attached users cannot be deleted.
- User create/update/status/activate flows are tenant-scoped.
- Re-enabling an already-activated user restores `active` only while its absolute expiry remains valid; it does not reset validity.
- RouterOS active Hotspot sessions can be synchronized into `hotspot_sessions` with tenant/router/user ownership checks.
- Successful RouterOS snapshots reconcile disappeared sessions as ended; transient empty snapshots are not treated as a disconnect-all event.
- Hotspot router operations use the existing encrypted router credentials and RouterOS API/SSH connection abstraction.
- Hotspot network and database mutation actions record success/failure audit entries with authenticated actor context where available.
- Existing Hotspot operational read APIs expose sessions, hosts, IP bindings, walled garden, address lists and logs.
- Controlled RouterOS operational resources are allow-listed; arbitrary RouterOS command paths are not accepted by the Hotspot API.
- IP bindings, walled garden and address lists have explicit read/create/delete operations guarded by router permissions and CSRF for mutations.
- Traffic API aggregates active RouterOS Hotspot bytes in/out and returns a bounded top-user list.
- Login history is read from tenant-scoped `hotspot_sessions` data and can be filtered by router with a bounded limit.
- RouterOS logs are filtered to Hotspot/login/logout-related records before returning to the caller.
- All operational resource/router-log actions are tenant-scoped through `RouterRepository` and the existing encrypted RouterOS connection abstraction.
- Hotspot routes remain protected by authentication, subscription guard, router view/manage permission and CSRF for mutations.

## Hotspot API routes currently exposed
- `GET /api/hotspot/profiles`
- `POST /api/hotspot/profiles`
- `PATCH /api/hotspot/profiles/update`
- `DELETE /api/hotspot/profiles/delete`
- `GET /api/hotspot/users`
- `POST /api/hotspot/users`
- `PATCH /api/hotspot/users/update`
- `POST /api/hotspot/users/activate`
- `POST /api/hotspot/users/disable`
- `POST /api/hotspot/users/enable`
- `GET /api/hotspot/sessions`
- `POST /api/hotspot/sessions/disconnect`
- `POST /api/hotspot/sessions/sync`
- `GET /api/hotspot/routers/time-check`
- `GET /api/hotspot/routers/active`
- `GET /api/hotspot/hosts`
- `GET /api/hotspot/ip-bindings`
- `GET /api/hotspot/walled-garden`
- `GET /api/hotspot/address-lists`
- `GET /api/hotspot/logs`
- `GET /api/hotspot/operational/ip-bindings`
- `POST /api/hotspot/operational/ip-bindings`
- `DELETE /api/hotspot/operational/ip-bindings`
- `GET /api/hotspot/operational/walled-garden`
- `POST /api/hotspot/operational/walled-garden`
- `DELETE /api/hotspot/operational/walled-garden`
- `GET /api/hotspot/operational/address-lists`
- `POST /api/hotspot/operational/address-lists`
- `DELETE /api/hotspot/operational/address-lists`
- `GET /api/hotspot/traffic`
- `GET /api/hotspot/login-history`
- `GET /api/hotspot/router-logs`

The custom router uses exact paths rather than URI-template parameters; operational resource IDs are therefore supplied in the request body for delete operations. Resource type is derived from the exact route path and mapped to an internal allow-list.

## Tests
- Added `tests/Hotspot/ValidityDurationSmokeTest.php` covering valid duration normalization and malformed/zero/duplicate-unit rejection.
- The dashboard UI batch introduces no database migration.
- Do not claim full Hotspot integration/security tests have passed yet; those require a real PostgreSQL test environment and RouterOS test doubles/integration coverage.

## Remaining Hotspot work
### Step 23C — Verification and synchronization hardening
1. Add RouterOS → database synchronization for hosts and operational resource state where appropriate.
2. Decide whether operational resources should be persisted locally for audit/history, without duplicating RouterOS as an unsafe second source of truth.
3. Add dedicated Hotspot permissions if the existing RBAC model can support them without breaking router permissions.
4. Add real PHPUnit/security/integration coverage using PostgreSQL fixtures and RouterOS test doubles.
5. Verify all mutation audit paths, tenant isolation, RouterOS failure handling and retry behavior.
6. Verify RouterOS API and SSH behavior for every operational resource command on supported RouterOS versions.

### Step 24 — Separate Hotspot Panel UI
- Build the mobile-first Hotspot Panel only after the API/domain layer is testable.
- Keep Hotspot navigation and business logic independent from PPPoE billing.
- Include non-blocking router-time warning with Fix Time / Ignore & Continue.
- Provide operational resource management, live sessions, traffic, login history and audit views.

### Step 25 — cPanel deployment / observability
- Verify migrations, cron commands, worker locking, logging, health checks and production-safe configuration on Namecheap/cPanel.

### Step 26 — Full integration/security/performance verification
- Run real PHPUnit/feature/security/integration coverage.
- Verify tenant isolation, encrypted credential handling and live MikroTik operations.
- Verify bounded API response sizes and failure/retry behavior under slow or unavailable routers.

### Step 27 — Production hardening / release candidate
- Final documentation, rollback procedure, deployment checklist and release validation.

## Important rules
- Canonical server repository path is `/home/isplzepc/repositories/ispluka-php`; do not use `/home/isplzepc/ispluka-php` as the project working tree.
- Git commands for this project must run from `/home/isplzepc/repositories/ispluka-php`.
- Strict tenant isolation on every tenant-owned query.
- Never store plaintext recoverable secrets; use `SecretBox` for router and Hotspot credentials.
- PDO prepared statements only.
- Payment callbacks require verification and idempotency.
- Cron jobs must be idempotent and locked.
- Network work should be queued/retried where appropriate.
- Public web root is `public/` only.
- Never commit real `.env` secrets.
- Reuse existing classes before creating duplicates.
- Do not replace the custom PHP architecture with Laravel or the previous Node/Next.js stack.
- Do not modify historical migrations blindly; inspect migration history/schema before schema changes.
- Deployment target remains Namecheap shared hosting/cPanel.
- Hotspot operational APIs must never accept arbitrary RouterOS command names from HTTP input.

## Last state review
Reviewed against `feat/mikrotik-reconciliation` on 2026-08-19. Dashboard UI was reorganized to match the documented ISP ERP business order and bilingual responsive requirements. Step 23C remains partially implemented: controlled RouterOS operational resource operations, traffic aggregation, login history and RouterOS Hotspot log APIs are wired with tenant checks, router permissions, CSRF on mutations and audit coverage. No database migration was introduced. The module is **not yet production-complete** because host/resource synchronization, dedicated permissions evaluation, RouterOS API/SSH integration verification and full integration/security tests remain.

## Immediate next
**Step 23C — Verification and synchronization hardening: host/resource synchronization strategy, dedicated Hotspot permissions evaluation, RouterOS API/SSH verification, and real integration/security tests.**

## Continuation instruction
If the user returns after a gap and says `next`, `পরবর্তী`, or `করুন`, read this file first and continue from the Immediate next item. Work in a coherent production batch and update this file after each completed step.
