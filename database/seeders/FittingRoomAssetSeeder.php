<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\FittingRoomLook;
use App\Models\FittingRoomLookItem;
use App\Models\FittingRoomMannequin;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Imports the supplied VISION lookbook once into public storage and the real
 * Media / product / variant / fitting-room tables. Re-running this seeder is
 * safe: it won't replace existing admin edits or re-upload the same media rows.
 */
class FittingRoomAssetSeeder extends Seeder
{
    private const ARCHIVE = 'assets/vision-fitting-room-assets.zip';

    public function run(): void
    {
        $assets = $this->readArchive();
        $disk = Storage::disk('public');

        // Store every image as a persistent public-disk asset. A missing file
        // can be repaired on a later seed; existing files are left untouched.
        foreach ($assets as $filename => $contents) {
            $path = 'fitting-room/seeded/' . $filename;
            if (! $disk->exists($path)) {
                $disk->put($path, $contents);
            }
        }

        DB::transaction(function () use ($assets): void {
            $categories = [];
            foreach ([
                'outerwear' => ['بالاپوش', 10],
                'knitwear' => ['بافت و سویشرت', 20],
                'tops' => ['تاپ و پیراهن', 30],
                'bottoms' => ['شلوار', 40],
            ] as $slug => [$name, $order]) {
                $categories[$slug] = Category::query()->firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'description' => 'کالکشن VISION', 'is_active' => true, 'sort_order' => $order]
                );
            }

            $brand = Brand::query()->firstOrCreate(
                ['slug' => 'vision'],
                ['name' => 'VISION', 'description' => 'کالکشن اصلی VISION.', 'is_active' => true]
            );

            $definitions = $this->products();
            $variants = [];
            foreach ($definitions as $item) {
                $product = Product::withTrashed()->firstOrNew(['slug' => $item['slug']]);
                if ($product->exists && $product->trashed()) {
                    continue; // Respect an administrator's deliberate archive.
                }

                if (! $product->exists) {
                    $product->fill([
                        'category_id' => $categories[$item['category']]->getKey(),
                        'brand_id' => $brand->getKey(),
                        'name' => $item['name'],
                        'slug' => $item['slug'],
                        'short_description' => $item['name'] . ' از کالکشن VISION.',
                        'description' => $item['name'] . '؛ قابل مشاهده در فروشگاه و اتاق پرو استودیویی VISION.',
                        'meta_title' => $item['name'] . ' | VISION',
                        'meta_description' => $item['name'] . ' از کالکشن VISION.',
                        'audience' => 'women',
                        'is_active' => true,
                        'is_featured' => false,
                        'is_hero' => false,
                        'is_taxable' => false,
                        'sort_order' => $item['sort'],
                    ])->save();
                }

                $variant = ProductVariant::withTrashed()->firstOrNew(['sku' => $item['sku']]);
                if ($variant->exists && $variant->trashed()) {
                    continue;
                }
                if (! $variant->exists) {
                    $variant->fill([
                        'product_id' => $product->getKey(),
                        'sku' => $item['sku'],
                        'size' => 'M',
                        'color' => $item['color'],
                        'color_code' => $item['color_code'],
                        'price' => $item['price'],
                        'sale_price' => $item['sale_price'],
                        'stock' => 12,
                        'low_stock_threshold' => 2,
                        'is_active' => true,
                        'sort_order' => 0,
                    ])->save();
                }

                // Never silently move a pre-existing SKU between products.
                if ((int) $variant->product_id !== (int) $product->getKey()) {
                    continue;
                }

                $variants[$item['sku']] = $variant;
                $filename = $item['image'];
                $this->attachMedia(
                    $variant,
                    $filename,
                    'gallery',
                    $product->name . ' — ' . $item['color'],
                    $assets[$filename],
                    420,
                    420
                );
            }

            $mannequin = FittingRoomMannequin::query()->firstOrCreate(
                ['code' => 'vision-studio-default'],
                [
                    'label' => 'VISION — مانکن اصلی',
                    'audience' => 'all',
                    'pose_key' => 'front-neutral',
                    'canvas_width' => 420,
                    'canvas_height' => 630,
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            $baseMedia = $this->attachMedia(
                $mannequin,
                's0.webp',
                'fitting-room-mannequin',
                'VISION — مانکن پایه',
                $assets['s0.webp'],
                420,
                630
            );
            if (! $mannequin->base_media_id) {
                $mannequin->base_media_id = $baseMedia->getKey();
                $mannequin->canvas_width = 420;
                $mannequin->canvas_height = 630;
                $mannequin->save();
            }

            $looks = $this->looks();
            foreach ($looks as $definition) {
                $look = FittingRoomLook::query()->firstOrCreate(
                    ['mannequin_id' => $mannequin->getKey(), 'code' => $definition['code']],
                    [
                        'label' => $definition['label'],
                        'is_active' => true,
                        'sort_order' => $definition['sort'],
                    ]
                );

                foreach ([
                    'T' => ['preview_top_media_id', 'preview_top', 'تصویر بالاپوش'],
                    'B' => ['preview_bottom_media_id', 'preview_bottom', 'تصویر پایین‌تنه'],
                    'TB' => ['preview_combined_media_id', 'preview_combined', 'تصویر ست کامل'],
                    'S' => ['preview_suggested_media_id', 'preview_suggested', 'تصویر ست پیشنهادی'],
                ] as $state => [$foreignKey, $collection, $alt]) {
                    $filename = $definition['scenes'][$state] ?? null;
                    if (! $filename) {
                        continue;
                    }

                    $media = $this->attachMedia(
                        $look,
                        $filename,
                        'fitting-room-scene',
                        $look->label . ' — ' . $alt,
                        $assets[$filename],
                        420,
                        630
                    );
                    // Seed defaults only once; dashboard uploads remain authoritative.
                    if (! $look->{$foreignKey}) {
                        $look->{$foreignKey} = $media->getKey();
                    }
                }
                $look->save();

                foreach (['top' => 'top_sku', 'bottom' => 'bottom_sku'] as $slot => $skuKey) {
                    $sku = $definition[$skuKey] ?? null;
                    if (! $sku || ! isset($variants[$sku])) {
                        continue;
                    }

                    FittingRoomLookItem::query()->firstOrCreate(
                        ['look_id' => $look->getKey(), 'slot' => $slot],
                        [
                            'product_variant_id' => $variants[$sku]->getKey(),
                            'fit_y' => $slot === 'bottom' ? 0.660 : (float) ($definition['fit_y'] ?? 0.380),
                            'is_active' => true,
                            'sort_order' => $slot === 'top' ? 10 : 20,
                        ]
                    );
                }
            }
        });

        $this->command?->info('VISION fitting-room assets imported: product galleries, mannequin scenes, 12 looks and variant mappings are persisted.');
    }

