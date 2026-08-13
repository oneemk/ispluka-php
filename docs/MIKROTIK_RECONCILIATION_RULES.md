# MikroTik ↔ ERP Reconciliation Rules

The reconciliation process is read-only by default and detects human-error conditions without changing either side.

## Checks
- ERP customer exists but PPPoE secret is missing on MikroTik.
- MikroTik PPPoE secret exists but has no mapped ERP customer.
- ERP expects enabled but MikroTik secret is disabled.
- ERP expects temporary disable but MikroTik is enabled.
- ERP profile differs from the MikroTik profile.
- PPPoE username is mapped ambiguously.
- Enabled customer has no recent activity for the configured inactivity threshold (default 20 days).
- Customer has an unexpected zero/free billing state and must appear in monthly audit.

## Safety
- Reconciliation never infers payment status from RouterOS.
- It never auto-enables or auto-disables a customer merely because a mismatch is found.
- Every mismatch is assigned a stable type and severity for the audit queue.
- A remediation action must use the authoritative billing/enforcement decision and the verified RouterOS executor.
