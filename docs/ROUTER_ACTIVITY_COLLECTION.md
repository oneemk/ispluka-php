# Router Activity Collection

The collector must use one batched active-session read per router per scheduled cycle rather than one API request per customer. It persists compact current state and only calculates usage deltas from counters that changed.

Default scheduled collection target: every 5 minutes. A router with no recent successful sync is marked stale and its customer inactivity cannot be conclusively classified.

The collector must not run a continuous per-customer loop. Live Rx/Tx is separate and on-demand for one selected customer for a short window.

For six-month usage, write hourly aggregates rather than high-frequency raw samples. Counter resets and router reboot are treated as re-baseline events.

Collection failures are recorded per router and retried with bounded backoff. One failing router must not block collection for other routers.
