<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CategorySeeder::class,
            // DemoDataSeeder::class,
            // DemoUsersSeeder::class,
            SubscriptionPlanSeeder::class,
            // DemoSubscriptionSeeder::class,   // Phase 6 — give seeded shops a free trial
            SponsoredContentTierSeeder::class,
            // SponsoredContentSeeder::class,
            // Phase3Seeder and PayoutSettingsSeeder are preserved in the codebase
            // but excluded from the default seed — orders/payouts are disabled in
            // Phase 6. Re-add them manually when testing transactional features.
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@1halalstore.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email_verified_at' => now(),
                'password' => Hash::make('j0xzB2T7MdI2'),
            ]
        );
        $admin->assignRole('admin');
    }
}
