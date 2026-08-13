# ISPLUKA Mobile UI Contract

The ERP uses a mobile-first responsive design system for phones, tablets and desktop screens.

## Rules

- Mobile breakpoint: below 768px.
- Desktop navigation becomes a slide-out sidebar on mobile.
- Primary mobile navigation uses a fixed bottom navigation bar.
- Touch targets are at least 44px high.
- Forms use full-width controls on small screens.
- Data tables remain readable through horizontal scrolling instead of shrinking columns into unusable text.
- Dashboard cards collapse from four columns to one on phones.
- `viewport-fit=cover` is used for modern mobile safe areas.
- Reduced-motion users are respected.
- No UI framework dependency is required; the CSS is lightweight and cPanel/shared-hosting friendly.
- The service worker only caches static UI assets and must never cache authenticated API responses.

## Assets

- `/assets/css/app.css`
- `/assets/js/app.js`
- `/manifest.json`
- `/sw.js`

All future web views should use the same layout classes and must not introduce fixed desktop-only widths.