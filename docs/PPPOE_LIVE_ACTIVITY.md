# PPPoE Live Activity

## Customer activity views

Provide separate Active and Inactive customer lists. Active state is based on the latest successful MikroTik session observation, not billing status alone.

Customer live panel may show current online/offline state, active IP, router, PPPoE username, session uptime, last seen, caller ID, current Rx rate, current Tx rate and cumulative session bytes when available.

## On-demand live Rx/Tx

Live Rx/Tx is an explicit customer action, not a dashboard-wide polling stream. When an operator opens Live Usage, the server starts a short-lived refresh window for that selected customer/router and polls only the required MikroTik active-session record. Close/timeout the view to stop polling.

Recommended refresh interval: 5–10 seconds for the selected customer only. Never poll every customer at this rate.

Normalize RouterOS counters and display `Rx Mbps / Tx Mbps` plus optional bytes in/out. If the selected user is offline, show the last known values and clearly label them as stale.

## Load protection

Normal customer lists read cached/current-state data collected by scheduled workers. The worker polls active sessions in batches per router and persists compact current state. Six-month history uses hourly/daily aggregates, not raw live samples.

Live Usage is rate-limited and permission-controlled. Multiple browser tabs must not multiply polling unnecessarily; use a short server-side cache/coalescing window per router+username.

## Active IP / Router Web

When an authorized operator clicks the active IP, generate a validated router web target using the configured management port (default `8080`). Never accept an arbitrary URL or arbitrary port from the browser. Prefer private/VPN routing or HTTPS for exposed router management endpoints. Log the access event without credentials.
