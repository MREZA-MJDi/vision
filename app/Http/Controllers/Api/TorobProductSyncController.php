<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class TorobProductSyncController extends Controller
{
    private const PAGE_SIZE = 100;

    /**
     * Implements Torob Product API v3. Torob calls the shop; the shop never
     * needs to send its own private payment or marketplace credentials to Torob.
     */
    public function index(Request $request): JsonResponse
    {
        if (!config('services.torob.product_api_enabled')) {
            return response()->json(['error' => 'Torob Product API is disabled.'], 503);
        }

        if (!$this->validTorobToken($request)) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        if (!$request->isJson()) {
            return $this->badRequest('Content-Type must be application/json.');
        }

        $body = $request->json()->all();
        if (!is_array($body) || $body === []) {
            return $this->badRequest('A valid request body is required.');
        }

        $hasUrls = array_key_exists('page_urls', $body);
        $hasUniques = array_key_exists('page_uniques', $body);
        $hasPage = array_key_exists('page', $body);
        $hasCursor = array_key_exists('cursor', $body)
            || (!$hasPage && ($body['sort'] ?? null) === 'product_id_desc');

        if ((int) $hasUrls + (int) $hasUniques + (int) $hasPage + (int) $hasCursor !== 1) {
            return $this->badRequest('Use exactly one of page_urls, page_uniques, page pagination, or cursor pagination.');
        }

        if ($hasUrls) {
            return $this->byPageUrls($body['page_urls']);
        }

        if ($hasUniques) {
            return $this->byPageUniques($body['page_uniques']);
        }

        if ($hasCursor) {
            return $this->byCursor($body);
        }

        return $this->byPage($body);
    }

    private function byPage(array $body): JsonResponse
    {
        $page = filter_var($body['page'] ?? null, FILTER_VALIDATE_INT);
        $sort = $body['sort'] ?? null;

        if ($page === false || $page === null || $page < 1) {
            return $this->badRequest('page must be an integer greater than zero.');
        }

        if (!in_array($sort, ['date_added_desc', 'date_updated_desc', 'product_id_desc'], true)) {
            return $this->badRequest('sort must be date_added_desc, date_updated_desc, or product_id_desc.');
        }

        if (array_key_exists('cursor', $body) || array_key_exists('limit', $body) || array_key_exists('size', $body)) {
            return $this->badRequest('Page pagination cannot include cursor, limit, or size.');
        }

        $query = $this->activeProductVariants();
        $this->applySort($query, $sort);

        $total = (clone $query)->reorder()->count('product_variants.id');
        $products = $query
            ->offset(((int) $page - 1) * self::PAGE_SIZE)
            ->limit(self::PAGE_SIZE)
            ->get()
            ->map(fn (ProductVariant $variant) => $this->serializeVariant($variant))
            ->filter()
            ->values();

        return response()->json([
            'api_version' => 'torob_api_v3',
            'current_page' => (int) $page,
            'total' => $total,
            'max_pages' => max(1, (int) ceil($total / self::PAGE_SIZE)),
            'next_cursor' => null,
            'products' => $products,
        ]);
    }

    private function byCursor(array $body): JsonResponse
    {
        if (($body['sort'] ?? null) !== 'product_id_desc') {
            return $this->badRequest('Cursor pagination requires sort=product_id_desc.');
        }

        if (array_key_exists('page', $body) || array_key_exists('limit', $body) || array_key_exists('size', $body)) {
            return $this->badRequest('Cursor pagination cannot include page, limit, or size.');
        }

        $cursor = $body['cursor'] ?? null;
        $cursorProductId = null;
        $cursorVariantId = null;

        if ($cursor !== null) {
            if (!is_string($cursor) || !preg_match('/^([1-9][0-9]*):([1-9][0-9]*)$/', $cursor, $matches)) {
                return $this->badRequest('cursor must be the unchanged next_cursor value returned by the previous response.');
            }
            $cursorProductId = (int) $matches[1];
            $cursorVariantId = (int) $matches[2];
        }

        $query = $this->activeProductVariants();
        if ($cursorProductId !== null && $cursorVariantId !== null) {
            $query->where(function (Builder $nested) use ($cursorProductId, $cursorVariantId): void {
                $nested->where('product_variants.product_id', '<', $cursorProductId)
                    ->orWhere(function (Builder $sameProduct) use ($cursorProductId, $cursorVariantId): void {
                        $sameProduct->where('product_variants.product_id', '=', $cursorProductId)
                            ->where('product_variants.id', '<', $cursorVariantId);
                    });
            });
        }

        $variants = $query
            ->orderByDesc('product_variants.product_id')
            ->orderByDesc('product_variants.id')
            ->limit(self::PAGE_SIZE + 1)
            ->get();

        $hasMore = $variants->count() > self::PAGE_SIZE;
        $pageVariants = $variants->take(self::PAGE_SIZE)->values();
        $nextCursor = null;

        if ($hasMore && $pageVariants->isNotEmpty()) {
            $last = $pageVariants->last();
            $nextCursor = (int) $last->product_id . ':' . (int) $last->getKey();
        }

        return response()->json([
            'api_version' => 'torob_api_v3',
            'total' => null,
            'max_pages' => null,
            'next_cursor' => $nextCursor,
            'products' => $pageVariants
                ->map(fn (ProductVariant $variant) => $this->serializeVariant($variant))
                ->filter()
                ->values(),
        ]);
    }

    private function byPageUniques(mixed $pageUniques): JsonResponse
    {
        if (!$this->validStringList($pageUniques, 100)) {
            return $this->badRequest('page_uniques must contain 1 to 100 string identifiers.');
        }

        $selectors = [];
        foreach ($pageUniques as $unique) {
            if (!preg_match('/^([1-9][0-9]*)_([1-9][0-9]*)$/', $unique, $matches)) {
                return $this->badRequest('Each page_unique must use the productId_variantId format.');
            }
            $selectors[] = ['product_id' => (int) $matches[1], 'variant_id' => (int) $matches[2]];
        }

        $variants = $this->activeProductVariants()
            ->whereIn('product_variants.id', array_values(array_unique(array_column($selectors, 'variant_id'))))
            ->get()
            ->keyBy(fn (ProductVariant $variant) => (int) $variant->getKey());

        $products = collect();
        foreach ($selectors as $selector) {
            $variant = $variants->get($selector['variant_id']);
            if (!$variant || (int) $variant->product_id !== $selector['product_id']) {
                continue;
            }
            $serialized = $this->serializeVariant($variant);
            if ($serialized) {
                $products->push($serialized);
            }
        }

        return response()->json([
            'api_version' => 'torob_api_v3',
            'current_page' => 1,
            'total' => $products->count(),
            'max_pages' => 1,
            'next_cursor' => null,
            'products' => $products,
        ]);
    }

    private function byPageUrls(mixed $pageUrls): JsonResponse
    {
        if (!$this->validStringList($pageUrls, 100)) {
            return $this->badRequest('page_urls must contain 1 to 100 absolute HTTP(S) URLs.');
        }

        $selectors = [];
        foreach ($pageUrls as $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)
                || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                return $this->badRequest('Each page_url must be an absolute HTTP(S) URL.');
            }

            $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
            $slug = basename($path);
            if ($slug === '' || $slug === '.' || $slug === '/') {
                continue;
            }

            $queryString = (string) parse_url($url, PHP_URL_QUERY);
            parse_str($queryString, $query);
            $selectors[] = [
                'slug' => $slug,
                'variant_id' => filter_var($query['variant_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            ];
        }

        if ($selectors === []) {
            return response()->json([
                'api_version' => 'torob_api_v3',
                'current_page' => 1,
                'total' => 0,
                'max_pages' => 1,
                'next_cursor' => null,
                'products' => [],
            ]);
        }

        $slugToId = Product::query()
            ->active()
            ->whereIn('slug', array_values(array_unique(array_column($selectors, 'slug'))))
            ->pluck('id', 'slug');

        $variants = $this->activeProductVariants()
            ->whereIn('product_variants.product_id', $slugToId->values()->all())
            ->orderBy('product_variants.product_id')
            ->orderBy('product_variants.sort_order')
            ->orderBy('product_variants.id')
            ->get()
            ->groupBy(fn (ProductVariant $variant) => (int) $variant->product_id);

        $products = collect();
        foreach ($selectors as $selector) {
            $productId = (int) ($slugToId[$selector['slug']] ?? 0);
            $productVariants = $variants->get($productId, collect());

            if ($selector['variant_id'] !== null) {
                $variant = $productVariants->first(
                    fn (ProductVariant $candidate) => (int) $candidate->getKey() === $selector['variant_id']
                );
            } else {
                $variant = $this->preferredVariant($productVariants);
            }

            if (!$variant) {
                continue;
            }
            $serialized = $this->serializeVariant($variant);
            if ($serialized) {
                $products->push($serialized);
            }
        }

        return response()->json([
            'api_version' => 'torob_api_v3',
            'current_page' => 1,
            'total' => $products->count(),
            'max_pages' => 1,
            'next_cursor' => null,
            'products' => $products,
        ]);
    }

    private function preferredVariant(Collection $variants): ?ProductVariant
    {
        return $variants
            ->sort(fn (ProductVariant $a, ProductVariant $b) => [
                (int) ((int) $a->stock < 1),
                (int) $a->sort_order,
                (int) $a->getKey(),
            ] <=> [
                (int) ((int) $b->stock < 1),
                (int) $b->sort_order,
                (int) $b->getKey(),
            ])
            ->first();
    }

    private function activeProductVariants(): Builder
    {
        return ProductVariant::query()
            ->join('products as torob_products', 'torob_products.id', '=', 'product_variants.product_id')
            ->where('torob_products.is_active', true)
            ->whereNull('torob_products.deleted_at')
            ->where('product_variants.is_active', true)
            ->where(function (Builder $query): void {
                $query->whereHas('primaryGalleryMedia')
                    ->orWhereHas('product.primaryGalleryMedia')
                    ->orWhereHas('product.galleryMedia');
            })
            ->select('product_variants.*')
            ->with([
                'product.category',
                'product.brand',
                'product.primaryGalleryMedia',
                'product.galleryMedia',
                'primaryGalleryMedia',
            ]);
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'date_added_desc' => $query
                ->orderByDesc('torob_products.created_at')
                ->orderByDesc('product_variants.id'),
            'date_updated_desc' => $query
                ->orderByDesc('torob_products.updated_at')
                ->orderByDesc('product_variants.id'),
            'product_id_desc' => $query
                ->orderByDesc('torob_products.id')
                ->orderByDesc('product_variants.id'),
        };
    }

    private function serializeVariant(ProductVariant $variant): ?array
    {
        $product = $variant->product;
        if (!$product || !$product->is_active || !$variant->is_active) {
            return null;
        }

        $images = collect([
            $variant->primaryGalleryMedia?->url,
            $product->primaryGalleryMedia?->url,
        ])
            ->merge($product->galleryMedia->pluck('url'))
            ->filter(fn ($url) => is_string($url) && trim($url) !== '')
            ->map(fn (string $url) => $this->absoluteImageUrl($url))
            ->filter()
            ->unique()
            ->take(10)
            ->values();

        if ($images->isEmpty()) {
            return null;
        }

        $available = (int) $variant->stock > 0;
        $price = (int) round((float) $variant->effective_price);
        $spec = array_filter([
            'size' => $variant->size,
            'color' => $variant->color,
            'sku' => $variant->sku,
            'brand' => $product->brand?->name,
        ], fn ($value) => is_scalar($value) && trim((string) $value) !== '');

        $pageUrl = route('products.show', [
            'product' => $product->slug,
            'variant_id' => $variant->getKey(),
        ]);

        return [
            'page_unique' => (int) $product->getKey() . '_' . (int) $variant->getKey(),
            'page_url' => $pageUrl,
            'product_group_id' => (string) $product->getKey(),
            'title' => Str::limit((string) $product->name, 500, ''),
            'subtitle' => Str::limit(trim(strip_tags((string) ($product->short_description ?: ''))), 500, ''),
            'current_price' => $available ? $price : 0,
            'old_price' => $available && $variant->is_on_sale ? (int) round((float) $variant->price) : null,
            'availability' => $available,
            'category_name' => $product->category?->name,
            'image_links' => $images->all(),
            'spec' => $spec,
            'short_desc' => Str::limit(trim(strip_tags((string) ($product->short_description ?: $product->description ?: ''))), 500, ''),
            'date_added' => ($product->created_at ?? now())->toIso8601String(),
            'date_updated' => ($product->updated_at ?? $product->created_at ?? now())->toIso8601String(),
        ];
    }

    private function absoluteImageUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return url('/' . ltrim($url, '/'));
    }

    private function validTorobToken(Request $request): bool
    {
        if ($request->header('X-Torob-Token-Version') !== '1') {
            return false;
        }

        $token = $request->header('X-Torob-Token');
        if (!is_string($token)) {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        $header = json_decode($this->base64UrlDecode($parts[0]) ?? '', true);
        $claims = json_decode($this->base64UrlDecode($parts[1]) ?? '', true);
        $signature = $this->base64UrlDecode($parts[2]);

        if (!is_array($header) || !is_array($claims) || !is_string($signature)) {
            return false;
        }
        if (($header['alg'] ?? null) !== 'EdDSA' || (int) ($header['v'] ?? 0) !== 1) {
            return false;
        }
        if (!isset($claims['exp']) || !is_numeric($claims['exp']) || (int) $claims['exp'] < time()) {
            return false;
        }
        if (isset($claims['nbf']) && (!is_numeric($claims['nbf']) || (int) $claims['nbf'] > time())) {
            return false;
        }

        $audience = $claims['aud'] ?? null;
        $host = $request->getHttpHost();
        $audienceMatches = is_string($audience)
            ? hash_equals($host, $audience)
            : (is_array($audience) && in_array($host, $audience, true));
        if (!$audienceMatches || !function_exists('sodium_crypto_sign_verify_detached')) {
            return false;
        }

        $configuredKey = base64_decode((string) config('services.torob.public_key'), true);
        if (!is_string($configuredKey)) {
            return false;
        }
        $publicKey = strlen($configuredKey) === 32 ? $configuredKey : substr($configuredKey, -32);

        if (strlen($publicKey) !== 32 || strlen($signature) !== 64) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($signature, $parts[0] . '.' . $parts[1], $publicKey);
    }

    private function base64UrlDecode(string $value): ?string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        return is_string($decoded) ? $decoded : null;
    }

    private function validStringList(mixed $values, int $max): bool
    {
        if (!is_array($values) || count($values) < 1 || count($values) > $max) {
            return false;
        }
        foreach ($values as $value) {
            if (!is_string($value) || trim($value) === '') {
                return false;
            }
        }
        return true;
    }

    private function badRequest(string $message): JsonResponse
    {
        return response()->json(['error' => $message], 400);
    }
}
