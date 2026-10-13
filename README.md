# VISION — Commerce Storefront

VISION is a Laravel 12 storefront backed by Eloquent, Blade, Vite and a reusable Commerce Admin Kit.

## Customer routes

- \`/\` and \`/shop\` — product discovery with search, category/brand filters, sorting and pagination.
- \`/products/{slug}\` — active product detail with selectable variants, current server-backed price and stock.
- \`/cart\` — database-backed bag for guest sessions and signed-in customers.
- \`/checkout\` — guest or signed-in checkout. The server validates availability and recalculates prices, tax and shipping.
- \`/order-confirmation/{uuid}\` — confirmation for the order's unguessable public token.
- \`/register\`, \`/login\`, \`/logout\`, \`/account\`, \`/account/orders/{id}\` — customer identity and private order history.

When a customer signs in or registers, their guest bag is merged into their account bag. Checkout re-locks variants, checks stock, snapshots product/variant data, writes order items and a pending payment record, decrements stock, records inventory movements and clears the bag in a database transaction. The order confirmation link does not expose the customer's address or phone number.

Wishlist hearts are currently device-local. They are not synced across browsers or stored in the customer's account.

## Commerce Admin Kit

- \`/admin/login\` and \`/admin\` — protected by the configured administrator flag.
- Product CRUD with variants, SKU/barcode validation, media uploads, taxable flag, and stock adjustments recorded to the inventory ledger.
- Order list/detail, manually-created orders, delivery-state transitions, cancellation/return stock restoration, and manual payment-record updates.
- Review moderation and analytics dashboards.

Create a customer account first, then promote an existing account to administrator from the project root:

\`\`\`bash
php artisan commerce-admin:make-admin admin@example.com
\`\`\`

The command changes only the configured administrator flag on the existing user. It does not create an account or seed fake commerce data.

The module does not currently provide standalone category/brand management screens; product forms select existing category and brand records. Online card processing and automated bank-transfer reconciliation are not enabled. Available customer payment methods are cash on delivery and bank transfer, with payment instructions handled outside this application. A manual admin payment update records the status in both the order and its latest payment record; it does not contact a payment provider.

## Configuration

The sample environment file exposes:

\`\`\`dotenv
COMMERCE_ADMIN_CURRENCY=USD
COMMERCE_ADMIN_CURRENCY_SYMBOL="$"
COMMERCE_ADMIN_TAX_RATE=0
COMMERCE_ADMIN_SHIPPING_FLAT_RATE=0
\`\`\`

Tax rate is fractional: \`0.08\` means 8%. Shipping is a fixed per-order amount. Both default to zero until explicitly configured. Product taxability is controlled by each product's \`is_taxable\` flag.

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
