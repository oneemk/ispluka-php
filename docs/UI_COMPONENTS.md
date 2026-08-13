# ISPLUKA UI Contract

All new admin/customer/reseller/employee interfaces must use the shared responsive CSS classes and mobile navigation contract.

## Layout
- `.app-header`, `.sidebar`, `.main`, `.container`
- `.grid`, `.grid-2`, `.grid-3`, `.grid-4`
- `.card`

## Forms
- `.form-grid`, `.field`, `.actions`
- Controls must remain at least 44px high on touch devices.

## Tables
Wrap tables in `.table-wrap`; tables may scroll horizontally on small screens instead of breaking the page layout.

## Mobile navigation
Use `[data-menu-toggle]` and `[data-sidebar]` for the responsive sidebar and `.bottom-nav` for the mobile navigation bar.

## Accessibility
Use semantic headings, labels, buttons, visible focus states, `aria-expanded` for toggles, and do not rely on color alone for status.
