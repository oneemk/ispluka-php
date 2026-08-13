# ISPLUKA REST API

Base path: `/api/v1`

## Authentication

API authentication uses a scoped token context. Tokens must never be logged or returned in plaintext after issuance.

## Rate limiting

The API rate limiter is database-backed and tenant-safe. Production deployments should place a web-server/WAF limit in front of PHP as an additional layer.

## Health

`GET /api/v1/health`

Returns a minimal service-health payload without exposing infrastructure secrets.

## Response contract

Success: `{ "success": true, "data": ... }`

Error: `{ "success": false, "message": "...", "errors": {} }`

Future resource endpoints should enforce tenant context and RBAC before repository access.
