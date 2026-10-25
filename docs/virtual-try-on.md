# Virtual Try-On

The fitting room is available at /try-on and is also embedded on product detail pages. A product page passes its currently selected variant to the same workspace; changing color or size updates that product's compatible layer in the mannequin preview. Other slot selections remain in place. The preview stays inline; only the explicit “بزرگ‌نمایی اتاق پرو” button expands it. No customer photo upload is part of this workflow.

Only adult women and men mannequins are supported; the module deliberately has no third or unisex audience.

## Expected customer workflow

1. An admin configures one base image for each mannequin profile in Commerce Admin → **اتاق پرو**. The base image is the person/mannequin only; it is not a product image.
2. Each product is assigned an audience (women or men). Every active product variant that should be selectable needs one active try-on mapping, its compatible mannequin, a garment slot and a transparent garment-layer image.
3. In the storefront, the hanger/try-on control is enabled only when the selected variant has a valid compatible layer. Clicking it opens the fitting room, selects that exact variant, and keeps selections in other garment slots. Selecting another item in the same slot replaces the prior item in that slot.
4. The preview API validates the variant and returns the ordered layer URLs. The browser overlays those assets on the configured base mannequin, and the loading indicator remains visible until the updated composition returns.
5. The ordinary product gallery image is a thumbnail only; it must never be treated as the garment overlay. If an active compatible layer is missing, the UI should explain that the product is not yet configured instead of pretending that a try-on was generated.

## Layer compositor versus AI try-on

The current implementation is a deterministic **transparent-layer compositor**, not an AI image-generation integration. It needs a base mannequin image plus a separate transparent, pose-aligned garment layer for each supported product variant. This mode is fast, predictable and can combine multiple slots in one preview, but it requires asset preparation.

An AI try-on workflow would be different: the inputs would normally be the configured mannequin/person image and the product's source garment photo. A backend provider adapter would submit those inputs to a try-on model, store a queued request, show progress while the provider works, and display the generated result when complete. The app would also need failure handling, provider credentials stored server-side, request deduplication and result caching keyed by mannequin image, product image/variant and model version. It would not require admins to upload a transparent image of every garment already aligned to the mannequin.

No AI provider, API credential, generation job or result-cache pipeline is wired into this repository today. Do not label the current layered preview as AI-generated. For a first production release, either keep the layer-compositor workflow and make its per-variant image requirement explicit, or implement AI as a distinct backend mode rather than silently mixing the two contracts. Multi-garment AI outfit composition must be verified against the selected provider; it should not be assumed to work just because single-product try-on works.

## Setup

Run composer dump-autoload, php artisan migrate, and php artisan db:seed. The regular DatabaseSeeder creates the two mannequin profiles and preserves the existing launch catalog. To add the optional editable demo outfit, run php artisan db:seed --class=VirtualTryOnDemoSeeder after the mannequin seeder has run. This seeds a mannequin base, two photo-based outerwear layers, and SVG top/trouser layers; it does not alter the default launch catalog counts.

Edit a product in Commerce Admin and select its audience. Leave the audience blank to keep it out of the fitting room. For each active variant intended for preview, choose a mannequin and a slot, then upload a transparent layer image.

## Layer contract

- Admin-upload contract: transparent PNG or WebP at exactly 1024 × 1536 pixels (2:3), with no background.
- Storage-optimized photo layers may be 320 × 480 WebP. They retain the same full 2:3 canvas and alignment while avoiding unnecessarily large catalog assets.
- The bundled demo also generates four transparent 320 × 480 SVG garment silhouettes (top and trousers) so the first outfit works without an external AI service. They are replaceable through Commerce Admin, but the admin upload input still accepts only PNG/WebP at 1024 × 1536.
- Every layer must share the same canvas, pose, scale and alignment as the configured mannequin.
- Do not use the ordinary product gallery image as an overlay. Gallery images are only thumbnails.
- One try-on mapping is supported per variant in the first version.
- Layer order is fixed by the server: shoes, bottom, top, outerwear, then accessory.

The supplied demo mannequin base is connected to the women-front mannequin through base_media_id when the opt-in demo seeder runs. A neutral, faceless SVG silhouette is rendered when a mannequin has no base image (the seeded men-front profile uses this fallback). Base images are Media records on the public disk.

## API

- GET /api/virtual-try-on/bootstrap?audience=women or men (optional `variant_id` preselection is accepted when a valid matching layer exists)
- GET /api/virtual-try-on/catalog?audience=women&mannequin_id=1&slot=top&page=1 (optional `variant_id` promotes the current product variant on the first page)
- POST /api/virtual-try-on/preview with audience, mannequin_id, revision and a selections map keyed by slot.

The browser submits selected variant IDs only. The server resolves the active product, compatible mannequin, trusted layer URL, price and stock. Preview is stateless: selection clicks do not create database rows. Requests are rate-limited, the catalog is paginated to 24 cards and relationships are eager-loaded. The browser aborts superseded requests and checks revisions to prevent stale responses replacing a newer selection.

## Verification

Run php artisan test --filter=VirtualTryOn and npm run build. Before release, inspect 320, 360, 390, 430, 768 px and desktop widths, including missing layer images, keyboard focus and reduced-motion preferences.

The portable module is in packages/RM/VirtualTryOn; the host Laravel app provides the route registration, storefront URL names and Commerce Admin adapter/persistence bridge.
