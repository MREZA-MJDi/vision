# Vision — Storefront

Vision is a Laravel 12 commerce storefront built with Blade, Eloquent, Vite, and a responsive product catalog.

## Current scope

- Public storefront routes: `/` and `/shop`
- Catalog browsing with search, category/brand/price/sale filters, sorting, and pagination
- Product cards with wishlist feedback and a client-side bag preview
- A visual fitting-room panel for selecting and previewing catalog pieces

**There is no admin dashboard or admin login in this build.** The previous admin routes, dashboard screens, management controllers, admin assets, and admin-account seeder have been removed. Existing database models, migrations, and commerce records are preserved. Catalog data must be provisioned through a separate trusted workflow until another management interface is introduced.

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

## Verification

```bash
php artisan test
npm run build
```

The feature tests check that the public storefront loads and that the removed `/admin` and `/login` endpoints remain unavailable.

## Links

- Repository: https://github.com/MREZA-MJDi/vision
- Laravel documentation: https://laravel.com/docs/12.x
