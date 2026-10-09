# Vision — Administration Platform

Vision is a Laravel application focused on the administration side of a commerce system. Its current routes include admin dashboard, brands, categories, customers, financial operations, inventory, media, products, product variants, orders, and site content. This repository is a separate project from `psychic-spork`; its README describes only the code present here, not features that may exist in another repository.

## Stack
- PHP `^8.2`, Laravel `^12.0`
- Blade, Vite and Eloquent
- Database migrations and seeders
- PHPUnit tests

## Requirements
PHP 8.2+, Composer, Node.js/npm, and a Laravel-supported database.

## Local installation
```bash
git clone https://github.com/MREZA-MJDi/vision.git
cd vision
composer install
```

Copy `.env.example` to `.env` (`copy .env.example .env` in Windows CMD; `cp .env.example .env` on macOS/Linux). Create a local database and configure `DB_*` values.

```bash
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan storage:link
php artisan serve
```

Open `http://127.0.0.1:8000`. During frontend development, run `npm run dev` separately.

## Tests
```bash
php artisan test
```

## Scope and safety
The current route definitions do not include the cheque and wholesale controllers present in `psychic-spork`. Do not assume that business rules, migrations, or tests from that separate project exist here. Review authorization and financial/inventory transitions before deployment, and never reset a database containing needed data.

## Links
- Repository: https://github.com/MREZA-MJDi/vision
- Laravel documentation: https://laravel.com/docs/12.x
