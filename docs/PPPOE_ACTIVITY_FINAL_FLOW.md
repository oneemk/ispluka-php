# PPPoE Activity Final Flow

- Router polling collects active PPPoE sessions in bounded batches.
- Activity state is upserted instead of writing a row for every live packet.
- `last_seen_at` is retained so an enabled account that disappears from active sessions can be identified as inactive.
- The 20-day audit reads stored state; it does not repeatedly query MikroTik for every customer.
- Live Rx/Tx is on-demand and guarded to a 5-10 second refresh window only while the operator is viewing one customer.
- Daily usage history is aggregated separately so the application does not store high-frequency samples for six months.
- Usage retention is six months; old daily records can be purged by date.
- The UI should expose Active, Inactive, Inactive >20 days and Live Usage views.
- Live usage must stop polling when the page is closed or the customer changes.
- Router-wide polling must never be driven by every browser session.
