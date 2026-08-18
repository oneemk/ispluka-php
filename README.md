### Read docs/AI_PROJECT_CONTEXT.md before making any changes ###
### Also Read docs all file before making any changes ###


Ispluka PHP — AI Project Context & Architecture Rules
  Project Identity
    Project name: Ispluka PHP
    Repository: ispluka-php
    Production domain: https://ispluka.online
    This project is an ISP Billing & Management ERP designed for ISP operators.
    This repository is the current source of truth for the PHP/cPanel edition.

Current Deployment Architecture
  The project is hosted on:
    Namecheap hosting
    cPanel
    LiteSpeed
    PHP 8.3
    PostgreSQL

Current project location on the server: /home/isplzepc/repositories/ispluka-php
Main project public directory: /home/isplzepc/repositories/ispluka-php/public
Current cPanel public document root: /home/isplzepc/public_html

# ISPLUKA PHP
Production-ready multi-tenant ISP Billing & Management ERP SaaS.

This project is designed specifically for shared hosting. VPS, Docker, Node.js, Redis, BullMQ, Nginx, PM2, Supervisor, and other long-running infrastructure are not required.

## Technology contract

- Custom PHP MVC architecture
- Composer dependency management
- PostgreSQL accessed through PDO and prepared statements
- Server-rendered PHP views with HTML/CSS/JavaScript
- Secure PHP sessions for web authentication
- RBAC and strict multi-tenant isolation
- REST API for approved integrations
- Database-backed jobs executed by cPanel Cron

## Roles

- Master Admin
- Admin
- Reseller
- Employee
- Customer

## Planned modules

- Tenant and ISP management
- Customer management
- Package management
- Billing and invoice
- Payments
- bKash/Nagad/Card gateway architecture
- MikroTik integration
- PPPoE
- Hotspot
- Router management
- Automatic suspension and restoration
- Reports
- Notifications
- Audit and security logs
- REST API
- Settings

These modules are planned and are not implemented in this baseline.

## Architecture contract

The application will follow:

Controller -> Service -> Repository -> PDO -> PostgreSQL

Rules:

- Controllers must not contain SQL.
- Views must not perform database queries.
- Business logic belongs in Services.
- Database access belongs in Repositories.
- PDO prepared statements are mandatory.
- Tenant-owned data must always be tenant-scoped.
- tenant_id supplied by a client request must never be trusted for authorization.
- The authenticated security context determines the active tenant.
- Secrets must never be hardcoded.
- Production errors must not expose stack traces, credentials, or sensitive data.

## Multi-tenancy contract

Master Admin controls the SaaS platform. Each Admin represents an ISP tenant. Tenant-owned records must be isolated from every other tenant.

Master Admin
- Tenant / ISP A
  - Admin
  - Employees
  - Resellers
  - Customers
- Tenant / ISP B
  - Admin
  - Employees
  - Resellers
  - Customers

## Security contract

Planned security requirements include:

- password_hash() and password_verify()
- secure PHP sessions
- session regeneration
- CSRF protection
- context-aware XSS output escaping
- PDO prepared statements
- strict RBAC
- tenant isolation
- encryption for sensitive stored credentials where appropriate
- secure cookie attributes
- login throttling
- audit logging
- production-safe error handling
- .env-based secret management

## Development phases

1. Architecture and specification freeze
2. Core application bootstrap and custom MVC foundation
3. Database architecture and migration/seeding engine
4. Authentication and session security
5. RBAC and multi-tenancy
6. Master Admin
7. ISP Admin, Employee, and Reseller
8. Customer management
9. Packages
10. Billing and invoices
11. Payments and gateway adapters
12. MikroTik, PPPoE, and Hotspot
13. Cron automation
14. Reports and notifications
15. REST API
16. Audit/security hardening
17. Testing
18. cPanel production deployment

## Git workflow

The canonical repository is `oneemk/ispluka-php`.

`main` is the production branch. Development will proceed in small, reviewable steps. Secrets and runtime data must never be committed.

## Configuration

Copy `.env.example` to `.env` in local or production environments and provide environment-specific values. Never commit `.env` or real credentials.

## Deployment target

The production deployment target is Namecheap cPanel Shared Hosting with Apache, PHP 8.3+, PostgreSQL, SSL, and cPanel Cron. The application must remain compatible with these constraints throughout development.

## Database foundation

The database layer uses native PHP PDO with the PostgreSQL driver. Connections use exception mode, associative fetches, native prepared statements, and configurable PostgreSQL SSL mode. Transactions are explicit and rollback automatically when a callback fails.

Migrations implement an explicit `up()`/`down()` contract and are tracked in the `schema_migrations` table. Seeders use an explicit runner contract. No application business tables have been created yet; those belong to the schema design phase.

## Status

Core application bootstrap and the PostgreSQL database foundation are implemented. Business features and the production database schema are not implemented yet.