    /**
     * Read the bundle without requiring PHP's optional ZipArchive extension.
     * The supplied archive uses standard ZIP local file headers and DEFLATE.
     *
     * @return array<string, string> filename => raw bytes
     */
    private function readArchive(): array
    {
        $path = database_path('seeders/' . self::ARCHIVE);
        $archive = @file_get_contents($path);
        if (! is_string($archive) || $archive === '') {
            throw new RuntimeException('Missing VISION asset bundle: ' . $path);
        }

        $assets = [];
        $offset = 0;
        $length = strlen($archive);
        $localHeader = "\x50\x4b\x03\x04";

        while ($offset + 30 <= $length && substr($archive, $offset, 4) === $localHeader) {
            $header = unpack(
                'Vsignature/vversion/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vuncompressed/vnameLength/vextraLength',
                substr($archive, $offset, 30)
            );
            if (! is_array($header)) {
                throw new RuntimeException('Invalid VISION asset bundle header.');
            }
            if (($header['flags'] & 0x08) !== 0) {
                throw new RuntimeException('VISION asset bundle uses an unsupported ZIP data descriptor.');
            }

            $nameStart = $offset + 30;
            $name = substr($archive, $nameStart, $header['nameLength']);
            $dataStart = $nameStart + $header['nameLength'] + $header['extraLength'];
            $compressed = substr($archive, $dataStart, $header['compressed']);
            if (strlen($compressed) !== $header['compressed']) {
                throw new RuntimeException('Truncated VISION asset bundle entry: ' . $name);
            }

            if ($header['method'] === 0) {
                $contents = $compressed;
            } elseif ($header['method'] === 8) {
                $contents = gzinflate($compressed);
            } else {
                throw new RuntimeException('Unsupported VISION asset compression method: ' . $header['method']);
            }
            if (! is_string($contents) || strlen($contents) !== $header['uncompressed']) {
                throw new RuntimeException('Could not decompress VISION asset: ' . $name);
            }

            if (str_starts_with($name, 'images/')) {
                $assets[substr($name, strlen('images/'))] = $contents;
            }
            $offset = $dataStart + $header['compressed'];
        }

        if (count($assets) !== 46) {
            throw new RuntimeException('Expected 46 VISION assets; found ' . count($assets) . '.');
        }

        return $assets;
    }

