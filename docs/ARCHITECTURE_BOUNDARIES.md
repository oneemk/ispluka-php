# ISPLUKA Architecture Boundaries

## Navigation
Admin Dashboard uses these primary business areas:
- Dashboard
- Customers
- Resellers
- Packages
- Billing
- Payments
- Reports
- Networking
- Hotspot Panel
- Inventory
- POS
- BTRC
- Audit Logs
- Settings

## Networking
`Networking` is infrastructure-centric and contains:

### MikroTik
- Routers
- Router dashboard/status
- Interfaces and traffic
- PPPoE infrastructure
- Hotspot infrastructure
- Active connections
- DHCP/IP pools
- Firewall/NAT
- Queues
- Logs

### OLT
- OLT devices
- OLT dashboard
- PON ports
- ONU/ONT inventory and status
- Optical power
- LOS
- ONU discovery
- Provisioning
- Vendor adapters
- Logs

Networking must not become dependent on billing business rules.

## Separate Hotspot Panel
Hotspot management has a dedicated UI and navigation entry. It is designed as a modern Mikhmon-v3-style operational panel for MikroTik Hotspot management.

Hotspot features include:
- Router connection
- Hotspot servers
- Hotspot users
- User profiles
- Active sessions
- Disconnect/kick
- Enable/disable users
- Password management
- MAC/IP bindings
- Hosts
- Login/session history
- Traffic and uptime
- Walled garden
- Address lists
- Hotspot server/profile management

### Hotspot isolation rule
Hotspot users, profiles, sessions and operational records must NOT have foreign-key/business relationships to:
- PPPoE packages
- PPPoE customer services
- PPPoE invoices
- Monthly billing
- Billing auto-suspend/restore
- Reseller billing

The same physical MikroTik router may be used by both PPPoE and Hotspot subsystems, but their business logic remains isolated.

## PPPoE
PPPoE remains part of the ISP customer/billing domain:
- Customer
- Package
- PPPoE account/service
- Invoice
- Payment
- Auto suspend/restore

## OLT independence
OLT management is separate from MikroTik management. Vendor-specific adapters may support Huawei, ZTE, FiberHome, VSOL, BDCOM and other supported vendors without coupling OLT operations to billing.

## Data boundary
Infrastructure entities are separate from business entities. Integration happens through explicit services/adapters/events rather than direct business coupling.

## UI rule
All screens must be mobile-friendly. User-facing billing terminology must use **Extra Time**, never “Grace Period”.
