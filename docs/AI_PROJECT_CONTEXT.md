
# Ispluka PHP — AI Project Context & Architecture Rules

## 1. Project Identity

Project name: **Ispluka PHP**

Repository: `ispluka-php`

Production domain:

`https://ispluka.online`

This project is an **ISP Billing & Management ERP** designed for ISP operators.

This repository is the current source of truth for the PHP/cPanel edition.

---

# 2. CRITICAL AI RULE

Before making ANY change to this project:

1. Read this document.
2. Inspect the existing repository structure.
3. Inspect the relevant existing PHP classes/routes/views/assets.
4. Preserve the current architecture.
5. Do NOT introduce the previous Next.js/Node.js architecture.
6. Do NOT migrate the project to Laravel unless explicitly requested.
7. Do NOT replace the custom PHP architecture with another framework.
8. Do NOT delete existing features merely to implement a new feature.
9. Do NOT make assumptions about database schema when the repository already contains the schema/migrations.
10. Prefer small, compatible changes over large rewrites.

The goal is to **extend the existing Ispluka PHP application**, not rebuild it from scratch.

---

# 3. Current Deployment Architecture

The project is hosted on:

* Namecheap hosting
* cPanel
* LiteSpeed
* PHP 8.3
* PostgreSQL

Current project location on the server:

`/home/isplzepc/repositories/ispluka-php`

Main project public directory:

`/home/isplzepc/repositories/ispluka-php/public`

Current cPanel public document root:

`/home/isplzepc/public_html`

---

# 4. public_html Architecture

The production `public_html/index.php` is intentionally a thin loader.

Current concept:

```php
<?php

declare(strict_types=1);

require '/home/isplzepc/repositories/ispluka-php/public/index.php';
```

Therefore:

**Do NOT move the entire project into public_html.**

The application source remains inside:

`/home/isplzepc/repositories/ispluka-php`

Only the public entry point is exposed through:

`/home/isplzepc/public_html`

This separation must be preserved for security and maintainability.

---

# 5. Project Public Entry Point

The real application entry point is:

`repositories/ispluka-php/public/index.php`

It performs two important jobs:

### Static assets

It can directly serve public static files such as:

* CSS
* JavaScript
* JSON
* SVG
* PNG
* JPG/JPEG
* GIF
* WEBP
* ICO
* WOFF
* WOFF2
* TTF

Then it loads:

```php
bootstrap/app.php
```

and runs the application.

Conceptual flow:

```text
Browser
   ↓
https://ispluka.online
   ↓
/home/isplzepc/public_html/index.php
   ↓
/home/isplzepc/repositories/ispluka-php/public/index.php
   ↓
bootstrap/app.php
   ↓
Application
   ↓
Router
   ↓
Middleware
   ↓
Controller
   ↓
Service / Repository
   ↓
PostgreSQL
```

---

# 6. Core Project Structure

Important directories:

```text
ispluka-php/
├── app/
├── bootstrap/
├── config/
├── database/
├── docs/
├── public/
├── resources/
├── routes/
├── storage/
├── vendor/
├── composer.json
├── composer.lock
├── .env
├── .env.example
├── .htaccess
└── README.md
```

---

# 7. Application Architecture

The application uses a custom PHP architecture.

Major layers:

```text
HTTP Request
    ↓
public/index.php
    ↓
bootstrap/app.php
    ↓
Application
    ↓
Router
    ↓
Middleware
    ↓
Controller
    ↓
Service
    ↓
Repository
    ↓
Database
```

The project already has concepts/classes for:

* Application
* Router
* Request
* Response
* Authentication
* Authorization
* Roles
* Sessions
* CSRF
* Encryption
* SecretBox
* Database
* Services
* Repositories
* Controllers
* Middleware
* Network/MikroTik integration

Preserve these patterns.

---

# 8. Authentication & Authorization

The application has:

* Login
* Signup
* Logout
* Session management
* Authentication
* Authorization
* RBAC
* Permission-based middleware
* CSRF protection
* Encryption

Important roles/permissions must remain compatible with the existing implementation.

Do not bypass:

```text
Authentication
Authorization
CSRF
Tenant isolation
Permission checks
```

when adding new routes or features.

---

# 9. Current Main Roles

The intended business role structure is:

```text
Master Admin
Admin
Reseller
Employee
Customer
```

There should NOT be a separate Franchise role unless explicitly requested.

Master Admin owns and controls the SaaS/platform layer.

Admin represents an ISP tenant.

Reseller manages assigned customers/services according to permissions.

Employee works within the tenant.

Customer accesses their own allowed information.

---

# 10. Dashboard

The dashboard is the main operational control center.

Current dashboard backend:

```text
app/Controllers/DashboardController.php
app/Core/Dashboard/DashboardService.php
resources/views/dashboard.php
public/assets/css/dashboard.css
public/assets/css/app.css
```

