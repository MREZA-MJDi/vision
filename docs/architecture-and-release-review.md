# VISION architecture and release review

## Active request flow

- `routes/web.php` defines public catalog, product detail, cart, checkout, account, and authentication routes.
- `routes/admin.php` groups commerce administration behind the configured administrator middleware.
- `StorefrontController` supplies the homepage, catalog, and product-detail data.
- Controllers and Form Requests validate input; shared business behavior for order state, payment, accounting, media, and homepage hero belongs in application services.
- Eloquent models and migrations define products, variants, carts, orders, payment records, inventory movements, and ledger entries.
- `resources/views/layouts/store.blade.php` is the canonical Persian/RTL storefront layout. Active views are `store/home.blade.php`, `store/index.blade.php`, and `store/products/show.blade.php`.
- `packages/RM/CommerceAdmin` is a presentation package. Application routes, authorization, models, schema, and business rules remain owned by the Laravel application.

## Tiamir identity intro

- The matte-black CRT/static intro is rendered only on the public home route; catalog, product, cart, checkout, account, and admin flows are not blocked.
- First visit runs the full 5-second sequence. After completion, a first-party cookie and session storage record are written; later visits run for 2.5 seconds.
- The intro reveals the page already underneath it; it does not navigate users to the Tiamir website. Returning visitors can dismiss it with a double-click/double-tap or Escape/Enter. The first-visit sequence cannot be skipped.
- The timer pauses while the browser tab is hidden. Canvas noise is resolution-capped and frame-limited, then stops on exit. Reduced-motion preferences suppress moving noise and glitch layers.
- Only the homepage preloads the Tiamir logo. If the remote image fails, a simple wordmark fallback appears. If JavaScript does not initialize, the intro stays hidden so the storefront remains usable.

## Quality-hardening changes

- Removed unreferenced starter/legacy views (`resources/views/welcome.blade.php`, `resources/views/store/layout.blade.php`, and `resources/views/store/products/index.blade.php`). Current routes render the active views listed above.
- Added escaped Open Graph/Twitter metadata to the shared layout and product-specific sharing metadata.
- Reworked the mobile campaign hero for a readable two-column information layout, touch-friendly controls, and reduced-motion preferences while keeping desktop styles unchanged.
- CI smoke-tests the storefront on PHP 8.2, runs the full suite on PHP 8.3, and installs frontend dependencies from the lockfile using `npm ci`.
- The default `DatabaseSeeder` creates editable launch-catalog Eloquent records, not the opt-in demo catalog. A media row is created only when its file really exists on the configured public disk; missing files must not generate broken image URLs.
- Dashboard hardening verifies CRUD persistence, variant-specific media, deletion flows, validation, query bounds, and idempotent seed behavior.

## Release risks to resolve

1. **Money units:** the sample currency code is `IRR` while the visible label is `تومان`. The application does not convert amounts when either setting changes. Agree on the unit for existing data and every checkout/accounting integration before production.
2. **Payment scope:** online card processing and automated bank-transfer reconciliation are not implemented. A manual admin payment update is not proof of provider settlement.
3. **Migrations:** review pending and historical migrations against a current backup of each environment before deployment, especially migrations that remove columns.
4. **Responsive QA:** inspect the homepage at 320, 360, 390, 430, 768, and desktop widths. Also test product variant changes, cart, checkout validation, filter drawers, and keyboard focus in real browsers; CSS assertions cannot replace visual interaction tests.
5. **Operations:** set production environment flags, disable debug output, configure HTTPS/session cookies, verify backups, build/cache assets, publish storage links, and confirm payment reconciliation rules before release.

## Suggested verification

```bash
php artisan test
npm ci
npm run build
```

Run these on a clean checkout with supported PHP and Node versions. This review does not replace production data-migration review, provider integration tests, or real-device visual QA.
