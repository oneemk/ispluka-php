# PPPoE Usage & Customer Router Access

## Live activity
Poll MikroTik PPP active/session counters on a scheduled worker. Do not poll on every dashboard request. The default collection interval is 15 minutes.

For each observed session keep compact current state: tenant, router, customer/username, active IP, session identity, last seen, bytes in/out, uptime and caller ID. A successful poll also updates the router sync timestamp.

## Six-month history without high load
Do not retain every raw RouterOS response for six months. Keep raw snapshots for troubleshooting for about 48 hours, then persist compact hourly/daily aggregates for about 184 days. Usage totals are calculated from byte-counter deltas and handle reconnects/counter resets by session identity and monotonicity checks.

Flow: `MikroTik poll -> normalize -> delta calculation -> hourly aggregate upsert -> update current state -> expire raw snapshots`.

Dashboard charts read aggregates, not MikroTik directly. Customer pages read current state plus aggregates. This avoids repeated RouterOS calls and keeps web/database load predictable.

## Active IP / router web access
When a customer has an active IP, the UI may offer **Router Web**. Default target port is `8080`; the URL is generated only after strict IP/port validation. The browser navigates directly to the endpoint; ISPLUKA does not proxy or store router credentials.

Access must be permission-controlled and audited. A management endpoint exposed on a public IP is sensitive; VPN/private routing or HTTPS is preferred. The application must never accept an arbitrary URL from the browser for this action.

## Inactivity audit
`enabled PPPoE + router sync healthy + last_seen_at older than 20 days` becomes a 20+ day inactivity finding. If router synchronization is stale/failed, report **Cannot Determine** instead of falsely declaring the customer inactive.
