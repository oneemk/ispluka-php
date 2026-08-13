# PPPoE Enforcement Audit UI

## Networking > MikroTik > PPPoE Enforcement Audit

Mobile-first screen with summary cards:
- Success
- Failed
- Mismatch

Filters:
- Status
- Router
- Username
- Date range
- Action

Each row shows:
- Customer/PPPoE username
- Router
- Action
- Original profile
- Target profile
- Reason
- Status
- Error message when failed
- Created time
- Actor

Mismatch rows are visually prominent and provide a detail view showing the verified `before`/`after` state where available. Failed and mismatch rows must not be silently retried from the UI; retry is an explicit action subject to permission and the bounded retry policy.
