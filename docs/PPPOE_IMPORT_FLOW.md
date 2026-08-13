# PPPoE Import Flow

1. Read RouterOS PPP secrets/active-user data in a bounded batch.
2. Normalize username, profile, IP and caller-id.
3. Match by tenant + router + PPP username; never merge users across tenants.
4. Existing customers are linked without overwriting manually entered ERP data by default.
5. Unknown MikroTik users become import candidates.
6. Incomplete candidates are shown in a dedicated queue with fields that still need completion.
7. Operator can open one candidate and complete customer identity, billing/package and required ERP fields.
8. Save creates/links the customer and records the import/mapping audit event.
9. Import must be idempotent: repeating the same RouterOS import must not create duplicate customers.
10. No customer is auto-marked paid/free merely because a MikroTik account exists.
