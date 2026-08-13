# PPPoE Enforcement Retry

- Only `Failed` and `Mismatch` operations are eligible.
- Retry is explicit; the UI never retries automatically.
- Retry requires the appropriate management permission.
- Maximum attempts are bounded (default 3).
- Before retry, read the current MikroTik state again.
- If the current state already matches the desired state, mark the operation resolved without sending another command.
- Otherwise execute through the verified executor and compare the resulting state.
- Persist the new result and before/after state diff for auditability.
- Never enable a customer solely because an old Failed/Mismatch record exists; the current billing/enforcement decision remains authoritative.
