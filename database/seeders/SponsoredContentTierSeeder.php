<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SponsoredContentTier;
use Illuminate\Database\Seeder;

final class SponsoredContentTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['name' => 'Gold',   'priority_weight' => 100, 'is_active' => true],
            ['name' => 'Silver', 'priority_weight' => 50,  'is_active' => true],
            ['name' => 'Bronze', 'priority_weight' => 25,  'is_active' => true],
        ];

        foreach ($tiers as $tier) {
            SponsoredContentTier::firstOrCreate(
                ['name' => $tier['name']],
                $tier,
            );
        }
    }
}
