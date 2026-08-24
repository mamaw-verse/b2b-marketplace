<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the roles and permissions per design.md §5.4.
     *
     * Roles: admin, seller, buyer
     * Permissions:
     *   - admin: onboarding.review, category.manage, document.view_any
     *   - seller: shop.manage_own, product.manage_own
     *   - buyer: directory.browse (implicit/public)
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'onboarding.review',
            'category.manage',
            'document.view_any',
            'shop.manage_own',
            'product.manage_own',
            'directory.browse',
            'payout.release',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'onboarding.review',
            'category.manage',
            'document.view_any',
            'payout.release',
        ]);

        $seller = Role::firstOrCreate(['name' => 'seller', 'guard_name' => 'web']);
        $seller->syncPermissions([
            'shop.manage_own',
            'product.manage_own',
        ]);

        $buyer = Role::firstOrCreate(['name' => 'buyer', 'guard_name' => 'web']);
        $buyer->syncPermissions([
            'directory.browse',
        ]);
    }
}
