# Hotspot API Contract

All endpoints are tenant-scoped and require authenticated Hotspot permissions.

## Router
- `GET /api/hotspot/routers`
- `GET /api/hotspot/routers/{id}/status`
- `GET /api/hotspot/routers/{id}/time-check`

## Profiles
- `GET /api/hotspot/profiles`
- `POST /api/hotspot/profiles`
- `PATCH /api/hotspot/profiles/{id}`
- `DELETE /api/hotspot/profiles/{id}`

Profile creation validates flexible duration expressions such as `15d`, `20h`, `90m`, and `2d 12h`.

## Users
- `GET /api/hotspot/users`
- `POST /api/hotspot/users`
- `PATCH /api/hotspot/users/{id}`
- `POST /api/hotspot/users/{id}/disable`
- `POST /api/hotspot/users/{id}/enable`
- `POST /api/hotspot/users/{id}/activate`

Activation is idempotent and first-login validity is absolute-time based.

## Operations
- `GET /api/hotspot/sessions`
- `POST /api/hotspot/sessions/{id}/disconnect`
- `GET /api/hotspot/hosts`
- `GET /api/hotspot/ip-bindings`
- `POST /api/hotspot/ip-bindings`
- `DELETE /api/hotspot/ip-bindings/{id}`
- `GET /api/hotspot/walled-garden`
- `POST /api/hotspot/walled-garden`
- `DELETE /api/hotspot/walled-garden/{id}`
- `GET /api/hotspot/address-lists`
- `POST /api/hotspot/address-lists`
- `DELETE /api/hotspot/address-lists/{id}`
- `GET /api/hotspot/traffic`
- `GET /api/hotspot/logs`

## Router time warning
Time check is non-blocking. API responses expose the difference and warning state; the UI must provide `Fix Time` and `Ignore & Continue` without blocking normal panel operations.
