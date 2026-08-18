<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\OnboardingStatusChanged;
use App\Jobs\IndexShopJob;
use App\Models\Shop;
use App\Models\ShopCertificate;
use App\Models\ShopOnboarding;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ApproveOnboardingAction
{
    /**
     * Approve an onboarding, creating the shop and copying associated records.
     */
    public function execute(ShopOnboarding $onboarding, User $admin): Shop
    {
        if ($onboarding->status === 'approved') {
            throw new RuntimeException('Onboarding has already been approved.');
        }

        $shop = DB::transaction(function () use ($onboarding, $admin): Shop {
            // Create shop from onboarding fields
            $shop = Shop::create([
                'registered_business_name' => $onboarding->registered_business_name,
                'storefront_name' => $onboarding->storefront_name,
                'business_structure' => $onboarding->business_structure,
                'dti_sec_number' => $onboarding->dti_sec_number,
                'tax_identification_number' => $onboarding->tax_identification_number,
                'phone' => $onboarding->phone,
                'email' => $onboarding->email,
                'address_1' => $onboarding->address_1,
                'address_2' => $onboarding->address_2,
                'city' => $onboarding->city,
                'province' => $onboarding->province,
                'zip_code' => $onboarding->zip_code,
                'country_code' => $onboarding->country_code,
                'fda_lto_number' => $onboarding->fda_lto_number,
                'average_production_lead_time' => $onboarding->average_production_lead_time,
                'export_readiness_status' => $onboarding->export_readiness_status,
                'value_chain_positions' => $onboarding->value_chain_positions,
                'capabilities' => $onboarding->capabilities,
                'user_id' => $onboarding->user_id,
                'status' => 'active',
            ]);

            // Copy certificates → shop_certificates (set is_verified = true)
            foreach ($onboarding->certificates as $cert) {
                ShopCertificate::create([
                    'shop_id' => $shop->id,
                    'certification_body' => $cert->certification_body,
                    'certification_body_other' => $cert->certification_body_other,
                    'registration_number' => $cert->registration_number,
                    'expiry_date' => $cert->expiry_date,
                    'status' => 'active',
                    'media_id' => $cert->media_id,
                    'is_verified' => true,
                ]);
            }

            // Copy documents → shop_documents (set is_verified = true)
            foreach ($onboarding->documents as $document) {
                $shop->documents()->attach($document->id, ['is_verified' => true]);
            }

            // Update onboarding record
            $onboarding->update([
                'status' => 'approved',
                'shop_id' => $shop->id,
                'reviewed_at' => now(),
                'reviewed_by_id' => $admin->id,
            ]);

            return $shop;
        });

        // Dispatch AFTER commit (per design.md §3.7)
        IndexShopJob::dispatch($shop);
        OnboardingStatusChanged::dispatch($onboarding->fresh());

        return $shop;
    }
}
