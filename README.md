# VISION — Commerce Storefront

VISION is a Laravel 12 storefront backed by Eloquent, Blade and Vite. The reusable Commerce Admin UI kit contains Blade views and static CSS/JavaScript only; this application owns routes, controllers, authorization, configuration and database migrations.

## Customer routes

- \`/\` and \`/shop\` — product discovery with search, category/brand filters, sorting and pagination.
- \`/products/{slug}\` — active product detail with selectable variants, current server-backed price and stock.
- \`/cart\` — database-backed bag for guest sessions and signed-in customers.
- \`/checkout\` — guest or signed-in checkout. The server validates availability and recalculates prices, tax and shipping.
- \`/order-confirmation/{uuid}\` — confirmation for the order's unguessable public token.
- \`/register\`, \`/login\`, \`/logout\`, \`/account\`, \`/account/orders/{id}\` — customer identity and private order history.

When a customer signs in or registers, their guest bag is merged into their account bag. Checkout re-locks variants, checks stock, snapshots product/variant data, writes order items and a pending payment record, decrements stock, records inventory movements and clears the bag in a database transaction. The order confirmation link does not expose the customer's address or phone number.

Wishlist hearts are currently device-local. They are not synced across browsers or stored in the customer's account.

## Commerce Admin UI Kit

- \`/admin/login\` and \`/admin\` — protected by the configured administrator flag.
- Persian RTL administration using the local IRANYekanX font, with four live dashboard KPIs and a 12-month grouped sales/order chart.
- Paginated product CRUD with variants, SKU validation, product and per-variant image uploads/cropping, grouped local-currency prices, taxable flag, and inventory audit trail.
- Category and brand management with unique slugs and safe deactivation when products still reference a record.
- Order list/detail, manually-created orders, state transitions, cancellation/return stock restoration, and shared payment/accounting services.
- Standalone customer, payment and inventory ledger screens, plus review moderation and analytics.

Create a customer account first, then promote an existing account to administrator from the project root:

\`\`\`bash
php artisan commerce-admin:make-admin admin@example.com
\`\`\`

The command changes only the configured administrator flag on an existing user by default. To create a new administrator deliberately, run `php artisan commerce-admin:make-admin admin@example.com --create`; it requests the name and a confirmed password interactively. No account is created without `--create`.

An optional `AdminSeeder` can bootstrap the first admin from environment values; it skips when credentials are blank and never resets an existing user's password. `DatabaseSeeder` also creates the two launch-catalog products as ordinary Eloquent records, so their database IDs are used for editing and archiving in Commerce Admin. The launch seeder attaches an image only when the referenced file really exists on the configured public disk; it never creates a Media row pointing at a missing file. The broader demo seeders below remain opt-in. Online card processing and automated bank-transfer reconciliation are not enabled. Available customer payment methods are cash on delivery and bank transfer, with payment instructions handled outside this application. A manual admin payment update records the status in both the order and its latest payment record; it does not contact a payment provider.

## Configuration

The sample environment file exposes:

\`\`\`dotenv
COMMERCE_ADMIN_CURRENCY=IRR
COMMERCE_ADMIN_CURRENCY_SYMBOL="تومان"
COMMERCE_ADMIN_TAX_RATE=0
COMMERCE_ADMIN_SHIPPING_FLAT_RATE=0
\`\`\`

Tax rate is fractional: \`0.08\` means 8%. Shipping is a fixed per-order amount. Both default to zero until explicitly configured. Product taxability is controlled by each product's \`is_taxable\` flag.

Displayed product and variant prices use thousands separators and the configured currency symbol. The product form accepts grouped Persian/Arabic or Latin digits, normalizes them before validation, and stores numeric values without commas. Variant images are saved against each variant's own media relation; selecting a color on the product page swaps its image, price, and stock together.

### Optional demo catalog seeders

The demo seeders create normal editable database records and version-controlled SVG product images under `storage/app/public/demo-products`. They include separate category, brand, product/variant, and inventory seeders. Run them only in a development or staging database:

```bash
php artisan db:seed --class=CategoryDemoSeeder
php artisan db:seed --class=BrandDemoSeeder
php artisan db:seed --class=ProductDemoSeeder
php artisan db:seed --class=InventoryDemoSeeder
# Or run all dependent demo data at once:
php artisan db:seed --class=CommerceDemoSeeder
```

The commands are idempotent and do not delete unrelated records. Demo categories, products, variants and images can be edited or archived through Commerce Admin. Inventory is adjusted through the Inventory screen; its movement ledger is intentionally retained as audit history.

## Local setup

Requirements: PHP 8.2+, Composer, Node.js/npm, and a Laravel-supported database.

\`\`\`bash
git clone https://github.com/MREZA-MJDi/vision.git
cd vision
composer install
\`\`\`

Copy \`.env.example\` to \`.env\` (Windows CMD: \`copy .env.example .env\`; PowerShell: \`Copy-Item .env.example .env\`), create/configure your database, then run:

\`\`\`bash
php artisan key:generate
php artisan migrate
php artisan vendor:publish --tag=commerce-admin-assets --force
npm install
npm run build
php artisan storage:link
php artisan serve
\`\`\`

Open \`http://127.0.0.1:8000\`. During frontend development, use \`npm run dev\` in a second terminal.

## Verification

Run the application checks before merging or deploying:

\`\`\`bash
php artisan test
npm run build
\`\`\`

The test suite covers public catalog browsing, customer cart/checkout, order totals, stock accounting, customer order privacy, administrator access, payment-state synchronization, and order-state transitions.

## Links

- Repository: https://github.com/MREZA-MJDi/vision
- Laravel documentation: https://laravel.com/docs/12.x