Dashboard route:

```text
GET /
```

The dashboard must remain the primary landing page after authentication.

---

# 11. Dashboard Data

DashboardService currently provides summary information including:

```text
customers
active_services
suspended_services
overdue_invoices
outstanding
monthly_collected
today_collected
new_customers_today
routers_total
routers_online
routers_offline
```

It also provides:

```text
recentPayments
recentCustomers
collectionTrend
```

These existing metrics must not be removed.

---

# 12. Dashboard Recommended Business Order

The dashboard should be organized in this operational order:

## Section 1 — Overview

Show:

* Total Customers
* Active Services
* Suspended Services
* Overdue Invoices
* Outstanding Amount
* Today's Collection
* Monthly Collection
* New Customers Today

Use modern cards with clean typography.

Avoid excessive icons.

---

## Section 2 — Network Status

Show:

* Total MikroTik Routers
* Online Routers
* Offline Routers
* Network health/status
* Important connectivity alerts

The network section should be visually prominent but clean.

---

## Section 3 — Collection

Show:

* Today's collection
* Monthly collection
* Outstanding
* Overdue invoices
* Collection trend

Include a clean chart for collection history.

---

## Section 4 — Recent Payments

Display:

* Customer
* Customer code
* Amount
* Payment method
* Reference
* Date/time

---

## Section 5 — Recent Customers

Display:

* Customer code
* Customer name
* Phone
* Status
* Created date

---

## Section 6 — Quick Actions

Important actions may include:

* Add Customer
* Collection
* Customers
* MikroTik Routers
* Customer Networking
* Enforcement Audit
* Subscription

Buttons should be modern and visually consistent.

---

# 13. Dashboard UI Requirements

The dashboard should look like a modern SaaS/ISP ERP.

Requirements:

* Responsive
* Mobile friendly
* Desktop friendly
* Clean spacing
* Modern cards
* Modern tables
* Modern buttons
* Good typography
* Clear hierarchy
* Minimal visual clutter
* Professional ISP ERP appearance

Do NOT add icons beside every menu item.

Icons should only be used where they genuinely improve usability.

Buttons should have:

* clear hierarchy
* consistent border radius
* hover state
* active state
* disabled state where required
* appropriate spacing
* accessible contrast

---

# 14. Language Requirement

The application must support:

```text
বাংলা
English
```

The user must be able to switch language from the UI.

Language selection must actually change visible interface text.

A language selector that only changes a visual value without translating the UI is NOT acceptable.

The language system should be designed so future translations can be expanded without rewriting every page.

Prefer a centralized translation structure rather than scattering language logic throughout controllers.

Do not break existing routes or authentication while implementing language support.

---

# 15. Main Application Modules

The project currently contains functionality around:

## Authentication

* Login
* Signup
* Logout
* Session
* Authorization
* Roles
* Permissions
* CSRF

## Dashboard

* Operational overview
* Customer statistics
* Service statistics
* Billing statistics
* Collection statistics
* Router/network status
* Recent payments
* Recent customers
* Collection trend

## Customer Management

* Customer listing/search
* Customer details
* Customer creation
* Customer update
* Customer deletion
* Customer status
* Customer services

## Billing & Collection

* Collection
* Invoice information
* Payment collection
* Payment records
* Payment reference
* Payment method
* Outstanding amount
* Overdue invoices
* Collection reports

## Customer Services

* Customer services
* Service status
* Active services
* Suspended services
* Service management

## MikroTik Networking

* MikroTik router management
* Router listing
* Router creation
* Router update
* Router deletion
* Router connection testing
* Router status
* PPPoE active users
* PPPoE disconnect
* MikroTik API
* MikroTik SSH
* RouterOS integration
* Network automation

## MikroTik Enforcement

* Enforcement audit
* Reconciliation
* Live PPPoE information
* Usage history
* Manual PPPoE actions
* Enforcement audit summary

## Customer Networking

The application contains a Customer Networking interface allowing operational network information to be viewed for a customer.

Existing route:

```text
/networking/customer
```

It includes concepts such as:

* Router ID
* PPPoE username
* Live Rx/Tx
* Usage history
* Router access

Do not remove this functionality.

## Subscription

Existing subscription functionality must remain.

Route:

```text
/subscription
```

Master Admin functionality also includes tenant/subscription administration.

---

# 16. Current Important Routes

Existing routes include:

```text
/login
/signup
/
/admin
/admin/tenants
/admin/subscriptions
/collection
/reports/collection
/customers/create
/customers
/subscription
/logout
```

MikroTik routes:

```text
/networking/mikrotik/routers
/networking/customer
/networking/mikrotik/enforcement-audit
```

API routes include customer, collection, service, MikroTik and enforcement endpoints.

Do not change route URLs casually because existing frontend links and production usage may depend on them.

