# ISPLUKA PostgreSQL Schema

## Design principles

- PostgreSQL 14+ compatible SQL where possible.
- `BIGSERIAL` primary keys are used for high-volume operational tables.
- Every tenant-owned table carries `tenant_id` unless the record is global by design.
- Tenant boundaries are enforced in application repositories/services; request-supplied tenant IDs are never trusted for authorization.
- Foreign keys use restrictive deletes for financial and customer records and cascading deletes only where ownership makes that safe.
- Monetary values use `NUMERIC`, never floating point.
- Timestamps use `TIMESTAMPTZ`.
- Flexible provider/integration metadata uses `JSONB`.
- Sensitive router and gateway configuration is stored as encrypted application ciphertext, not plaintext secrets.
- High-volume lookup paths have tenant-first indexes.

## Core domains

### Platform / tenancy

- `tenants` — ISP tenants and platform ownership boundary.
- `tenant_subscriptions` — SaaS subscription state for each tenant.

### Identity / authorization

- `users` — authenticated identities; `tenant_id` is nullable for platform-level identities.
- `roles` — global or tenant-scoped role definitions.
- `permissions` — granular permission catalog.
- `user_roles` — many-to-many user/role assignments.
- `role_permissions` — many-to-many role/permission assignments.

### ISP operations

- `resellers` — tenant-scoped reseller accounts.
- `customers` — tenant-scoped subscriber/customer records.
- `packages` — tenant-scoped service packages.
- `routers` — tenant-scoped MikroTik/RouterOS connection records.
- `customer_services` — PPPoE/Hotspot service subscriptions for customers.

### Billing / payments

- `invoices` — customer invoices.
- `invoice_items` — immutable invoice line-item history.
- `payments` — payment ledger and gateway transaction records.
- `payment_gateways` — tenant gateway configuration with encrypted config storage.

### Automation / observability

- `jobs` — database-backed queue for cPanel Cron workers.
- `notifications` — asynchronous notification delivery queue/history.
- `audit_logs` — security and business audit trail.

## Multi-tenant rule

For tenant-owned data, every read/write must scope by the authenticated tenant context. A URL parameter, form field, API body, or hidden input containing `tenant_id` is not an authorization source.

Repositories should prefer query shapes such as:

```sql
SELECT *
FROM customers
WHERE tenant_id = :tenant_id
  AND id = :id;
```

rather than querying by `id` alone.

## High-volume indexing

The initial schema includes composite indexes beginning with `tenant_id` for customers, services, invoices, payments, routers, packages, resellers, audit records, jobs, and subscription state. This is intentional for multi-tenant filtering and 100k+ customer workloads.

## Financial integrity

- Amounts are `NUMERIC(14,2)`.
- Invoice items are retained independently from packages so historical invoices do not change when a package price changes.
- Payments have a tenant-scoped reference and optional unique gateway transaction ID.
- Invoice/payment status transitions will be enforced by application services in a later phase.
- Financial records are not hard-deleted by normal business operations.

## Credentials

`routers.encrypted_password` and `payment_gateways.encrypted_config` are ciphertext fields. Encryption/decryption belongs in a dedicated application service and key material must come from environment configuration. The database schema must never contain plaintext router or payment credentials.

## Migration

The initial schema is implemented in:

`database/migrations/20260813_000001_create_core_schema.php`

It creates the tables and indexes listed above and can be rolled back as a single initial schema migration.

No real database has been provisioned or modified by this repository commit. The migration will run only against the configured PostgreSQL environment during the database deployment/migration phase.
