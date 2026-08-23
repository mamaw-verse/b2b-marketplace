<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BuyerProfile;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DemoUsersSeeder extends Seeder
{
    /**
     * Seed a demo buyer and a fully-onboarded demo seller with 5 published products.
     * Seller products carry variant_attributes_schema inherited from their categories,
     * and all variant attributes use the attr_<key> format matching those schemas.
     */
    public function run(): void
    {
        $this->seedBuyer();
        $this->seedSeller();
    }

    // -------------------------------------------------------------------------

    private function seedBuyer(): void
    {
        $buyer = User::firstOrCreate(
            ['email' => 'buyer@gmail.com'],
            [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        $buyer->assignRole('buyer');

        BuyerProfile::firstOrCreate(
            ['user_id' => $buyer->id],
            [
                'business_name' => 'Santos Food Enterprises',
                'billing_address_1' => '123 Rizal Avenue',
                'billing_address_2' => 'Brgy. Poblacion',
                'billing_city' => 'Makati City',
                'billing_province' => 'Metro Manila',
                'billing_zip_code' => '1200',
                'billing_country_code' => 'PH',
                'shipping_address_1' => '123 Rizal Avenue',
                'shipping_address_2' => 'Brgy. Poblacion',
                'shipping_city' => 'Makati City',
                'shipping_province' => 'Metro Manila',
                'shipping_zip_code' => '1200',
                'shipping_country_code' => 'PH',
                'contact_phone' => '+63 917 123 4567',
                'contact_email' => 'buyer@gmail.com',
            ]
        );
    }

    private function seedSeller(): void
    {
        $seller = User::firstOrCreate(
            ['email' => 'seller@gmail.com'],
            [
                'first_name' => 'Abdullah',
                'last_name' => 'Macapaar',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        $seller->assignRole('seller');

        /** @var Shop $shop */
        $shop = Shop::firstOrCreate(
            ['email' => 'info@macapaarfoods.ph'],
            [
                'user_id' => $seller->id,
                'registered_business_name' => 'Macapaar Halal Foods Corporation',
                'storefront_name' => 'Macapaar Halal Foods',
                'business_structure' => 'corporation',
                'dti_sec_number' => 'SEC-2019-00784',
                'tax_identification_number' => '423-856-012-000',
                'phone' => '+63 64 421 9900',
                'address_1' => 'Quezon Avenue, Brgy. Tamontaka',
                'city' => 'Cotabato City',
                'province' => 'Maguindanao del Norte',
                'zip_code' => '9600',
                'country_code' => 'PH',
                'fda_lto_number' => 'LTO-2020-053219',
                'average_production_lead_time' => 7,
                'export_readiness_status' => 'exporting_regionally',
                'value_chain_positions' => ['manufacturing', 'distribution'],
                'capabilities' => ['processing', 'packaging', 'private_label', 'wholesale'],
                'status' => 'active',
            ]
        );

        $this->attachShopCategories($shop);
        $this->seedProducts($shop);
    }

    private function attachShopCategories(Shop $shop): void
    {
        foreach (['meat-poultry', 'processed-foods', 'spices-seasonings'] as $slug) {
            $categoryId = Category::where('slug', $slug)->value('id');

            if ($categoryId && ! $shop->categories()->where('category_id', $categoryId)->exists()) {
                $shop->categories()->attach($categoryId);
            }
        }
    }

    /**
     * Merge variant_attributes_schema entries from the given category slugs.
     * Deduplicates by key — first occurrence wins (same logic as ProductForm).
     *
     * @param  string[]  $slugs
     * @return array<string, mixed>
     */
    private function schemaForCategories(array $slugs): array
    {
        $merged = [];

        foreach ($slugs as $slug) {
            $schema = Category::where('slug', $slug)->value('variant_attributes_schema') ?? [];
            foreach ($schema as $key => $def) {
                $merged[$key] ??= $def;
            }
        }

        return $merged;
    }

    private function seedProducts(Shop $shop): void
    {
        $products = [
            // ----------------------------------------------------------------
            // 1 — Halal Whole Broiler Chicken (Frozen)
            //     Categories: meat-poultry
            //     Schema keys: attr_cut, attr_weight, attr_packaging
            // ----------------------------------------------------------------
            [
                'product' => [
                    'name' => 'Halal Whole Broiler Chicken (Frozen)',
                    'description' => 'Premium halal-certified whole broiler chickens, hand-slaughtered per IDCP protocols and blast-frozen at −18 °C. Individually vacuum-sealed for maximum freshness. Antibiotic-free, grain-fed. Ideal for food service operators, hotels, restaurants, and institutional catering.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['meat-poultry'],
                'variants' => [
                    [
                        'sku' => 'MHF-CHK-1200',
                        'description' => '1.2 kg average weight, 10 per carton',
                        'attributes' => [
                            'attr_cut' => 'Whole',
                            'attr_weight' => '1.2 kg avg',
                            'attr_packaging' => 'Vacuum-sealed, 10 per carton',
                        ],
                        'price' => 215.00,
                        'minimum_order_quantity' => 100,
                        'unit' => 'piece',
                        'currency' => 'PHP',
                        'available_quantity' => 8000,
                        'lead_time_days' => 3,
                        'pack_size' => '10 pieces per carton',
                        'quantity_breaks' => [
                            ['min_qty' => 500, 'price' => 205.00],
                            ['min_qty' => 1000, 'price' => 195.00],
                        ],
                        'status' => 'published',
                    ],
                    [
                        'sku' => 'MHF-CHK-1800',
                        'description' => '1.8 kg average weight, 8 per carton',
                        'attributes' => [
                            'attr_cut' => 'Whole',
                            'attr_weight' => '1.8 kg avg',
                            'attr_packaging' => 'Vacuum-sealed, 8 per carton',
                        ],
                        'price' => 310.00,
                        'minimum_order_quantity' => 50,
                        'unit' => 'piece',
                        'currency' => 'PHP',
                        'available_quantity' => 4000,
                        'lead_time_days' => 3,
                        'pack_size' => '8 pieces per carton',
                        'quantity_breaks' => [
                            ['min_qty' => 200, 'price' => 298.00],
                            ['min_qty' => 500, 'price' => 285.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],

            // ----------------------------------------------------------------
            // 2 — Halal Beef Caldereta Cut (Bone-In)
            //     Categories: meat-poultry
            //     Schema keys: attr_cut, attr_weight, attr_packaging
            // ----------------------------------------------------------------
            [
                'product' => [
                    'name' => 'Halal Beef Caldereta Cut (Bone-In)',
                    'description' => 'Bone-in Brahman beef cuts portioned for Filipino caldereta and beef stew. Sourced from NMIS-accredited halal slaughterhouses in Cotabato. Vacuum-packed in 1 kg retail or 5 kg bulk packs. No added water, no fillers.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['meat-poultry'],
                'variants' => [
                    [
                        'sku' => 'MHF-BEEF-1KG',
                        'description' => '1 kg retail vacuum pack',
                        'attributes' => [
                            'attr_cut' => 'Bone-in',
                            'attr_weight' => '1 kg',
                            'attr_packaging' => 'Vacuum-sealed',
                        ],
                        'price' => 480.00,
                        'minimum_order_quantity' => 50,
                        'unit' => 'pack',
                        'currency' => 'PHP',
                        'available_quantity' => 3000,
                        'lead_time_days' => 5,
                        'pack_size' => '1 kg pack, 10 per carton',
                        'quantity_breaks' => [
                            ['min_qty' => 200, 'price' => 455.00],
                            ['min_qty' => 500, 'price' => 430.00],
                        ],
                        'status' => 'published',
                    ],
                    [
                        'sku' => 'MHF-BEEF-5KG',
                        'description' => '5 kg bulk carton for food service',
                        'attributes' => [
                            'attr_cut' => 'Bone-in',
                            'attr_weight' => '5 kg',
                            'attr_packaging' => 'Vacuum-sealed carton',
                        ],
                        'price' => 2200.00,
                        'minimum_order_quantity' => 10,
                        'unit' => 'carton',
                        'currency' => 'PHP',
                        'available_quantity' => 1500,
                        'lead_time_days' => 5,
                        'pack_size' => '5 kg carton',
                        'quantity_breaks' => [
                            ['min_qty' => 50, 'price' => 2100.00],
                            ['min_qty' => 100, 'price' => 2000.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],

            // ----------------------------------------------------------------
            // 3 — Chicken Longganisa (Halal, Sweet-Type)
            //     Categories: processed-foods, meat-poultry
            //     Merged schema: attr_weight, attr_packaging, attr_shelf_life (processed-foods)
            //                  + attr_cut (meat-poultry, deduped attr_weight/packaging)
            // ----------------------------------------------------------------
            [
                'product' => [
                    'name' => 'Chicken Longganisa (Halal, Sweet-Type)',
                    'description' => 'Filipino-style sweet chicken longganisa made from ground free-range chicken, muscovado sugar, garlic, and mild spices. Halal-certified by IDCP. No artificial preservatives; cured with natural sea salt and calamansi. Pan-fry or grill from frozen. 12 links per tray.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['processed-foods', 'meat-poultry'],
                'variants' => [
                    [
                        'sku' => 'MHF-LONGGA-400G',
                        'description' => '400g tray (12 links)',
                        'attributes' => [
                            'attr_weight' => '400g',
                            'attr_packaging' => 'Retail tray, vacuum-sealed',
                            'attr_shelf_life' => 'Frozen: 3 months',
                            'attr_cut' => 'Boneless',
                        ],
                        'price' => 185.00,
                        'minimum_order_quantity' => 48,
                        'unit' => 'tray',
                        'currency' => 'PHP',
                        'available_quantity' => 10000,
                        'lead_time_days' => 5,
                        'pack_size' => '400g tray, 12 trays per master carton',
                        'quantity_breaks' => [
                            ['min_qty' => 240, 'price' => 175.00],
                            ['min_qty' => 960, 'price' => 165.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],

            // ----------------------------------------------------------------
            // 4 — Mindanao Chili Garlic Paste (Halal)
            //     Categories: spices-seasonings
            //     Schema keys: attr_weight, attr_grade, attr_packaging
            // ----------------------------------------------------------------
            [
                'product' => [
                    'name' => 'Mindanao Chili Garlic Paste (Halal)',
                    'description' => 'Traditional Mindanao chili-garlic paste blended from sun-dried bird\'s eye chili (labuyo), roasted garlic, vinegar, and coconut oil. No MSG, no artificial colors. Used as a condiment, marinade base, and cooking paste.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1692302756206-3dd7506cb8f0?q=80&w=987',
                    'status' => 'published',
                ],
                'categories' => ['spices-seasonings'],
                'variants' => [
                    [
                        'sku' => 'MHF-CHIGARLIC-250G',
                        'description' => '250g glass jar',
                        'attributes' => [
                            'attr_weight' => '250g',
                            'attr_grade' => 'Food Grade',
                            'attr_packaging' => 'Glass jar with tamper-evident lid',
                        ],
                        'price' => 120.00,
                        'minimum_order_quantity' => 24,
                        'unit' => 'jar',
                        'currency' => 'PHP',
                        'available_quantity' => 6000,
                        'lead_time_days' => 7,
                        'pack_size' => '250g jar, 12 per carton',
                        'quantity_breaks' => [
                            ['min_qty' => 144, 'price' => 110.00],
                            ['min_qty' => 576, 'price' => 100.00],
                        ],
                        'status' => 'published',
                    ],
                    [
                        'sku' => 'MHF-CHIGARLIC-1KG',
                        'description' => '1 kg institutional tub',
                        'attributes' => [
                            'attr_weight' => '1 kg',
                            'attr_grade' => 'Food Grade',
                            'attr_packaging' => 'HDPE tub with lid',
                        ],
                        'price' => 420.00,
                        'minimum_order_quantity' => 12,
                        'unit' => 'tub',
                        'currency' => 'PHP',
                        'available_quantity' => 2000,
                        'lead_time_days' => 7,
                        'pack_size' => '1 kg tub, 6 per carton',
                        'quantity_breaks' => [
                            ['min_qty' => 48, 'price' => 395.00],
                            ['min_qty' => 120, 'price' => 370.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],

            // ----------------------------------------------------------------
            // 5 — Halal Beef Tapa (Ready-to-Cook)
            //     Categories: processed-foods, meat-poultry
            //     Merged schema: attr_weight, attr_packaging, attr_shelf_life,
            //                    attr_cut (from meat-poultry, deduped)
            // ----------------------------------------------------------------
            [
                'product' => [
                    'name' => 'Halal Beef Tapa (Ready-to-Cook)',
                    'description' => 'Thinly sliced Brahman beef marinated in a classic Filipino blend of garlic, soy sauce, calamansi juice, and muscovado sugar. Halal-certified. No artificial tenderizers or MSG. Vacuum-packed for a 21-day refrigerated shelf life. Ready to pan-fry in under 10 minutes.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['processed-foods', 'meat-poultry'],
                'variants' => [
                    [
                        'sku' => 'MHF-TAPA-500G',
                        'description' => '500g retail vacuum pack',
                        'attributes' => [
                            'attr_weight' => '500g',
                            'attr_packaging' => 'Vacuum-sealed',
                            'attr_shelf_life' => '21 days refrigerated',
                            'attr_cut' => 'Boneless',
                        ],
                        'price' => 290.00,
                        'minimum_order_quantity' => 50,
                        'unit' => 'pack',
                        'currency' => 'PHP',
                        'available_quantity' => 5000,
                        'lead_time_days' => 5,
                        'pack_size' => '500g pack, 20 per carton',
                        'quantity_breaks' => [
                            ['min_qty' => 200, 'price' => 275.00],
                            ['min_qty' => 500, 'price' => 260.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],
        ];

        foreach ($products as $data) {
            $schema = $this->schemaForCategories($data['categories']);

            /** @var Product $product */
            $product = Product::firstOrCreate(
                [
                    'shop_id' => $shop->id,
                    'name' => $data['product']['name'],
                ],
                array_merge($data['product'], [
                    'shop_id' => $shop->id,
                    'variant_attributes_schema' => $schema,
                ])
            );

            // Update schema on existing rows (safe on re-seed).
            if (empty($product->variant_attributes_schema)) {
                $product->update(['variant_attributes_schema' => $schema]);
            }

            foreach ($data['categories'] as $slug) {
                $categoryId = Category::where('slug', $slug)->value('id');

                if ($categoryId && ! $product->categories()->where('category_id', $categoryId)->exists()) {
                    $product->categories()->attach($categoryId);
                }
            }

            foreach ($data['variants'] as $variantData) {
                ProductVariant::firstOrCreate(
                    ['sku' => $variantData['sku']],
                    array_merge($variantData, ['product_id' => $product->id])
                );
            }
        }
    }
}
