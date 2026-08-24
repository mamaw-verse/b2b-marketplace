<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePayoutSettingsRequest;
use App\Models\PayoutSetting;
use Illuminate\Http\JsonResponse;

final class PayoutSettingsController extends Controller
{
    /**
     * GET /api/v1/admin/payout-settings — Returns all settings as key-value object.
     */
    public function index(): JsonResponse
    {
        $settings = PayoutSetting::all()
            ->mapWithKeys(fn (PayoutSetting $setting) => [$setting->key => $setting->value]);

        return response()->json(['data' => $settings]);
    }

    /**
     * PUT /api/v1/admin/payout-settings — Bulk update one or more settings.
     */
    public function update(UpdatePayoutSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            PayoutSetting::where('key', $key)->update([
                'value' => (string) $value,
                'updated_by_id' => $request->user()->id,
            ]);
        }

        $settings = PayoutSetting::all()
            ->mapWithKeys(fn (PayoutSetting $setting) => [$setting->key => $setting->value]);

        return response()->json(['data' => $settings]);
    }
}
