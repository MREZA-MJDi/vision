# RM Virtual Try-On module

A Laravel feature module for layer-based garment previews over a fixed mannequin. The repository integration includes a shared workspace used by the /try-on page and product detail pages, a JSON API, Eloquent persistence, Commerce Admin product editor fields, a default mannequin seeder, migrations and regression tests. Product variants can preselect a matching layer; only the explicit expand control opens the workspace as a larger overlay. A product preview modal is not used.

## Supported behaviour

- Audience values are exactly women and men. Unclassified products stay out of the fitting room.
- The host catalog uses its own product and variant records. A selected variant needs one active try-on asset tied to a matching audience mannequin and a slot.
- A try-on layer must be a transparent PNG or WebP image on a 1024 × 1536 canvas, aligned to the mannequin pose.
- The server resolves the asset URL, effective price and stock. Browser input only contains variant IDs and slot keys.
- Layer ordering is stable: shoes, bottom, top, outerwear, accessory.
- Catalog results are paginated and eager-loaded; the browser aborts obsolete requests and ignores stale preview revisions.

## Integration points

Composer maps the module namespace to packages/RM/VirtualTryOn/src. The host app registers the API routes from routes/api.php, the /try-on page route and the Vite entries. The config file at config/virtual-try-on.php selects Eloquent models, product detail route, currency and pagination size.

When porting to another Laravel project, map models in config/virtual-try-on.php. The product model needs active state and audience; the variant model needs product(), effective_price and display_name; media needs a URL accessor; the database needs the module migrations or equivalent tables. Adjust the storefront controller/page URL integration and admin persistence bridge to the host app's route names and product editor. Do not treat the module as a vendor package with automatic route registration; the host integration is explicit.

## Setup

Run composer dump-autoload, php artisan migrate and php artisan db:seed --class=VirtualTryOnMannequinSeeder. To configure assets, edit a product in Commerce Admin, choose Women or Men, select the matching mannequin and slot for a variant, then upload a transparent 1024 × 1536 PNG/WebP.

## Verification

Run php artisan test --filter=VirtualTryOn and npm run build. Before release, inspect 320, 360, 390, 430, 768 px and desktop widths, missing layer images, keyboard focus and reduced-motion settings.
