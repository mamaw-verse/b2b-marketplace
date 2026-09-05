<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Consolidate the country-scoped "Philippines Premium Plan" into the single
 * global "Annual Membership Plan". Each country now runs its own independent
 * app instance, so a country-scoped plan is redundant.
 *
 * Steps:
 *  1. Give the global plan trial_days (inheriting from the PH plan default).
 *  2. Reassign all subscriptions referencing the PH plan to the global plan.
 *  3. Delete the PH plan row.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $phPlan = DB::table('subscription_plans')
                ->where('country_scope', 'PH')
                ->first();

            $globalPlan = DB::table('subscription_plans')
                ->whereNull('country_scope')
                ->first();

            if (! $phPlan || ! $globalPlan) {
                return;
            }

            // Carry over trial_days to the global plan if it doesn't have one yet
            if ($globalPlan->trial_days === null && $phPlan->trial_days !== null) {
                DB::table('subscription_plans')
                    ->where('id', $globalPlan->id)
                    ->update(['trial_days' => $phPlan->trial_days]);
            }

            // Reassign subscriptions pointing at the PH plan
            DB::table('subscriptions')
                ->where('subscription_plan_id', $phPlan->id)
                ->update(['subscription_plan_id' => $globalPlan->id]);

            // Remove the PH plan
            DB::table('subscription_plans')
                ->where('id', $phPlan->id)
                ->delete();
        });
    }

    public function down(): void
    {
        // Intentionally not reversible — re-seed with SubscriptionPlanSeeder if needed.
    }
};
