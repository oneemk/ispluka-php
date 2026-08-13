# Customer Import Engine

Customer import is preview-first. Files are never imported directly into production records without validation.

## Sources
- Excel/CSV customer files
- MikroTik PPP/secret users

## Smart mapping
Headers can be English or Bangla and do not need a fixed order. The detector recognizes common aliases for name, mobile, PPPoE username, package/profile, address, NID, email, password and reseller. When headers are weak or absent, sample values can be used for limited inference.

## Safety flow
`Upload → Analyze → Detect fields → Map → Validate → Duplicate/Conflict detection → Preview → Confirm → Import → Incomplete queue`

Potential matches are never auto-merged. Existing customer records are not overwritten silently.

## Incomplete customers
MikroTik users with incomplete business data are imported into an incomplete queue. Each record can be completed individually and later bulk-completed by another validated import.

## Audit
Every batch should retain source, detected mapping, counts, conflicts, skipped rows and operator. Import must remain tenant-scoped and idempotent where possible.
