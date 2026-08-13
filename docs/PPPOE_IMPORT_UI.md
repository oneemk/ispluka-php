# PPPoE Import UI

The MikroTik import screen is a dedicated mobile-first workflow.

- Router selector and last successful sync status.
- Import action with progress/result summary: seen, created, updated, skipped, errors.
- Pending candidates queue with filters: Pending, Completed, Incomplete.
- Each candidate shows username, profile, active IP, caller ID and mapping state.
- Search existing customer by name, phone or PPPoE username.
- One-tap mapping opens a completion form for missing ERP fields.
- Bulk import is bounded; oversized imports must be split into batches.
- Errors are shown per row and do not invalidate successful rows.
- No payment/free status is inferred from MikroTik import.