---

# 17. MikroTik Router Module

Important routes:

```text
/networking/mikrotik/routers

/api/networking/mikrotik/routers
/api/networking/mikrotik/routers/status
/api/networking/mikrotik/pppoe/active
/api/networking/mikrotik/pppoe/disconnect
/api/networking/mikrotik/routers
/api/networking/mikrotik/routers/update
/api/networking/mikrotik/routers/test
/api/networking/mikrotik/routers/delete
```

The system supports both:

```text
RouterOS API
RouterOS SSH
```

There is a connection client abstraction so network integration should remain modular.

---

# 18. MikroTik Refresh Requirement

The MikroTik router page previously had a countdown/auto-refresh UI.

NEW REQUIREMENT:

**Remove the countdown button/indicator.**

Do not show:

```text
1s
0s
countdown
automatic countdown refresh
```

Instead provide a clean:

```text
Refresh
```

or:

```text
Refresh Status
```

button.

The user should manually refresh router status when needed.

Do not introduce an automatic countdown again unless explicitly requested.

---

# 19. Database

Database layer is PostgreSQL.

Database access is through the project's existing custom database abstraction.

Important existing database concepts include:

* tenants
* customers
* customer services
* invoices
* payments
* routers
* subscriptions
* roles
* permissions
* migration tracking

Do not directly rewrite database architecture.

Do not delete migrations.

Do not change production schema without checking existing migrations and current database state.

---

# 20. Migration Safety

There have previously been migration failures in this project.

Known historical problems include:

```text
MigrationInterfaceInterfaceInterface
```

and:

```text
routers_status_check
```

constraint failures.

Example historical issue:

```text
new row for relation "routers"
violates check constraint "routers_status_check"
```

Therefore:

### DO NOT

* blindly rerun every migration
* delete migration files
* modify historical migrations unnecessarily
* drop production tables
* drop constraints without checking existing data
* reset the production database
* assume the database is empty

Before modifying migrations:

1. Inspect migration files.
2. Inspect MigrationRunner.
3. Check migration history.
4. Check current PostgreSQL schema.
5. Check existing production data.
6. Create a forward-compatible migration.

---

# 21. Existing PHP Warning

There have been harmless PHP warnings such as:

```text
The use statement with non-compound name 'PDO' has no effect
```

Do not treat this warning alone as a production failure.

The real application must be tested separately.

---

# 22. Production Hosting Architecture

The project is deployed under cPanel/Namecheap.

Important paths:

```text
/home/isplzepc/
├── public_html/
│   └── index.php
│
└── repositories/
    └── ispluka-php/
        ├── app/
        ├── bootstrap/
        ├── config/
        ├── database/
        ├── public/
        ├── resources/
        ├── routes/
        ├── storage/
        ├── vendor/
        └── ...
```

The production request enters through:

```text
public_html/index.php
```

which loads:

```text
repositories/ispluka-php/public/index.php
```

Do not expose:

```text
app/
config/
database/
storage/
vendor/
.env
```

directly to the public web.

---

# 23. public_html Must Remain Minimal

`public_html` should NOT become a second copy of the entire application.

Avoid copying:

```text
app
bootstrap
config
database
resources
routes
storage
vendor
.env
```

into public_html.

The source application remains under:

```text
repositories/ispluka-php
```

---

# 24. .env

Production environment configuration is stored in:

```text
/home/isplzepc/repositories/ispluka-php/.env
```

Never commit production secrets.

Never put:

* database password
* APP_KEY
* encryption keys
* API credentials
* MikroTik passwords
* payment credentials

into source code.

Use `.env.example` for documentation.

---

# 25. Static Assets

Public assets are stored under:

```text
public/assets/
```

Examples include:

```text
public/assets/css/app.css
public/assets/css/dashboard.css
public/assets/js/
```

When modifying frontend JavaScript/CSS:

1. Check the existing file.
2. Preserve existing behavior.
3. Avoid duplicate implementations.
4. Do not create unnecessary new frameworks.
5. Test production URL after modification.

---

# 26. Git Rules

The Git repository is inside:

```text
/home/isplzepc/repositories/ispluka-php/.git
```

Git commands must be executed from:

```text
/home/isplzepc/repositories/ispluka-php
```

Do not assume `/home/isplzepc/ispluka-php` is the Git repository.

Before changing files:

```bash
cd /home/isplzepc/repositories/ispluka-php
git status
git branch --show-current
```

Always inspect the working tree before committing.

Do not commit backup files such as:

```text
*.bak
```

unless explicitly requested.

---

# 27. Deployment Rule

The preferred deployment flow is:

```text
Local/AI changes
      ↓
GitHub
      ↓
Server repository
      ↓
Composer / database migration if required
      ↓
Production
```

Do not manually overwrite random production files without checking Git status.

After deployment:

