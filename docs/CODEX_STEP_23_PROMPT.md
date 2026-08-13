# Codex Prompt — Step 23

Implement the Hotspot live-operation layer in `oneemk/ispluka-php`.

1. Inspect existing MVC, authentication, RBAC, tenant context, PDO, router management and MikroTik adapter code before changing anything.
2. Implement a real MikroTik Hotspot adapter behind `MikroTikHotspotGateway`; keep RouterOS protocol details out of controllers.
3. Reuse existing encrypted router credentials and connection abstractions. Never expose credentials in API responses or logs.
4. Implement secure Hotspot CRUD for profiles and users. Validate arbitrary validity expressions through `ValidityDuration::parse()`.
5. First-login activation must be atomic/idempotent and calculate absolute `expires_at` exactly once.
6. Implement active-session synchronization and disconnect with tenant/router ownership checks.
7. Implement router time pre-flight. It is non-blocking and returns router time, application time, difference, warning state and `can_continue=true`. Never silently alter router time.
8. Add Fix Time only as an explicit privileged operation after confirming the existing router-management permission model.
9. Every mutation uses prepared PDO statements, browser CSRF protection where applicable, RBAC, tenant scoping, audit/operation logging and safe errors.
10. Add tests for parser, tenant isolation, first-login idempotency, expiry, time-warning threshold and disconnect authorization.
11. Keep Hotspot independent from PPPoE billing, invoices and PPPoE packages.
12. Do not use third-party product names as Hotspot UI branding.
13. Update `docs/DEVELOPMENT_STATE.md` only after implementation and tests pass.

Do not claim an operation is live unless the MikroTik adapter is actually wired and tested. Keep compatibility with PHP 8.3, PostgreSQL, PDO, Composer, Apache and cPanel shared hosting.
