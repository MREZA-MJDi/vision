# VISION fitting-room asset import

The lookbook bundle is intentionally supplied separately from application code because it contains binary product/mannequin imagery. Place the provided `vision-fitting-room-assets.zip` at either:

- `database/seeders/assets/vision-fitting-room-assets.zip` (recommended, version-controlled); or
- `storage/app/seed-imports/vision-fitting-room-assets.zip` (server-side one-off import).

Then run:

```bash
php artisan migrate --force
php artisan db:seed --class=FittingRoomAssetSeeder --force
```

The seeder imports all 46 WebP files into the persistent `public` storage disk under `fitting-room/seeded/`, adds real rows to `media`, creates or reuses the 17 catalog products/variants, and seeds one base mannequin, 12 curated looks, their T/B/TB/S scene references, and the top/bottom variant mappings. Re-running it is safe and does not replace existing dashboard uploads or existing product/variant edits.

The asset bundle contains:
- 17 product-card images (including the two `g0/g1` assets)
- 1 base mannequin image
- 28 pre-rendered scene images for top, bottom, combined, and suggested looks

If the archive is not present, the seeder prints a warning and exits without breaking ordinary `db:seed` runs. The existing storefront remains backed by the database; it does not read this ZIP at request time.