```text
curl https://ispluka.online/login
```

should return a successful response.

Authenticated routes may correctly return:

```text
302
```

when the user is not logged in.

---

# 28. Existing Production Health Behavior

Expected behavior:

```text
/login                         → 200
/networking/mikrotik/routers  → 302 when unauthenticated
/customers                    → 302 when unauthenticated
/subscription                 → 302 when unauthenticated
```

The root dashboard `/` may return `404` when unauthenticated because it is protected by authentication middleware.

This should not automatically be treated as a routing failure.

---

# 29. AI Development Workflow

Before modifying a feature:

### Step 1

Find the route.

### Step 2

Find the controller.

### Step 3

Find the service.

### Step 4

Find repository/database access.

### Step 5

Find the view.

### Step 6

Find related JavaScript/CSS.

### Step 7

Modify the smallest necessary set of files.

### Step 8

Run PHP syntax checks.

### Step 9

Test the relevant URL/API.

### Step 10

Review Git diff.

### Step 11

Commit with a clear message.

---

# 30. PHP Quality Rules

Use:

```text
declare(strict_types=1);
```

where consistent with the existing project.

Preserve:

* namespaces
* type declarations
* dependency injection
* existing interfaces
* existing abstractions

Do not introduce unnecessary global functions.

Do not put database queries directly into views.

Do not put business logic directly into HTML/JavaScript.

---

# 31. Security Rules

Always preserve:

* authentication
* authorization
* tenant isolation
* CSRF
* encrypted credentials
* secure sessions
* prepared SQL statements
* input validation
* output escaping
* public/private filesystem separation

Never expose router passwords or encrypted secrets to frontend JavaScript unnecessarily.

---

# 32. UI/UX Rules

The interface should feel like a professional modern ISP ERP/SaaS.

Preferred style:

```text
Modern
Clean
Fast
Professional
Responsive
Minimal
Operational
```

Avoid:

```text
Excessive gradients
Excessive icons
Crowded cards
Huge buttons
Unnecessary animations
Cluttered menus
Old-fashioned admin-panel styling
```

Menu icons are optional.

Do NOT automatically add an icon beside every menu.

Buttons should be visually polished and consistent.

---

# 33. Language UI

Primary supported languages:

```text
বাংলা
English
```

The language selector should be visible in the application shell/header.

Language selection should persist where practical.

All major dashboard/menu labels should support both languages.

Example:

```text
Dashboard / ড্যাশবোর্ড
Customers / গ্রাহক
Collection / আদায়
Routers / রাউটার
Reports / রিপোর্ট
Subscription / সাবস্ক্রিপশন
Settings / সেটিংস
```

Do not implement language switching as a fake UI control.

---

# 34. What AI Must NOT Do

Without explicit user permission, AI must NOT:

* convert PHP to Laravel
* convert PHP to Node.js
* convert PHP to Next.js
* introduce React as the primary frontend
* introduce Docker as a production requirement
* replace PostgreSQL
* replace the custom router
* delete existing modules
* delete existing migrations
* reset production database
* move the application into public_html
* expose `.env`
* remove authentication
* remove RBAC
* remove CSRF
* remove MikroTik API/SSH support
* remove billing/collection functionality
* remove customer functionality
* remove subscription functionality
* replace existing architecture wholesale

---

# 35. What AI SHOULD Do

AI should:

* inspect before changing
* reuse existing classes
* reuse existing routes
* reuse existing services
* reuse existing repositories
* reuse existing CSS conventions
* reuse existing JS patterns
* create small migrations
* preserve backward compatibility
* keep production hosting compatible
* keep cPanel/Namecheap deployment simple
* make UI improvements without architecture changes
* improve mobile usability
* improve dashboard readability
* add Bengali/English properly
* improve MikroTik management safely
* preserve tenant isolation

---

# 36. Current Business Goal

Ispluka PHP is intended to become a practical ISP ERP capable of managing:

```text
ISP tenants
Customers
Resellers
Employees
Customer services
Invoices
Payments
Collections
Outstanding balances
Subscriptions
MikroTik routers
PPPoE users
Network status
Network enforcement
Usage information
Reports
Audit/reconciliation
Automation (Billing, Mikrotik Router, OLT, Customer)
```

The system should remain scalable and maintainable as the feature set grows.

---

# 37. Final AI Instruction

When working on Ispluka PHP:

> **Extend the existing architecture. Do not replace it.**

Always assume that existing code may contain production business logic.

Before deleting, renaming, moving, or replacing any file:

```text
Inspect it.
Find its dependencies.
Find its routes.
Find its consumers.
Check Git diff.
Then modify it.
```

The safest implementation is normally the smallest compatible implementation.

If a requested feature conflicts with this architecture, stop and explain the conflict before making destructive changes.

**This document is the primary AI context for the Ispluka PHP project.**
