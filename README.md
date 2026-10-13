# Vision — Storefront + Commerce Admin

Vision is a Laravel 12 commerce application built with Blade, Eloquent, Vite, and a responsive product catalog. The reusable administration UI lives in `packages/RM/CommerceAdmin`.

## Current scope

- Public storefront: `/` and `/shop`, with catalog search, category/brand/price/sale filters, sorting, and pagination.
- Product cards with wishlist feedback, a client-side bag preview, and a visual fitting-room panel.
- Admin login and role-gated routes under `/admin`.
- Database-backed overview and analytics dashboards.
- Product list, create/edit form, product details, variant/SKU/barcode and stock management, and product image management.
- Order list, manual order creation, order details, payment status, and guarded fulfillment-status transitions.
- Customer-review moderation.
- A reusable Laravel module with configurable model adapters and its own views, CSS, JavaScript, routes, and migrations.

The fitting-room panel previews existing product imagery; it does not generate an AI try-on image. The client-side bag is not connected to a server-side checkout.

## Stack

- PHP `^8.2`, Laravel `^12.0`
- Blade, Vite, Tailwind CSS, and Eloquent
- Database migrations and PHPUnit tests

## Requirements

PHP 8.2+, Composer, Node.js/npm, and a Laravel-supported database.

## Local installation

```bash
git clone https://github.com/MREZA-MJDi/vision.git
cd vision
composer install
```

Copy `.env.example` to `.env` (`copy .env.example .env` in Windows CMD or `cp .env.example .env` on macOS/Linux). Create a local database and configure `DB_*` values.

```bash
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan storage:link
php artisan serve
```

Open `http://127.0.0.1:8000`. During frontend development, run `npm run dev` separately.

## Admin access

Create or choose a trusted user account first, then promote that existing user to administrator:

```bash
php artisan commerce-admin:make-admin admin@example.com
```

Sign in at `http://127.0.0.1:8000/admin/login`. The command does not create demo users or seed fake commerce records. Protect administrator credentials and never expose the promotion command to untrusted users.

## Verification

```bash
php artisan test
npm run build
```

The feature tests cover public storefront responses and core admin route protection/rendering. Review `docs/commerce-admin-kit.md` for the module's integration and reuse contract.

## Links

- Repository: https://github.com/MREZA-MJDi/vision
- Laravel documentation: https://laravel.com/docs/12.x
