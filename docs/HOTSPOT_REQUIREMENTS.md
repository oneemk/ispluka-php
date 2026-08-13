# Hotspot Panel Requirements

## UI and branding
- Hotspot is a separate UI from the main ERP billing screens.
- The UI must be modern, mobile-first, touch-friendly and responsive.
- Do not use the name of any third-party Hotspot management product in UI text, page titles, navigation, documentation, or branding.
- The panel is operationally focused on MikroTik Hotspot management.

## Business isolation
Hotspot has no business relation to PPPoE billing: no PPPoE package FK, invoice FK, customer-service FK, monthly billing dependency, or PPPoE billing auto-suspend/restore dependency. The same physical MikroTik router may be referenced by both systems, but Hotspot business logic is isolated.

## Flexible validity profiles
Profiles support arbitrary duration expressions, not a fixed list. Examples: `11d`, `15d`, `20h`, `5h`, `90m`, `2d 12h`, `1d 6h 30m`. The parser must normalize the expression to an exact duration and reject malformed, negative, or zero values.

### First-login activation
- New user starts as `unused`.
- Validity does not start while unused.
- On the first successful activation/login, persist `activated_at` and calculate `expires_at` from the exact configured duration.
- Offline time still consumes validity.
- Expiry uses absolute timestamps, not cumulative online usage.
- Activation must be idempotent so repeated login events cannot extend validity.

Example: a `15d` profile first activated at 2026-08-13 10:00 expires at 2026-08-28 10:00 even if the user only connects for one hour.

## MikroTik router time pre-flight
On entering Hotspot Panel, verify router date/time against application/server time. When the difference exceeds configurable tolerance, show a NON-BLOCKING warning containing router time, server time, difference, `Fix Time`, and `Ignore & Continue`.

Ignoring the warning must never prevent normal Hotspot operations. The panel remains usable for routers, profiles, users, sessions, bindings, hosts, traffic and logs. Validity/expiry operations may show a contextual reminder that an unsynchronized router clock can make router-side expiry behavior inaccurate.

Never silently modify router time. Time correction requires explicit administrator action through a controlled Fix Time flow.

## Operational modules
Dashboard; Routers; Hotspot Servers; Profiles; Users; Active Users; Sessions; IP Bindings; Hosts; Walled Garden; Address Lists; Traffic; Login History; Logs.

## Profile fields
name, code, duration expression/normalized duration, activation mode, speed/rate limit, data limit, session limit, shared users, status.

## Reliability
MikroTik API operations require timeouts, safe error handling and audit logging. Time checks must tolerate normal network latency. Expiry processing must be idempotent. Tenant isolation applies to every Hotspot record.
