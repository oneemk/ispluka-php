# MikroTik UI Structure

## Networking > MikroTik

- Router list: status, last sync, version, PPP active count, sync health.
- Router details: overview, PPPoE users, profiles, activity, usage, reconciliation, import, audit.
- PPPoE Activity: Active / Inactive tabs, search, active IP, last seen, uptime, live Rx/Tx action.
- Import: router selector, import summary, pending candidates, customer mapping/completion.
- Reconciliation: ERP-only, MikroTik-only, enabled mismatch, profile mismatch.
- Router Web: permission-checked action opening the configured management target (default port 8080).

All screens are mobile-first. Live Rx/Tx is on-demand and must never create a permanent high-frequency polling job.
