# VISION architecture and release review

## Active request flow

- `routes/web.php` defines public catalog, product detail, cart, checkout, account, and authentication routes.
- `routes/admin.php` groups commerce administration under the admin middleware.
- `StorefrontController` supplies homepage, catalog, and product-detail data.
- Controllers and Form Requests validate requests; `app/Services` owns shared order-state, payment, accounting, media, and hero behavior.
- Eloquent models and migrations define products, variants, carts, orders, payment records, inventory movements, and ledger entries.
- `resources/views/layouts/store.blade.php` is the active Persian/RTL storefront layout. The primary public views are `store/home.blade.php`, `store/index.blade.php`, and `store/products/show.blade.php`.
- `packages/RM/CommerceAdmin` is a presentation package. Application routes, authorization, models, schema, and business rules remain owned by the Laravel app.

## Quality-hardening changes

- Removed unreferenced starter/legacy views (`resources/views/welcome.blade.php`, `resources/views/store/layout.blade.php`, and `resources/views/store/products/index.blade.php`). Current routes render the active views listed above.
- Added Open Graph and Twitter metadata to the active layout with product-specific sharing metadata.
- Escaped dynamic values emitted into metadata attributes.
- Made missing Vite assets visible outside the testing environment instead of silently rendering a production page without CSS/JS. Automated tests still bypass Vite manifest loading.
- Reworked the mobile campaign hero for a readable two-column information layout, larger key controls, and reduced-motion preferences; desktop styles remain unchanged.
- Expanded storefront assertions to cover social metadata output.
- CI now smoke-tests with PHP 8.2, runs the full suite on PHP 8.3, and installs Node dependencies using `npm ci`.

## Release risks to resolve

1. **Money units:** the sample currency code is `IRR` while the visible label is `تومان`. The application does not convert amounts when either setting changes. Agree the unit for existing data and every checkout/accounting integration before production.
2. **Payment scope:** online card processing and automated bank-transfer reconciliation are not implemented; manual payment updates are not proof of a provider settlement.
3. **Migrations:** review pending and historical migrations against a current backup of each environment before deployment, especially changes that remove columns.
4. **Responsive QA:** validate the homepage and product/checkout forms in real browsers at narrow mobile, tablet, and desktop widths. CSS assertions cannot replace visual interaction testing.
5. **Operations:** set `APP_ENV=production`, `APP_DEBUG=false`, HTTPS/session-cookie settings, reliable backups, and production cache/build/storage steps before going live.

## Suggested verification

```bash
php artisan test
npm ci
npm run build
```

Run these commands on a clean checkout with supported PHP and Node versions. This branch is not a substitute for production data-migration review, provider integration tests, or real-device visual QA.
