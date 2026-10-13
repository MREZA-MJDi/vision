# Commerce Admin Kit — Integration Contract

The admin module is kept under `packages/RM/CommerceAdmin` so its UI and workflows can be reused without copying controllers and Blade views into the host application's main namespaces.

## Included screens

- `/admin/login`: administrator sign-in, rate-limited and session-regenerated on success.
- `/admin`: live dashboard metrics, revenue series, recent orders, and recent products.
- `/admin/analytics`: revenue, payment, order, product, inventory, and review reporting.
- `/admin/products`: search, category/status filters, pagination, and optional columns.
- `/admin/products/create` and `/admin/products/{id}/edit`: product and variant management, SKU/barcode validation, stock, pricing, and image uploads.
- `/admin/products/{id}`: product detail and sales summary.
- `/admin/orders`, `/admin/orders/create`, and `/admin/orders/{id}`: order listing, manual creation, order details, and status/payment updates.
- `/admin/reviews`: moderation for product reviews.

All numbers and lists come from the configured Eloquent models; the module does not seed fake products or orders.

## Enable the module in another Laravel 12 application

1. Copy `packages/RM/CommerceAdmin` into the host application's `packages/RM/CommerceAdmin`.
2. Add the namespace mapping to the root `composer.json` under `autoload.psr-4`:
   ```json
   "Rm\\CommerceAdmin\\": "packages/RM/CommerceAdmin/src/"
   ```
3. Register `Rm\\CommerceAdmin\\CommerceAdminServiceProvider::class` in `bootstrap/providers.php`.
4. Add both package asset files as Vite inputs in `vite.config.js`:
   ```js
   'packages/RM/CommerceAdmin/resources/css/admin-kit.css',
   'packages/RM/CommerceAdmin/resources/js/admin-kit.js',
   ```
5. Run `composer dump-autoload`, configure the model adapters, run `php artisan migrate`, then `npm run build`.
6. Promote an existing trusted account with `php artisan commerce-admin:make-admin admin@example.com`. Sign in at `/admin/login`.

For this repository, the namespace, service provider, and Vite inputs are already registered.

## Model adapter contract

Set model class names through `config/commerce-admin.php`. The replacement models must preserve the names of the fields and relationships used by the controllers and views.

- **User:** authentication-compatible model, `email`, `name`, and a boolean role field selected by `admin_flag_column`.
- **Product:** `name`, `slug`, `is_active`, `category_id`, `brand_id`; relationships `category`, `brand`, `variants`, `galleryMedia`, `media`, and `approvedReviews`.
- **Product variant:** `product_id`, `sku`, `barcode`, `price`, `sale_price`, `stock`, `low_stock_threshold`, `is_active`, `sort_order`; relationships `product`, `primaryGalleryMedia`, and `galleryMedia`.
- **Order:** `order_number`, customer and shipping fields, `status`, `payment_status`, `subtotal`, `discount`, `shipping_cost`, `tax`, `total`, and fulfillment timestamps; relationships `user`, `items`, and `payments`.
- **Order item:** `order_id`, `product_id`, `product_variant_id`, `product_name`, `variant_name`, `sku`, `product_image`, `unit_price`, `quantity`, `line_total`, and `tax_amount`.
- **Media:** `mediable` polymorphic relationship and a `url` accessor.
- **Inventory movement:** supports `product_variant_id`, `type`, `quantity`, `stock_after`, `reference_type`, `reference_id`, `note`, and `created_by`.

The order workflow currently relies on the host application's `Order::STATUSES` and `Payment::STATUSES` constants. Keep those status contracts aligned if adapting the module to a different domain model.

## Operational notes

- Take a database backup before running schema changes on an existing store.
- The `is_hero` migration is safe to resume if its column was already created during an earlier interrupted/manual run.
- The admin guard checks the configured role flag on every protected request. Do not remove this middleware to make the screen visible.
- Product edits and order creation use validation and database transactions. Order creation checks available stock and records inventory movements; terminal order transitions restore reserved stock once.
- Images use Laravel's configured disk (default: `public`). Run `php artisan storage:link` when local public storage is used.
- After config or route changes, run `php artisan optimize:clear` while debugging, followed by the production build.
