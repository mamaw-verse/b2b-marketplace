<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CategorySeeder extends Seeder
{
    /**
     * Seed the canonical category tree: top-level groups with their
     * subcategories. Rebuilds the table from scratch — the truncate cascades
     * to the product_categories / shop_categories pivots, so re-seeding also
     * clears stale category assignments.
     */
    public function run(): void
    {
        DB::table('categories')->truncate();

        $schemas = $this->categorySchemas();

        foreach (self::tree() as $groupIndex => $groupDef) {
            $group = Category::create([
                'name' => $groupDef['name'],
                'slug' => Str::slug($groupDef['name']),
                'description' => $groupDef['description'],
                'sort_order' => $groupIndex + 1,
                'variant_attributes_schema' => [],
            ]);

            foreach ($groupDef['children'] as $childIndex => $childDef) {
                Category::create([
                    'name' => $childDef['name'],
                    'slug' => $slug = Str::slug($childDef['name']),
                    'description' => $childDef['description'] ?? null,
                    'parent_id' => $group->id,
                    'sort_order' => $childIndex + 1,
                    'variant_attributes_schema' => $schemas[$slug] ?? [],
                ]);
            }
        }
    }

    /**
     * The canonical 2-level taxonomy: top-level groups with subcategories.
     *
     * @return array<int, array{name: string, description: string, children: array<int, array{name: string, description?: string}>}>
     */
    public static function tree(): array
    {
        return [
            [
                'name' => 'Foods',
                'description' => 'Halal food and beverage products across the supply chain.',
                'children' => [
                    ['name' => 'Meat & Poultry', 'description' => 'Halal-certified beef, chicken, goat, and other meats for food service and retail.'],
                    ['name' => 'Seafood', 'description' => 'Fish, shrimp, and other permissible seafood products.'],
                    ['name' => 'Fresh Produce', 'description' => 'Fruits and vegetables for wholesale and food service.'],
                    ['name' => 'Grains, Rice & Legumes', 'description' => 'Rice, wheat, dried beans, lentils, and other commodity grains.'],
                    ['name' => 'Dairy, Eggs & Alternatives', 'description' => 'Halal dairy products, eggs, and plant-based alternatives.'],
                    ['name' => 'Bakery, Flour & Noodles', 'description' => 'Bakery products, flours, noodles, and baking mixes.'],
                    ['name' => 'Oils, Sugar & Sweeteners', 'description' => 'Cooking oils, sugar, honey, syrups, and sweeteners.'],
                    ['name' => 'Spices & Seasonings', 'description' => 'Halal spice blends, marinades, and seasonings for food manufacturing.'],
                    ['name' => 'Condiments & Sauces', 'description' => 'Halal soy sauce, vinegar, chili sauces, and cooking pastes.'],
                    ['name' => 'Beverages', 'description' => 'Halal-certified juices, teas, energy drinks, and bottled water.'],
                    ['name' => 'Snacks & Confectionery', 'description' => 'Halal chips, nuts, biscuits, candies, and chocolate products.'],
                    ['name' => 'Frozen & Ready-to-Eat', 'description' => 'Ready-to-eat and ready-to-cook halal frozen and processed food products.'],
                    ['name' => 'Food Ingredients & Additives', 'description' => 'Functional ingredients, additives, and processing aids for food manufacturing.'],
                ],
            ],
            [
                'name' => 'Non-Foods',
                'description' => 'Halal-compliant non-food consumer and industrial products.',
                'children' => [
                    ['name' => 'Cosmetics & Personal Care', 'description' => 'Halal-certified cosmetics, skincare, and personal care products.'],
                    ['name' => 'Pharma & Supplements', 'description' => 'Halal pharmaceuticals, vitamins, and dietary supplements.'],
                    ['name' => 'Household & Cleaning', 'description' => 'Halal-compliant household and cleaning products.'],
                    ['name' => 'Modest Fashion & Prayer Items', 'description' => 'Modest apparel, hijabs, prayer garments, and prayer accessories.'],
                    ['name' => 'Packaging Materials', 'description' => 'Halal-compliant packaging materials for food and consumer goods.'],
                    ['name' => 'Equipment & Machinery', 'description' => 'Food processing, manufacturing, and industrial equipment.'],
                    ['name' => 'Foodservice & Hospitality Supplies', 'description' => 'Supplies for restaurants, hotels, and catering businesses.'],
                    ['name' => 'Pet Supplies', 'description' => 'Pet food, accessories, and care products.'],
                    ['name' => 'Appliances & Cooking Utensils', 'description' => 'Kitchen appliances, cookware, and utensils.'],
                    ['name' => 'Furniture', 'description' => 'Home, office, and hospitality furniture.'],
                    ['name' => 'Automotive', 'description' => 'Vehicles, parts, and automotive accessories.'],
                    ['name' => 'Jewelry & Watches', 'description' => 'Jewelry, watches, and accessories.'],
                    ['name' => 'Toys', 'description' => 'Toys and children\'s products.'],
                    ['name' => 'Bags, Shoes & Apparel', 'description' => 'Bags, footwear, and general apparel.'],
                    ['name' => 'Sports', 'description' => 'Sports equipment, apparel, and accessories.'],
                    ['name' => 'Electronic Equipment & Gadgets', 'description' => 'Consumer electronics, gadgets, and accessories.'],
                ],
            ],
            [
                'name' => 'Services',
                'description' => 'Halal supply-chain and professional services.',
                'children' => [
                    ['name' => 'Halal Certification & Audit', 'description' => 'Halal certification, audit, and compliance assessment services.'],
                    ['name' => 'Lab Testing', 'description' => 'Laboratory testing for halal integrity, safety, and quality.'],
                    ['name' => 'Training & Consultancy', 'description' => 'Halal training, capacity building, and consultancy services.'],
                    ['name' => 'Cold Chain & Transport', 'description' => 'Temperature-controlled logistics and transport services.'],
                    ['name' => 'Warehousing & Cold Storage', 'description' => 'Halal-segregated warehousing and cold storage facilities.'],
                    ['name' => 'Export, Freight & Customs', 'description' => 'Freight forwarding, customs brokerage, and export services.'],
                    ['name' => 'Halal Slaughter Services', 'description' => 'Certified halal slaughtering and processing services.'],
                    ['name' => 'Packaging & Labeling', 'description' => 'Packaging, labeling, and printing services.'],
                ],
            ],
            [
                'name' => 'Agriculture & Raw Materials',
                'description' => 'Farm inputs, livestock, and unprocessed raw materials.',
                'children' => [
                    ['name' => 'Livestock & Poultry (live)', 'description' => 'Live halal livestock and poultry for farms and processors.'],
                    ['name' => 'Aquaculture & Fisheries', 'description' => 'Farmed fish, seafood, and aquaculture products.'],
                    ['name' => 'Crops & Farm Produce (bulk)', 'description' => 'Bulk crops and unprocessed farm produce.'],
                    ['name' => 'Animal Feed', 'description' => 'Halal-compliant animal feed and feed ingredients.'],
                    ['name' => 'Seeds, Fertilizer & Farm Inputs', 'description' => 'Seeds, fertilizers, and other agricultural inputs.'],
                ],
            ],
            [
                'name' => 'Business & Community',
                'description' => 'Halal economy business services, events, and community offerings.',
                'children' => [
                    ['name' => 'Business Matching & Partnerships', 'description' => 'B2B matching, partnerships, and trade facilitation.'],
                    ['name' => 'Events, Expos & Trade Missions', 'description' => 'Halal trade events, expos, and trade missions.'],
                    ['name' => 'Halal Franchise & Investment', 'description' => 'Franchise opportunities and halal investment offerings.'],
                    ['name' => 'Halal Business Consultancy', 'description' => 'Business advisory for halal market entry and growth.'],
                    ['name' => 'Halal IT & Software Development', 'description' => 'Software and IT services for the halal economy.'],
                    ['name' => 'Halal Tourism & Hospitality', 'description' => 'Halal-friendly hotels, restaurants, and travel services.'],
                    ['name' => 'Halal Finance & Takaful', 'description' => 'Islamic finance, takaful, and related financial services.'],
                    ['name' => 'Halal Cruises', 'description' => 'Halal-certified cruise and leisure travel offerings.'],
                    ['name' => 'Hajj & Umrah', 'description' => 'Hajj and Umrah travel packages and services.'],
                    ['name' => 'Zakat & Sadaqah', 'description' => 'Zakat and sadaqah collection and distribution services.'],
                    ['name' => 'Others', 'description' => 'Other halal economy offerings not listed above.'],
                ],
            ],
        ];
    }

    /**
     * Canonical variant attribute schemas, keyed by category slug.
     * Key format: attr_<slug> — stable, reproducible, matches admin editor convention.
     * "options" is always a comma-separated string (not an array).
     *
     * @return array<string, array<string, array{label: string, type: string, options: string, required: bool}>>
     */
    private function categorySchemas(): array
    {
        return [
            'meat-poultry' => [
                'attr_cut' => [
                    'label' => 'Cut',
                    'type' => 'select',
                    'options' => 'Whole,Breast,Thigh,Drumstick,Wing,Bone-in,Boneless,Ground',
                    'required' => true,
                ],
                'attr_weight' => [
                    'label' => 'Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'seafood' => [
                'attr_form' => [
                    'label' => 'Form',
                    'type' => 'select',
                    'options' => 'Whole,Fillet,Steak,Value-added,Dried',
                    'required' => true,
                ],
                'attr_weight' => [
                    'label' => 'Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'fresh-produce' => [
                'attr_weight' => [
                    'label' => 'Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_grade' => [
                    'label' => 'Grade',
                    'type' => 'select',
                    'options' => 'Premium,Class A,Class B',
                    'required' => false,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'grains-rice-legumes' => [
                'attr_weight' => [
                    'label' => 'Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_grade' => [
                    'label' => 'Grade',
                    'type' => 'select',
                    'options' => 'Premium,Food Grade,Organic,Export Grade',
                    'required' => false,
                ],
                'attr_milling' => [
                    'label' => 'Milling',
                    'type' => 'select',
                    'options' => 'Whole Grain,Light Bran,Fully Milled,Stone Ground',
                    'required' => false,
                ],
            ],
            'dairy-eggs-alternatives' => [
                'attr_volume' => [
                    'label' => 'Volume / Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_fat_content' => [
                    'label' => 'Fat Content',
                    'type' => 'select',
                    'options' => 'Full Fat,Reduced Fat,Low Fat,Skimmed',
                    'required' => false,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'bakery-flour-noodles' => [
                'attr_weight' => [
                    'label' => 'Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_type' => [
                    'label' => 'Type',
                    'type' => 'select',
                    'options' => 'Flour,Bread,Pastry,Noodles,Baking Mix',
                    'required' => false,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'oils-sugar-sweeteners' => [
                'attr_volume' => [
                    'label' => 'Volume / Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_grade' => [
                    'label' => 'Grade',
                    'type' => 'select',
                    'options' => 'Refined,Unrefined,Organic,Premium',
                    'required' => false,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'spices-seasonings' => [
                'attr_weight' => [
                    'label' => 'Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_grade' => [
                    'label' => 'Grade',
                    'type' => 'select',
                    'options' => 'Food Grade,Premium,Organic,Export Grade',
                    'required' => false,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'condiments-sauces' => [
                'attr_weight' => [
                    'label' => 'Weight / Volume',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
                'attr_heat_level' => [
                    'label' => 'Heat Level',
                    'type' => 'select',
                    'options' => 'Mild,Medium,Medium-hot,Hot,Extra Hot',
                    'required' => false,
                ],
            ],
            'beverages' => [
                'attr_volume' => [
                    'label' => 'Volume',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'select',
                    'options' => 'PET Bottle,Glass Bottle,Tetra Pak,Carton,Can,Pouch',
                    'required' => false,
                ],
                'attr_concentration' => [
                    'label' => 'Concentration',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'snacks-confectionery' => [
                'attr_weight' => [
                    'label' => 'Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_count' => [
                    'label' => 'Count / Pieces',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'select',
                    'options' => 'Retail Box,Pouch,Tin Can,Gift Box,Bulk Bag,Wrapper',
                    'required' => false,
                ],
            ],
            'frozen-ready-to-eat' => [
                'attr_weight' => [
                    'label' => 'Weight / Volume',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
                'attr_shelf_life' => [
                    'label' => 'Shelf Life',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'food-ingredients-additives' => [
                'attr_weight' => [
                    'label' => 'Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_grade' => [
                    'label' => 'Grade',
                    'type' => 'select',
                    'options' => 'Food Grade,Premium,Organic,Export Grade',
                    'required' => false,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'cosmetics-personal-care' => [
                'attr_volume' => [
                    'label' => 'Volume / Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_application' => [
                    'label' => 'Application',
                    'type' => 'select',
                    'options' => 'Face,Body,Hair,All Skin Types',
                    'required' => false,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'pharma-supplements' => [
                'attr_form' => [
                    'label' => 'Form',
                    'type' => 'select',
                    'options' => 'Capsule,Tablet,Syrup,Powder,Softgel',
                    'required' => true,
                ],
                'attr_count' => [
                    'label' => 'Count',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'household-cleaning' => [
                'attr_volume' => [
                    'label' => 'Volume / Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_type' => [
                    'label' => 'Type',
                    'type' => 'select',
                    'options' => 'Liquid,Powder,Spray,Wipes',
                    'required' => false,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'modest-fashion-prayer-items' => [
                'attr_size' => [
                    'label' => 'Size',
                    'type' => 'select',
                    'options' => 'XS,S,M,L,XL,XXL,Free Size',
                    'required' => true,
                ],
                'attr_material' => [
                    'label' => 'Material',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
                'attr_color' => [
                    'label' => 'Color',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'packaging-materials' => [
                'attr_material' => [
                    'label' => 'Material',
                    'type' => 'select',
                    'options' => 'Paper,Plastic,Glass,Metal,Biodegradable',
                    'required' => true,
                ],
                'attr_size' => [
                    'label' => 'Size',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
                'attr_quantity' => [
                    'label' => 'Quantity',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'pet-supplies' => [
                'attr_weight' => [
                    'label' => 'Weight / Volume',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_type' => [
                    'label' => 'Type',
                    'type' => 'select',
                    'options' => 'Food,Accessories,Hygiene,Toys',
                    'required' => false,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'livestock-poultry-live' => [
                'attr_breed' => [
                    'label' => 'Breed',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
                'attr_age' => [
                    'label' => 'Age',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
                'attr_weight' => [
                    'label' => 'Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
            ],
            'aquaculture-fisheries' => [
                'attr_species' => [
                    'label' => 'Species',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_form' => [
                    'label' => 'Form',
                    'type' => 'select',
                    'options' => 'Live,Fresh-Chilled,Frozen,Dried',
                    'required' => false,
                ],
                'attr_weight' => [
                    'label' => 'Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'crops-farm-produce-bulk' => [
                'attr_weight' => [
                    'label' => 'Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_grade' => [
                    'label' => 'Grade',
                    'type' => 'select',
                    'options' => 'Premium,Class A,Class B',
                    'required' => false,
                ],
                'attr_harvest_season' => [
                    'label' => 'Harvest Season',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'animal-feed' => [
                'attr_weight' => [
                    'label' => 'Weight',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_type' => [
                    'label' => 'Type',
                    'type' => 'select',
                    'options' => 'Pellet,Mash,Crumbles',
                    'required' => false,
                ],
                'attr_for' => [
                    'label' => 'For',
                    'type' => 'select',
                    'options' => 'Poultry,Livestock,Aquaculture,Pets',
                    'required' => false,
                ],
            ],
            'seeds-fertilizer-farm-inputs' => [
                'attr_weight' => [
                    'label' => 'Weight / Volume',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_type' => [
                    'label' => 'Type',
                    'type' => 'select',
                    'options' => 'Seeds,Fertilizer,Pesticide,Feed,Tools',
                    'required' => false,
                ],
                'attr_packaging' => [
                    'label' => 'Packaging',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'training-consultancy' => [
                'attr_delivery_mode' => [
                    'label' => 'Delivery Mode',
                    'type' => 'select',
                    'options' => 'On-site,Online,Hybrid',
                    'required' => true,
                ],
                'attr_duration' => [
                    'label' => 'Duration',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
            'lab-testing' => [
                'attr_test_type' => [
                    'label' => 'Test Type',
                    'type' => 'text',
                    'options' => '',
                    'required' => true,
                ],
                'attr_turnaround' => [
                    'label' => 'Turnaround Time',
                    'type' => 'text',
                    'options' => '',
                    'required' => false,
                ],
            ],
        ];
    }
}
