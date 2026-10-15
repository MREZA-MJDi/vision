# Virtual Try-On

The feature is available at /try-on. Only adult women and men mannequins are supported; the module deliberately has no third or unisex audience.

## Setup

Run composer dump-autoload, php artisan migrate, and php artisan db:seed --class=VirtualTryOnMannequinSeeder. The regular DatabaseSeeder also creates the two default mannequin profiles.

Edit a product in Commerce Admin and select its audience. Leave the audience blank to keep it out of the fitting room. For each active variant intended for preview, choose a mannequin and a slot, then upload a transparent layer image.

## Layer contract

- Exact canvas: 1024 × 1536 pixels (2:3).
- Transparent PNG or WebP only, with no background.
- Every layer must share the same canvas, pose, scale and alignment as the configured mannequin.
- Do not use the ordinary product gallery image as an overlay. Gallery images are only thumbnails.
- One try-on mapping is supported per variant in the first version.
- Layer order is fixed by the server: shoes, bottom, top, outerwear, then accessory.

A neutral, faceless silhouette is rendered when a custom base image is not configured. A custom base image can be connected to a mannequin through base_media_id using the existing Media table.

## API

- GET /api/virtual-try-on/bootstrap?audience=women or men
- GET /api/virtual-try-on/catalog?audience=women&mannequin_id=1&slot=top&page=1
- POST /api/virtual-try-on/preview with audience, mannequin_id, revision and a selections map keyed by slot.

The browser submits selected variant IDs only. The server resolves the active product, compatible mannequin, trusted layer URL, price and stock. Preview is stateless: selection clicks do not create database rows. Requests are rate-limited, the catalog is paginated to 24 cards and relationships are eager-loaded. The browser aborts superseded requests and checks revisions to prevent stale responses replacing a newer selection.

## Verification

Run php artisan test --filter=VirtualTryOn and npm run build. Before release, inspect 320, 360, 390, 430, 768 px and desktop widths, including missing layer images, keyboard focus and reduced-motion preferences.

The portable module is in packages/RM/VirtualTryOn; the host Laravel app provides the route registration, storefront URL names and Commerce Admin adapter/persistence bridge.