    private function attachMedia(
        FittingRoomMannequin|FittingRoomLook|ProductVariant $owner,
        string $filename,
        string $collection,
        string $alt,
        string $contents,
        int $width,
        int $height
    ): Media {
        $path = 'fitting-room/seeded/' . $filename;
        return $owner->media()->firstOrCreate(
            ['collection' => $collection, 'disk' => 'public', 'path' => $path],
            [
                'original_name' => $filename,
                'mime_type' => 'image/webp',
                'size' => strlen($contents),
                'width' => $width,
                'height' => $height,
                'alt_text' => $alt,
                'sort_order' => 0,
                'metadata' => ['source' => 'VISION fitting-room assets ZIP', 'seeded' => true],
                'uploaded_by' => null,
            ]
        );
    }

    /**
     * Definitions align with the approved static demo's product/scene mapping.
     *
     * @return array<int, array<string, mixed>>
     */
    private function products(): array
    {
        return [
            ['slug'=>'vision-black-puffer','sku'=>'VIS-BLACK-PUFFER','name'=>'کاپشن پافر مشکی','category'=>'outerwear','image'=>'g0.webp','price'=>3790000,'sale_price'=>3290000,'color'=>'مشکی','color_code'=>'#171719','sort'=>10,'look'=>'look-00','slot'=>'top','fit_y'=>0.38],
            ['slug'=>'vision-black-trousers','sku'=>'VIS-BLACK-TROUSERS','name'=>'شلوار پارچه‌ای مشکی','category'=>'bottoms','image'=>'g1.webp','price'=>1590000,'sale_price'=>null,'color'=>'مشکی','color_code'=>'#111111','sort'=>20,'look'=>'look-00','slot'=>'bottom','fit_y'=>0.66],
            ['slug'=>'vision-cream-turtleneck','sku'=>'VIS-CREAM-TURTLENECK','name'=>'پلیور یقه‌اسکی کرم','category'=>'knitwear','image'=>'p-sweater.webp','price'=>1490000,'sale_price'=>null,'color'=>'کرم','color_code'=>'#DCCDB6','sort'=>30,'look'=>'look-01','slot'=>'top','fit_y'=>0.38],
            ['slug'=>'vision-chestnut-trousers','sku'=>'VIS-CHESTNUT-TROUSERS','name'=>'شلوار پارچه‌ای قهوه‌ای','category'=>'bottoms','image'=>'p-brownpants.webp','price'=>1690000,'sale_price'=>null,'color'=>'قهوه‌ای','color_code'=>'#594235','sort'=>40,'look'=>'look-01','slot'=>'bottom','fit_y'=>0.66],
            ['slug'=>'vision-solar-puffer','sku'=>'VIS-SOLAR-PUFFER-ORANGE','name'=>'کاپشن پافر نارنجی','category'=>'outerwear','image'=>'p-puffer.webp','price'=>3990000,'sale_price'=>3490000,'color'=>'نارنجی آفتابی','color_code'=>'#E96512','sort'=>50,'look'=>'look-02','slot'=>'top','fit_y'=>0.36],
            ['slug'=>'vision-black-cargo-pants','sku'=>'VIS-BLACK-CARGO','name'=>'شلوار کارگو مشکی','category'=>'bottoms','image'=>'p-cargo.webp','price'=>1790000,'sale_price'=>null,'color'=>'مشکی','color_code'=>'#161619','sort'=>60,'look'=>'look-02','slot'=>'bottom','fit_y'=>0.66],
            ['slug'=>'vision-chestnut-coat','sku'=>'VIS-CHESTNUT-COAT-BROWN','name'=>'پالتوی بلند قهوه‌ای','category'=>'outerwear','image'=>'p-coat.webp','price'=>6490000,'sale_price'=>null,'color'=>'قهوه‌ای چستنات','color_code'=>'#704231','sort'=>70,'look'=>'look-03','slot'=>'top','fit_y'=>0.45],
            ['slug'=>'vision-pink-cardigan','sku'=>'VIS-PINK-CARDIGAN','name'=>'کاردیگان صورتی','category'=>'knitwear','image'=>'p-pink.webp','price'=>1390000,'sale_price'=>null,'color'=>'صورتی','color_code'=>'#E3B7BB','sort'=>80,'look'=>'look-04','slot'=>'top','fit_y'=>0.38],
            ['slug'=>'vision-blue-jeans','sku'=>'VIS-BLUE-JEANS','name'=>'شلوار جین گشاد آبی','category'=>'bottoms','image'=>'p-jeans.webp','price'=>1590000,'sale_price'=>null,'color'=>'آبی روشن','color_code'=>'#8AA9C5','sort'=>90,'look'=>'look-04','slot'=>'bottom','fit_y'=>0.66],
            ['slug'=>'vision-charcoal-hoodie','sku'=>'VIS-CHARCOAL-HOODIE','name'=>'هودی ذغالی','category'=>'knitwear','image'=>'p-dhoodie.webp','price'=>1290000,'sale_price'=>null,'color'=>'ذغالی','color_code'=>'#34343A','sort'=>100,'look'=>'look-05','slot'=>'top','fit_y'=>0.38],
            ['slug'=>'vision-grey-sweatpants','sku'=>'VIS-GREY-SWEATPANTS','name'=>'شلوار گرمکن طوسی','category'=>'bottoms','image'=>'p-sweatp.webp','price'=>990000,'sale_price'=>null,'color'=>'طوسی','color_code'=>'#C5C5C7','sort'=>110,'look'=>'look-05','slot'=>'bottom','fit_y'=>0.66],
            ['slug'=>'vision-grey-zip-hoodie','sku'=>'VIS-GREY-ZIP-HOODIE','name'=>'هودی زیپ‌دار طوسی','category'=>'knitwear','image'=>'p-zip.webp','price'=>1390000,'sale_price'=>null,'color'=>'طوسی','color_code'=>'#77777A','sort'=>120,'look'=>'look-06','slot'=>'top','fit_y'=>0.38],
            ['slug'=>'vision-square-neck-top','sku'=>'VIS-SQUARE-NECK-TOP','name'=>'تاپ آستین‌بلند یقه‌چهارگوش','category'=>'tops','image'=>'p-square.webp','price'=>790000,'sale_price'=>null,'color'=>'مشکی','color_code'=>'#111111','sort'=>130,'look'=>'look-07','slot'=>'top','fit_y'=>0.36],
            ['slug'=>'vision-brown-cardigan','sku'=>'VIS-BROWN-CARDIGAN','name'=>'کاردیگان بافت قهوه‌ای','category'=>'knitwear','image'=>'p-bcard.webp','price'=>1450000,'sale_price'=>null,'color'=>'قهوه‌ای','color_code'=>'#6B4836','sort'=>140,'look'=>'look-08','slot'=>'top','fit_y'=>0.38],
            ['slug'=>'vision-gold-button-cardigan','sku'=>'VIS-GOLD-BUTTON-CARDIGAN','name'=>'کاردیگان مشکی دکمه‌طلایی','category'=>'knitwear','image'=>'p-gold.webp','price'=>1990000,'sale_price'=>1690000,'color'=>'مشکی','color_code'=>'#151515','sort'=>150,'look'=>'look-09','slot'=>'top','fit_y'=>0.38],
            ['slug'=>'vision-grey-kangaroo-hoodie','sku'=>'VIS-GREY-KANGAROO-HOODIE','name'=>'هودی طوسی کانگورویی','category'=>'knitwear','image'=>'p-ghood.webp','price'=>1190000,'sale_price'=>null,'color'=>'طوسی','color_code'=>'#A1A1A5','sort'=>160,'look'=>'look-10','slot'=>'top','fit_y'=>0.38],
            ['slug'=>'vision-light-blue-shirt','sku'=>'VIS-LIGHT-BLUE-SHIRT','name'=>'پیراهن آبی روشن','category'=>'tops','image'=>'p-blue.webp','price'=>1090000,'sale_price'=>null,'color'=>'آبی روشن','color_code'=>'#9DBDDB','sort'=>170,'look'=>'look-11','slot'=>'top','fit_y'=>0.38],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function looks(): array
    {
        return [
            ['code'=>'look-00','label'=>'پافر مشکی و شلوار مشکی','sort'=>0,'top_sku'=>'VIS-BLACK-PUFFER','bottom_sku'=>'VIS-BLACK-TROUSERS','scenes'=>['T'=>'s1.webp','B'=>'s2.webp','TB'=>'s3.webp']],
            ['code'=>'look-01','label'=>'پلیور کرم و شلوار قهوه‌ای','sort'=>10,'top_sku'=>'VIS-CREAM-TURTLENECK','bottom_sku'=>'VIS-CHESTNUT-TROUSERS','scenes'=>['T'=>'m-o1-T.webp','B'=>'m-o1-B.webp','TB'=>'m-o1-TB.webp']],
            ['code'=>'look-02','label'=>'پافر نارنجی و کارگو','sort'=>20,'top_sku'=>'VIS-SOLAR-PUFFER-ORANGE','bottom_sku'=>'VIS-BLACK-CARGO','scenes'=>['T'=>'m-o2-T.webp','B'=>'m-o2-B.webp','TB'=>'m-o2-TB.webp']],
            ['code'=>'look-03','label'=>'پالتوی بلند قهوه‌ای','sort'=>30,'top_sku'=>'VIS-CHESTNUT-COAT-BROWN','scenes'=>['T'=>'m-o3-T.webp']],
            ['code'=>'look-04','label'=>'کاردیگان صورتی و جین آبی','sort'=>40,'top_sku'=>'VIS-PINK-CARDIGAN','bottom_sku'=>'VIS-BLUE-JEANS','scenes'=>['T'=>'m-o4-T.webp','B'=>'m-o4-B.webp','TB'=>'m-o4-TB.webp']],
            ['code'=>'look-05','label'=>'هودی ذغالی و گرمکن طوسی','sort'=>50,'top_sku'=>'VIS-CHARCOAL-HOODIE','bottom_sku'=>'VIS-GREY-SWEATPANTS','scenes'=>['T'=>'m-o5-T.webp','B'=>'m-o5-B.webp','TB'=>'m-o5-TB.webp']],
            ['code'=>'look-06','label'=>'هودی زیپ‌دار طوسی','sort'=>60,'top_sku'=>'VIS-GREY-ZIP-HOODIE','scenes'=>['T'=>'m-o6-T.webp','S'=>'m-o6-S.webp']],
            ['code'=>'look-07','label'=>'تاپ یقه‌چهارگوش','sort'=>70,'top_sku'=>'VIS-SQUARE-NECK-TOP','scenes'=>['T'=>'m-o7-T.webp','S'=>'m-o7-S.webp']],
            ['code'=>'look-08','label'=>'کاردیگان بافت قهوه‌ای','sort'=>80,'top_sku'=>'VIS-BROWN-CARDIGAN','scenes'=>['T'=>'m-o8-T.webp','S'=>'m-o8-S.webp']],
            ['code'=>'look-09','label'=>'کاردیگان مشکی دکمه‌طلایی','sort'=>90,'top_sku'=>'VIS-GOLD-BUTTON-CARDIGAN','scenes'=>['T'=>'m-o9-T.webp','S'=>'m-o9-S.webp']],
            ['code'=>'look-10','label'=>'هودی طوسی کانگورویی','sort'=>100,'top_sku'=>'VIS-GREY-KANGAROO-HOODIE','scenes'=>['T'=>'m-o10-T.webp','S'=>'m-o10-S.webp']],
            ['code'=>'look-11','label'=>'پیراهن آبی روشن','sort'=>110,'top_sku'=>'VIS-LIGHT-BLUE-SHIRT','scenes'=>['T'=>'m-o11-T.webp','S'=>'m-o11-S.webp']],
        ];
    }
}
