# ISPLUKA Module Map

| Area | Responsibility | Billing relation |
|---|---|---|
| Customers | ISP customer accounts | Yes |
| Resellers | Reseller accounts/ledger | Yes |
| Packages | PPPoE service packages | Yes |
| Billing | Invoice/payment lifecycle | Yes |
| Payments | Gateway/payment settlement | Yes |
| Networking > MikroTik | Router infrastructure | No direct billing dependency |
| Networking > OLT | OLT/ONU infrastructure | No direct billing dependency |
| Hotspot Panel | Mikhmon-v3-style MikroTik Hotspot operations | **No PPPoE/invoice/package relation** |
| Reports | Business/operational reports | Read-only aggregation |
| BTRC | Regulatory report data | Reporting only |
| Inventory | Stock/items | Optional POS reference |
| POS | Retail sales | Optional customer/invoice reference |
| Audit Logs | Security/operations trail | Cross-module logging |
| Settings | Tenant/platform configuration | Configuration only |

## Hotspot data boundary
`hotspot_*` tables must remain independent of `invoices`, `packages`, `customer_services`, and PPPoE account tables.

## Networking data boundary
`mikrotik_*` and `olt_*` infrastructure data is owned by Networking. Customer/billing services may request explicit provisioning actions through service boundaries, but infrastructure screens remain independently usable.
