# ISPLUKA Development State

## Project
- PHP 8.3+ / PostgreSQL / PDO / Composer
- Custom MVC architecture
- Namecheap shared hosting + cPanel + Apache/LiteSpeed-compatible public entry
- GitHub repository: oneemk/ispluka-php
- Active development branch: `feat/mikrotik-reconciliation`
- No VPS migration; deployment remains cPanel/shared hosting

## Completed / implemented foundation
1. Repository baseline/environment protection
2. MVC/core and database migration foundation
3. Security foundation
4. Authentication/session/CSRF/RBAC
5. Customer/service management
6. Packages and MikroTik routers
7. Billing/invoice engine
8. Payment gateway core
9. MikroTik PPPoE automation foundation
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
20. Hotspot architecture requirements and boundaries locked
21. Hotspot core data model + flexible validity + first-login expiry engine + router-time check
22. Hotspot operational data model + operation guard + API contract
23. PPPoE activity snapshots / bounded live-metrics design and implementation foundation
24. Six-month PPPoE usage history/reporting foundation
25. MikroTik reconciliation and enforcement audit foundation
26. Verified PPPoE enforcement outcomes: `success`, `failed`, `mismatch`
27. Manual PPPoE Enable / Disable / Suspend execution path
28. Safe `suspend` profile policy with Temporary Disable fallback and previous-profile restore tracking
29. Native RouterOS API client
30. RouterOS SSH client integration using phpseclib
31. Per-router RouterOS API/SSH connection-method selection and encrypted router credentials
32. Shared-hosting-safe database-backed network worker and overdue enforcement queue
33. Tenant-scoped MikroTik router management and connection testing
34. Customer import/reconciliation flow foundation and import documentation
35. Real RouterOS Hotspot gateway foundation
36. Hotspot API controller/bootstrap wiring
37. Hotspot operational read APIs for sessions, hosts, bindings, walled garden, address lists and logs
38. Hotspot network-operation audit service with actor context

## Verified current repository state
The branch was re-inspected against the repository structure, README, AI project context, API/architecture/deployment documentation, MikroTik/PPPoE documentation, Hotspot documentation, and current PHP source tree.

### MikroTik / PPPoE
- `RouterService` supports RouterOS API and SSH connection methods.
- PPPoE active-session read/disconnect and provisioning/suspend/restore paths exist.
- Hotspot provisioning/suspend/restore helper paths exist inside `RouterService`.
- Router credentials are stored encrypted and decrypted only in the network service path.
- Reconciliation is read-only by default and must never infer payment status from RouterOS or auto-change customer state merely because of a mismatch.
- PPPoE live activity uses bounded router polling plus on-demand selected-customer Live Rx/Tx.
- Router activity collection is designed around one batched active-session read per router per cycle, compact current-state persistence, and hourly/daily historical aggregation.
- Enforcement retry is explicit, permission-controlled, bounded, and re-reads current RouterOS state before retrying.

### Hotspot
The Hotspot core existed before this state review and is now wired further:
- `HotspotRepository`
- `HotspotCrudService`
- `HotspotActionService`
- `HotspotAuditService`
- `HotspotOperationGuard`
- `HotspotValidityService`
- `ValidityDuration`
- `RouterTimeCheckService`
- Tenant-aware `MikroTikHotspotGateway`
- `RouterOsHotspotGateway` using the existing `RouterRepository`, `SecretBox`, and `MikrotikConnectionClient`
- `HotspotApiController`
- Hotspot services wired in `bootstrap/app.php`
- Secured Hotspot API routes wired in `routes/web.php`

The live gateway currently supports tenant-scoped router time reads, active Hotspot session reads, session disconnect, router-side Hotspot user create/update/enable/disable operations, and encrypted router credentials through the existing connection abstraction. Operational read APIs now expose hosts, IP bindings, walled garden, address lists and Hotspot operation logs. Network mutations/actions record success/failure audit entries with the authenticated actor when available.

This is **not yet a production-complete Hotspot module**. Database user CRUD/activation synchronization, profile update/delete, RouterOS-to-database session synchronization, write APIs for bindings/walled garden/address lists, traffic API, dedicated Hotspot permissions, complete mutation audit coverage, real integration/security tests, and the Hotspot UI are still pending.

### Tests
The repository contains the test directory structure (`Unit`, `Feature`, `Integration`, `Security`), but the branch inspection did not find actual test files in those directories. Do not claim Hotspot integration/security tests have passed until tests are added and executed.

