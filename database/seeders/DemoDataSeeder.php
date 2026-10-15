<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DemoDataSeeder extends Seeder
{
    /**
     * Seed the database with realistic Philippine halal food marketplace data.
     *
     * 5 companies · 10 products — all with real Unsplash image URLs.
     * Categories come from the canonical tree in CategorySeeder.
     * Each category carries a variant_attributes_schema; products inherit it and
     * variant attributes are keyed to match the schema (attr_<slug> format).
     */
    public function run(): void
    {
        $categories = $this->seedCategories();
        $companies = $this->seedCompanies();
        $this->seedProducts($companies, $categories);
    }

    // -------------------------------------------------------------------------
    // Seeders
    // -------------------------------------------------------------------------

    /**
     * Seed the canonical category tree (delegates to CategorySeeder) and
     * return the selectable categories keyed by slug.
     *
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $this->call(CategorySeeder::class);

        return Category::whereNotNull('parent_id')
            ->get()
            ->keyBy('slug')
            ->all();
    }

    /**
     * @return array<int, Shop>
     */
    private function seedCompanies(): array
    {
        $companies = [
            [
                'user' => [
                    'first_name' => 'Ahmad',
                    'last_name' => 'Macarambon',
                    'email' => 'ahmad@alaminternational.ph',
                ],
                'shop' => [
                    'registered_business_name' => 'Al-Amin International Foods Corporation',
                    'storefront_name' => 'Al-Amin International Foods',
                    'business_structure' => 'corporation',
                    'dti_sec_number' => 'SEC-2018-00512',
                    'tax_identification_number' => '451-234-789-000',
                    'phone' => '+63 64 352 1234',
                    'email' => 'sales@alaminternational.ph',
                    'address_1' => 'Km 5 Cotabato-Davao Road',
                    'address_2' => 'Brgy. Rosary Heights',
                    'city' => 'Cotabato City',
                    'province' => 'Maguindanao del Norte',
                    'zip_code' => '9600',
                    'country_code' => 'PH',
                    'fda_lto_number' => 'LTO-2019-045821',
                    'average_production_lead_time' => 7,
                    'export_readiness_status' => 'exporting_regionally',
                    'value_chain_positions' => ['manufacturing', 'distribution'],
                    'capabilities' => ['processing', 'contract_manufacturing', 'private_label', 'exporting'],
                    'status' => 'active',
                ],
                'categories' => ['meat-poultry', 'frozen-ready-to-eat'],
            ],
            [
                'user' => [
                    'first_name' => 'Sittie',
                    'last_name' => 'Hadji Pangcatan',
                    'email' => 'sittie@goldenhalalfoods.ph',
                ],
                'shop' => [
                    'registered_business_name' => 'Golden Halal Foods Manufacturing Inc.',
                    'storefront_name' => 'Golden Halal Foods',
                    'business_structure' => 'corporation',
                    'dti_sec_number' => 'SEC-2015-00874',
                    'tax_identification_number' => '318-907-234-000',
                    'phone' => '+63 82 244 8800',
                    'email' => 'orders@goldenhalalfoods.ph',
                    'address_1' => 'Purok 12, Kilometer 7',
                    'address_2' => 'Mintal Industrial Zone',
                    'city' => 'Davao City',
                    'province' => 'Davao del Sur',
                    'zip_code' => '8000',
                    'country_code' => 'PH',
                    'fda_lto_number' => 'LTO-2016-028410',
                    'average_production_lead_time' => 5,
                    'export_readiness_status' => 'exporting_globally',
                    'value_chain_positions' => ['processing', 'manufacturing', 'distribution'],
                    'capabilities' => ['processing', 'packaging', 'contract_processing', 'oem_odm', 'private_label', 'exporting'],
                    'status' => 'active',
                ],
                'categories' => ['spices-seasonings', 'condiments-sauces', 'frozen-ready-to-eat'],
            ],
            [
                'user' => [
                    'first_name' => 'Nur-Aisha',
                    'last_name' => 'Abubakar',
                    'email' => 'nuraisha@mindanaoorganic.ph',
                ],
                'shop' => [
                    'registered_business_name' => 'Mindanao Organic Grains & Spice Co.',
                    'storefront_name' => 'Mindanao Organic Grains',
                    'business_structure' => 'partnership',
                    'dti_sec_number' => 'DTI-XI-2017-04321',
                    'tax_identification_number' => '226-544-110-000',
                    'phone' => '+63 83 228 6600',
                    'email' => 'info@mindanaoorganic.ph',
                    'address_1' => 'Maharlika Highway, Barangay Poblacion',
                    'city' => 'General Santos City',
                    'province' => 'South Cotabato',
                    'zip_code' => '9500',
                    'country_code' => 'PH',
                    'fda_lto_number' => 'LTO-2018-061233',
                    'average_production_lead_time' => 14,
                    'export_readiness_status' => 'exporting_regionally',
                    'value_chain_positions' => ['farm', 'processing', 'distribution'],
                    'capabilities' => ['processing', 'packaging', 'wholesale', 'exporting'],
                    'status' => 'active',
                ],
                'categories' => ['grains-rice-legumes', 'spices-seasonings'],
            ],
            [
                'user' => [
                    'first_name' => 'Mohammed',
                    'last_name' => 'Alonto',
                    'email' => 'mohammed@lanahalalsnacks.ph',
                ],
                'shop' => [
                    'registered_business_name' => 'Lanao Halal Snacks & Confectionery Corp.',
                    'storefront_name' => 'Lanao Halal Snacks',
                    'business_structure' => 'corporation',
                    'dti_sec_number' => 'SEC-2020-01095',
                    'tax_identification_number' => '509-381-762-000',
                    'phone' => '+63 63 221 7090',
                    'email' => 'trade@lanahalalsnacks.ph',
                    'address_1' => 'National Highway, Puga-an District',
                    'city' => 'Marawi City',
                    'province' => 'Lanao del Sur',
                    'zip_code' => '9700',
                    'country_code' => 'PH',
                    'fda_lto_number' => 'LTO-2021-082045',
                    'average_production_lead_time' => 10,
                    'export_readiness_status' => 'never_exported',
                    'value_chain_positions' => ['manufacturing', 'retail'],
                    'capabilities' => ['processing', 'packaging', 'private_label', 'wholesale'],
                    'status' => 'active',
                ],
                'categories' => ['snacks-confectionery', 'beverages'],
            ],
            [
                'user' => [
                    'first_name' => 'Jamal',
                    'last_name' => 'Disomimba',
                    'email' => 'jamal@zamboangahalalagri.ph',
                ],
                'shop' => [
                    'registered_business_name' => 'Zamboanga Halal Agri-Ventures Inc.',
                    'storefront_name' => 'ZamHalal Agri-Ventures',
                    'business_structure' => 'corporation',
                    'dti_sec_number' => 'SEC-2013-00344',
                    'tax_identification_number' => '178-622-905-000',
                    'phone' => '+63 62 993 1122',
                    'email' => 'export@zamboangahalalagri.ph',
                    'address_1' => 'Gov. Camins Avenue',
                    'address_2' => 'Zone 4, Sta. Maria',
                    'city' => 'Zamboanga City',
                    'province' => 'Zamboanga del Sur',
                    'zip_code' => '7000',
                    'country_code' => 'PH',
                    'fda_lto_number' => 'LTO-2014-019987',
                    'average_production_lead_time' => 21,
                    'export_readiness_status' => 'exporting_globally',
                    'value_chain_positions' => ['farm', 'processing', 'manufacturing', 'distribution', 'exporting'],
                    'capabilities' => ['processing', 'contract_processing', 'packaging', 'cold_storage', 'warehousing', 'exporting'],
                    'status' => 'active',
                ],
                'categories' => ['dairy-eggs-alternatives', 'beverages', 'grains-rice-legumes'],
            ],
        ];

        $shops = [];

        foreach ($companies as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['user']['email']],
                [
                    'first_name' => $data['user']['first_name'],
                    'last_name' => $data['user']['last_name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );

            /** @var Shop $shop */
            $shop = Shop::firstOrCreate(
                ['email' => $data['shop']['email']],
                array_merge($data['shop'], ['user_id' => $user->id])
            );

            $user->assignRole('seller');

            foreach ($data['categories'] as $slug) {
                $categoryId = Category::where('slug', $slug)->value('id');
                if ($categoryId && ! $shop->categories()->where('category_id', $categoryId)->exists()) {
                    $shop->categories()->attach($categoryId);
                }
            }

            $shops[] = $shop;
        }

        return $shops;
    }

    /**
     * Merge schemas from multiple category slugs into a single product schema.
     * Deduplicates by key — first occurrence wins.
     *
     * @param  string[]  $categorySlugs
     * @param  array<string, Category>  $categoryMap
     * @return array<string, mixed>
     */
    private function mergedSchema(array $categorySlugs, array $categoryMap): array
    {
        $merged = [];

        foreach ($categorySlugs as $slug) {
            $schema = $categoryMap[$slug]?->variant_attributes_schema
                ?? Category::where('slug', $slug)->value('variant_attributes_schema')
                ?? [];

            foreach ($schema as $key => $def) {
                $merged[$key] ??= $def;
            }
        }

        return $merged;
    }

    /**
     * @param  array<int, Shop>  $companies
     * @param  array<string, Category>  $categoryMap
     */
    private function seedProducts(array $companies, array $categoryMap): void
    {
        [$alAmin, $golden, $mindanao, $lanao, $zamboanga] = $companies;

        $products = [

            // ----------------------------------------------------------------
            // Al-Amin International Foods — Meat & Poultry / Processed Foods
            // ----------------------------------------------------------------
            [
                'shop' => $alAmin,
                'product' => [
                    'name' => 'Halal Frozen Whole Chicken',
                    'description' => 'Fresh-processed, halal-certified whole broiler chickens, individually vacuum-sealed and blast-frozen at -18°C. Raised without antibiotics, hand-slaughtered following IDCP halal protocols. Ideal for food service, hotels, restaurants, and institutional buyers.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['meat-poultry'],
                'variants' => [
                    [
                        'sku' => 'ALA-CHK-1200',
                        'description' => '1.2 kg average weight per bird',
                        'attributes' => [
                            'attr_cut' => 'Whole',
                            'attr_weight' => '1.2 kg avg',
                            'attr_packaging' => 'Vacuum-sealed, 10 per carton',
                        ],
                        'price' => 220.00,
                        'minimum_order_quantity' => 100,
                        'unit' => 'piece',
                        'currency' => 'PHP',
                        'available_quantity' => 5000,
                        'lead_time_days' => 3,
                        'pack_size' => '10 pieces per carton',
                        'quantity_breaks' => [
                            ['min_qty' => 500, 'price' => 210.00],
                            ['min_qty' => 1000, 'price' => 198.00],
                        ],
                        'status' => 'published',
                    ],
                    [
                        'sku' => 'ALA-CHK-1800',
                        'description' => '1.8 kg average weight per bird',
                        'attributes' => [
                            'attr_cut' => 'Whole',
                            'attr_weight' => '1.8 kg avg',
                            'attr_packaging' => 'Vacuum-sealed, 8 per carton',
                        ],
                        'price' => 320.00,
                        'minimum_order_quantity' => 50,
                        'unit' => 'piece',
                        'currency' => 'PHP',
                        'available_quantity' => 3000,
                        'lead_time_days' => 3,
                        'pack_size' => '8 pieces per carton',
                        'quantity_breaks' => [
                            ['min_qty' => 200, 'price' => 308.00],
                            ['min_qty' => 500, 'price' => 295.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],
            [
                'shop' => $alAmin,
                'product' => [
                    'name' => 'Halal Beef Tapa (Thinly Sliced)',
                    'description' => 'Traditional Filipino beef tapa made from premium halal-certified Brahman beef, marinated in a blend of garlic, soy sauce, calamansi, and muscovado sugar. Ready to pan-fry or grill. Vacuum-packed for extended shelf life.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['meat-poultry', 'frozen-ready-to-eat'],
                'variants' => [
                    [
                        'sku' => 'ALA-TAPA-500G',
                        'description' => '500g retail vacuum pack',
                        'attributes' => [
                            'attr_cut' => 'Boneless',
                            'attr_weight' => '500g',
                            'attr_packaging' => 'Vacuum-sealed',
                            'attr_shelf_life' => '21 days refrigerated',
                        ],
                        'price' => 280.00,
                        'minimum_order_quantity' => 50,
                        'unit' => 'pack',
                        'currency' => 'PHP',
                        'available_quantity' => 2000,
                        'lead_time_days' => 5,
                        'pack_size' => '500g per pack, 20 packs per carton',
                        'quantity_breaks' => [
                            ['min_qty' => 200, 'price' => 265.00],
                            ['min_qty' => 500, 'price' => 250.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],

            // ----------------------------------------------------------------
            // Golden Halal Foods — Spices & Condiments
            // ----------------------------------------------------------------
            [
                'shop' => $golden,
                'product' => [
                    'name' => 'Satti Spice Blend (Halal)',
                    'description' => 'Authentic Mindanao satti spice blend — a proprietary mix of roasted coriander, cumin, turmeric, chili, galangal, and lemongrass. Certified by IDCP. Available in bulk bags for food manufacturers and restaurant chains.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['spices-seasonings'],
                'variants' => [
                    [
                        'sku' => 'GHF-SATTI-1KG',
                        'description' => '1 kg re-sealable foil bag',
                        'attributes' => [
                            'attr_weight' => '1 kg',
                            'attr_grade' => 'Food Grade',
                            'attr_packaging' => 'Foil bag, re-sealable',
                        ],
                        'price' => 480.00,
                        'minimum_order_quantity' => 20,
                        'unit' => 'bag',
                        'currency' => 'PHP',
                        'available_quantity' => 3000,
                        'lead_time_days' => 7,
                        'pack_size' => '1 kg bag, 10 bags per master carton',
                        'quantity_breaks' => [
                            ['min_qty' => 100, 'price' => 455.00],
                            ['min_qty' => 500, 'price' => 420.00],
                        ],
                        'status' => 'published',
                    ],
                    [
                        'sku' => 'GHF-SATTI-25KG',
                        'description' => '25 kg bulk sack for food manufacturers',
                        'attributes' => [
                            'attr_weight' => '25 kg',
                            'attr_grade' => 'Food Grade',
                            'attr_packaging' => 'Multi-wall kraft sack',
                        ],
                        'price' => 9800.00,
                        'minimum_order_quantity' => 5,
                        'unit' => 'sack',
                        'currency' => 'PHP',
                        'available_quantity' => 500,
                        'lead_time_days' => 7,
                        'pack_size' => '25 kg sack',
                        'quantity_breaks' => [
                            ['min_qty' => 20, 'price' => 9200.00],
                            ['min_qty' => 50, 'price' => 8700.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],
            [
                'shop' => $golden,
                'product' => [
                    'name' => 'Halal Banana Ketchup (Bulk)',
                    'description' => 'A uniquely Filipino condiment — sweet banana ketchup made from ripe Cavendish bananas, vinegar, onion, garlic, and spices. No artificial colors, halal-certified. Used in quick-service restaurants, school canteens, and institutional food service.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1606914501449-5a96b6ce24ca?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['condiments-sauces'],
                'variants' => [
                    [
                        'sku' => 'GHF-BKETCH-3KG',
                        'description' => '3 kg institutional jar',
                        'attributes' => [
                            'attr_weight' => '3 kg',
                            'attr_packaging' => 'PET jar with tamper-evident seal',
                            'attr_heat_level' => 'Mild',
                        ],
                        'price' => 185.00,
                        'minimum_order_quantity' => 48,
                        'unit' => 'jar',
                        'currency' => 'PHP',
                        'available_quantity' => 8000,
                        'lead_time_days' => 5,
                        'pack_size' => '3 kg jar, 6 per carton',
                        'quantity_breaks' => [
                            ['min_qty' => 240, 'price' => 175.00],
                            ['min_qty' => 960, 'price' => 162.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],

            // ----------------------------------------------------------------
            // Mindanao Organic Grains — Grains & Spices
            // ----------------------------------------------------------------
            [
                'shop' => $mindanao,
                'product' => [
                    'name' => 'Organic Brown Rice (Mindanao Upland)',
                    'description' => 'Single-origin organic brown rice harvested from the upland rice terraces of Bukidnon and Cotabato. Naturally gluten-free, whole-grain, with a nutty aroma. No synthetic pesticides. Halal-certified through IDCP.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1505253304499-671c55fb57fe?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['grains-rice-legumes'],
                'variants' => [
                    [
                        'sku' => 'MOG-BRICE-5KG',
                        'description' => '5 kg retail bag',
                        'attributes' => [
                            'attr_weight' => '5 kg',
                            'attr_grade' => 'Organic',
                            'attr_milling' => 'Light Bran',
                        ],
                        'price' => 420.00,
                        'minimum_order_quantity' => 50,
                        'unit' => 'bag',
                        'currency' => 'PHP',
                        'available_quantity' => 10000,
                        'lead_time_days' => 14,
                        'pack_size' => '5 kg woven bag with inner liner',
                        'quantity_breaks' => [
                            ['min_qty' => 200, 'price' => 398.00],
                            ['min_qty' => 1000, 'price' => 375.00],
                        ],
                        'status' => 'published',
                    ],
                    [
                        'sku' => 'MOG-BRICE-50KG',
                        'description' => '50 kg bulk sack',
                        'attributes' => [
                            'attr_weight' => '50 kg',
                            'attr_grade' => 'Food Grade',
                            'attr_milling' => 'Light Bran',
                        ],
                        'price' => 3800.00,
                        'minimum_order_quantity' => 10,
                        'unit' => 'sack',
                        'currency' => 'PHP',
                        'available_quantity' => 2000,
                        'lead_time_days' => 14,
                        'pack_size' => '50 kg jute sack',
                        'quantity_breaks' => [
                            ['min_qty' => 50, 'price' => 3600.00],
                            ['min_qty' => 100, 'price' => 3400.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],
            [
                'shop' => $mindanao,
                'product' => [
                    'name' => 'Turmeric Powder (Luyang Dilaw)',
                    'description' => 'Sun-dried and stone-ground turmeric (Curcuma longa) from organically cultivated farms in North Cotabato. High curcumin content (>3%). Halal-certified; COA and moisture analysis available on request.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1615485500704-8e990f9900f7?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['spices-seasonings'],
                'variants' => [
                    [
                        'sku' => 'MOG-TURM-1KG',
                        'description' => '1 kg fine powder, food grade',
                        'attributes' => [
                            'attr_weight' => '1 kg',
                            'attr_grade' => 'Organic',
                            'attr_packaging' => '1 kg heat-sealed pouch',
                        ],
                        'price' => 560.00,
                        'minimum_order_quantity' => 25,
                        'unit' => 'bag',
                        'currency' => 'PHP',
                        'available_quantity' => 5000,
                        'lead_time_days' => 10,
                        'pack_size' => '1 kg heat-sealed pouch',
                        'quantity_breaks' => [
                            ['min_qty' => 100, 'price' => 520.00],
                            ['min_qty' => 500, 'price' => 480.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],

            // ----------------------------------------------------------------
            // Lanao Halal Snacks — Snacks & Beverages
            // ----------------------------------------------------------------
            [
                'shop' => $lanao,
                'product' => [
                    'name' => 'Maranao Peanut Brittle (Ampao)',
                    'description' => 'Traditional Maranao ampao — peanut brittle made from locally sourced roasted peanuts, muscovado sugar, and coconut oil. No artificial preservatives. Halal-certified by IDMA. A popular pasalubong item for regional and national distribution.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1621939514649-280e2ee25f60?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['snacks-confectionery'],
                'variants' => [
                    [
                        'sku' => 'LHS-AMPAO-200G',
                        'description' => '200g gift box (12 pieces)',
                        'attributes' => [
                            'attr_weight' => '200g',
                            'attr_count' => '12 pieces',
                            'attr_packaging' => 'Gift Box',
                        ],
                        'price' => 95.00,
                        'minimum_order_quantity' => 100,
                        'unit' => 'box',
                        'currency' => 'PHP',
                        'available_quantity' => 15000,
                        'lead_time_days' => 7,
                        'pack_size' => '200g box, 24 boxes per master carton',
                        'quantity_breaks' => [
                            ['min_qty' => 500, 'price' => 88.00],
                            ['min_qty' => 2000, 'price' => 80.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],
            [
                'shop' => $lanao,
                'product' => [
                    'name' => 'Halal Coconut Water (Bottled)',
                    'description' => 'Pure, not-from-concentrate coconut water sourced from young Matutum coconuts in South Cotabato. No added sugar, preservatives, or artificial flavors. Cold-filled in BPA-free PET bottles. BFAD/FDA registered, halal-certified.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['beverages'],
                'variants' => [
                    [
                        'sku' => 'LHS-COCOWATER-330ML',
                        'description' => '330ml single-serve bottle',
                        'attributes' => [
                            'attr_volume' => '330 ml',
                            'attr_packaging' => 'PET Bottle',
                        ],
                        'price' => 42.00,
                        'minimum_order_quantity' => 240,
                        'unit' => 'bottle',
                        'currency' => 'PHP',
                        'available_quantity' => 50000,
                        'lead_time_days' => 10,
                        'pack_size' => '330ml, 24 bottles per carton',
                        'quantity_breaks' => [
                            ['min_qty' => 1200, 'price' => 38.00],
                            ['min_qty' => 4800, 'price' => 35.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],

            // ----------------------------------------------------------------
            // Zamboanga Halal Agri-Ventures — Processed Foods & Beverages
            // ----------------------------------------------------------------
            [
                'shop' => $zamboanga,
                'product' => [
                    'name' => 'Zamboanga Sardines in Olive Oil (Halal)',
                    'description' => 'Wild-caught Pacific sardines from the Sulu Sea, packed in premium extra-virgin olive oil with sea salt and bay leaf. Processed in a halal-certified fish canning facility. No preservatives, no MSG.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1574680096145-d05b474e2155?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['frozen-ready-to-eat'],
                'variants' => [
                    [
                        'sku' => 'ZHA-SARD-155G',
                        'description' => '155g retail can',
                        'attributes' => [
                            'attr_weight' => '155g',
                            'attr_packaging' => 'EZO tin can',
                            'attr_shelf_life' => '3 years',
                        ],
                        'price' => 68.00,
                        'minimum_order_quantity' => 500,
                        'unit' => 'can',
                        'currency' => 'PHP',
                        'available_quantity' => 100000,
                        'lead_time_days' => 21,
                        'pack_size' => '155g can, 50 per master carton',
                        'quantity_breaks' => [
                            ['min_qty' => 2500, 'price' => 63.00],
                            ['min_qty' => 10000, 'price' => 57.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],
            [
                'shop' => $zamboanga,
                'product' => [
                    'name' => 'Calamansi Concentrate (Halal)',
                    'description' => 'Cold-pressed, minimally processed calamansi (Philippine lime) juice concentrate. 5:1 concentration ratio. Sourced from contract farms in Zamboanga Peninsula. No added sugar. Suitable for beverage manufacturers and export markets.',
                    'featured_image_path' => 'https://images.unsplash.com/photo-1587496679742-bad502958fbf?w=800&q=80',
                    'status' => 'published',
                ],
                'categories' => ['beverages'],
                'variants' => [
                    [
                        'sku' => 'ZHA-CALAMANSI-1L',
                        'description' => '1 liter Tetra Pak',
                        'attributes' => [
                            'attr_volume' => '1 L',
                            'attr_packaging' => 'Tetra Pak',
                            'attr_concentration' => '5:1',
                        ],
                        'price' => 320.00,
                        'minimum_order_quantity' => 48,
                        'unit' => 'carton',
                        'currency' => 'PHP',
                        'available_quantity' => 20000,
                        'lead_time_days' => 14,
                        'pack_size' => '1L Tetra Pak, 12 per carton',
                        'quantity_breaks' => [
                            ['min_qty' => 240, 'price' => 298.00],
                            ['min_qty' => 960, 'price' => 275.00],
                        ],
                        'status' => 'published',
                    ],
                ],
            ],
        ];

        foreach ($products as $data) {
            // Build merged schema from the product's categories
            $schema = $this->mergedSchema($data['categories'], $categoryMap);

            /** @var Product $product */
            $product = Product::firstOrCreate(
                [
                    'shop_id' => $data['shop']->id,
                    'name' => $data['product']['name'],
                ],
                array_merge($data['product'], [
                    'shop_id' => $data['shop']->id,
                    'variant_attributes_schema' => $schema,
                ])
            );

            // Update schema on existing rows (safe on re-seed).
            if (empty($product->variant_attributes_schema)) {
                $product->update(['variant_attributes_schema' => $schema]);
            }

            // Attach product categories
            foreach ($data['categories'] as $slug) {
                $categoryId = $categoryMap[$slug]?->id ?? null;
                if ($categoryId && ! $product->categories()->where('category_id', $categoryId)->exists()) {
                    $product->categories()->attach($categoryId);
                }
            }

            // Create variants — attributes keyed to match the product schema
            foreach ($data['variants'] as $variantData) {
                ProductVariant::firstOrCreate(
                    ['sku' => $variantData['sku']],
                    array_merge($variantData, ['product_id' => $product->id])
                );
            }
        }
    }
}
