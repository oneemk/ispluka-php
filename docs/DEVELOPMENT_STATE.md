# ISPLUKA Development State

## Project
- PHP 8.3+ / PostgreSQL / PDO / Composer
- Custom MVC architecture
- Namecheap shared hosting + cPanel + LiteSpeed-compatible public entry
- Repository: `oneemk/ispluka-php`
- Active branch: `feat/mikrotik-reconciliation`
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
11. Hotspot architecture/data boundaries and independent billing isolation
12. Hotspot flexible validity and first-login absolute expiry engine
13. Hotspot router-time pre-flight and operation guard
14. Real tenant-aware RouterOS Hotspot gateway
15. Hotspot API/controller/bootstrap wiring
16. Hotspot operational read APIs and network-operation audit logging
17. Hotspot database profile/user CRUD and activation API
18. RouterOS active-session → database synchronization foundation
19. Hotspot mutation audit coverage for database and router-backed actions
20. PATCH/DELETE request routing support and request-body parsing for API operations
21. Hotspot validity-duration smoke coverage

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

The custom router currently uses exact paths rather than URI-template parameters; update/delete IDs are therefore supplied in the request body. This is intentional and compatible with the existing application routing style.

## Tests
- Added `tests/Hotspot/ValidityDurationSmokeTest.php` covering valid duration normalization and malformed/zero/duplicate-unit rejection.
- Do not claim full Hotspot integration/security tests have passed yet; those require a real PostgreSQL test environment and RouterOS test doubles/integration coverage.

## Remaining Hotspot work
### Step 23C — Complete operational resource synchronization
1. Add controlled write APIs for IP bindings, walled garden and address lists.
2. Add RouterOS → database synchronization for hosts and operational resource state where appropriate.
3. Add Hotspot traffic aggregation/report API and login-history support.
4. Add dedicated Hotspot permissions if the existing RBAC model can support them without breaking router permissions.
5. Add real PHPUnit/security/integration coverage using PostgreSQL fixtures and RouterOS test doubles.
6. Verify all mutation audit paths, tenant isolation and failure/retry behavior.

### Step 24 — Separate Hotspot Panel UI
- Build the mobile-first Hotspot Panel only after the API/domain layer is testable.
- Keep Hotspot navigation and business logic independent from PPPoE billing.
- Include non-blocking router-time warning with Fix Time / Ignore & Continue.

### Step 25 — cPanel deployment / observability
- Verify migrations, cron commands, worker locking, logging, health checks and production-safe configuration on Namecheap/cPanel.

### Step 26 — Full integration/security/performance verification
- Run real PHPUnit/feature/security/integration coverage.
- Verify tenant isolation, encrypted credential handling and live MikroTik operations.

### Step 27 — Production hardening / release candidate
- Final documentation, rollback procedure, deployment checklist and release validation.

## Important rules
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

## Last state review
Reviewed against `feat/mikrotik-reconciliation` on 2026-08-19. Step 23B is substantially implemented: database-backed Hotspot profile/user CRUD, encrypted user credentials, idempotent first-login activation, RouterOS session synchronization, mutation audit coverage, API routing and smoke-test coverage are now present. The module is **not yet production-complete** because operational write synchronization, traffic/history, dedicated permissions and full integration/security tests remain.

## Immediate next
**Step 23C — Complete Hotspot operational resource synchronization, controlled writes, traffic/history APIs, dedicated permissions evaluation, and real integration/security tests.**

## Continuation instruction
If the user returns after a gap and says `next`, `পরবর্তী`, or `করুন`, read this file first and continue from the Immediate next item. Work in a coherent production batch and update this file after each completed step.