## UI terminology
- Database/API may use `grace_days`.
- User-facing label must be **Extra Time**.
- Never display “Grace Period” in the UI.
- Hotspot is a separate UI and must not use third-party product names as branding.

## Hotspot requirements locked
- Hotspot is independent from PPPoE packages, customer services, invoices and billing lifecycle.
- The same MikroTik router may serve PPPoE and Hotspot, but business logic remains isolated.
- Hotspot validity supports arbitrary duration expressions such as `11d`, `15d`, `20h`, `5h`, `90m`, `2d 12h` and `1d 6h 30m`.
- First-login activation is the primary validity mode: unused users have no running validity; first successful activation stores `activated_at` and calculates absolute `expires_at`.
- Offline time consumes validity; repeated login events cannot extend the original expiry.
- Hotspot Panel checks MikroTik clock against application/server time on entry.
- Clock warning is non-blocking and provides Fix Time / Ignore & Continue.
- Ignore must never disable normal Hotspot operations.
- Router time is never silently changed; correction requires explicit administrator action.

## Current state
- Billing automation and overdue processing exist.
- Tenant billing policies define Extra Time (stored as `grace_days`) and auto suspend/restore.
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
- PPPoE activity, usage, reconciliation, import, enforcement audit and manual enforcement foundations are present.
- Router connection testing and per-router API/SSH selection are present.
- Hotspot schema separates profiles/users from PPPoE billing and includes sessions, IP bindings, hosts, walled garden, address lists and operation logs.
- Hotspot API contract is documented.
- Hotspot live gateway/API foundation is wired, but the module is still incomplete and must not be described as production-ready.

## Next work order
### Step 23B — Complete Hotspot API/domain synchronization
1. Implement database-backed Hotspot user CRUD and profile update/delete operations through the existing repository/service pattern.
2. Implement first-login activation atomically and idempotently, calculating `expires_at` exactly once.
3. Synchronize RouterOS active sessions into the existing Hotspot session tables with tenant/router ownership checks.
4. Extend operation/audit logging to every Hotspot mutation, including database CRUD and router-backed resources.
5. Add full contract endpoints for bindings, hosts, walled garden, address lists and traffic, including controlled writes where required.
6. Add dedicated Hotspot permission codes only if the existing RBAC design supports them without breaking router permissions.
7. Add PHPUnit unit/security/integration tests before declaring the API live.

### Step 24 — Separate Hotspot Panel UI
- Build the dedicated mobile-first Hotspot Panel only after the API/domain synchronization layer is testable.
- Keep Hotspot navigation and business logic independent from PPPoE billing.

### Step 25 — cPanel deployment / observability
- Verify cron commands, worker locking, logging, health checks and production-safe configuration on Namecheap/cPanel.

### Step 26 — Full integration/security/performance verification
- Run real PHPUnit/feature/security/integration coverage and perform tenant-isolation, credential-safety and network-operation verification.

### Step 27 — Production hardening / release candidate
- Final documentation, rollback procedure, deployment checklist and release validation.

## Important rules
- Strict tenant isolation on every tenant-owned query.
- Never store plaintext recoverable secrets; use SecretBox for router/API credentials.
- PDO prepared statements only.
- Payment callbacks require verification and idempotency.
- Cron jobs must be idempotent and locked.
- Network work should be queued/retried.
- Public web root is `public/` only.
- Never commit real `.env` secrets.
- Reuse existing classes before creating duplicates.
- Do not replace the custom PHP architecture with Laravel or the previous Node/Next.js stack.
- Do not modify historical migrations blindly; inspect migration history/schema before schema changes.
- Deployment target remains Namecheap shared hosting/cPanel; never move to VPS unless explicitly requested.

## Last state review
Reviewed against the current `feat/mikrotik-reconciliation` branch on 2026-08-19. The previous Step-22-only state was stale. The branch already contains substantial PPPoE/MikroTik enforcement, activity, usage, reconciliation, import, API/SSH connectivity, and Hotspot core work. The branch now also contains a real RouterOS Hotspot gateway foundation, secured API wiring, operational read endpoints, and resilient network-operation audit logging.

## Immediate next
**Step 23B — Complete Hotspot database synchronization, full API/domain contract, complete audit coverage, and tests.**

## Continuation instruction
If the user returns after a long gap and says `next`, `পরবর্তী`, or `করুন`, read this file first and continue from the Immediate next item. Work in a large coherent production batch and update this file after each completed step.
