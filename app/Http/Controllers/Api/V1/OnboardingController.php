<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Events\OnboardingSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\Step1Request;
use App\Http\Requests\Onboarding\Step2Request;
use App\Http\Requests\Onboarding\Step3Request;
use App\Http\Requests\Onboarding\Step4Request;
use App\Http\Requests\Onboarding\Step5Request;
use App\Http\Requests\Onboarding\StoreCertificateRequest;
use App\Http\Requests\Onboarding\StoreDocumentRequest;
use App\Models\ShopOnboarding;
use App\Models\ShopOnboardingCertificate;
use App\Services\GeocodingService;
use App\Services\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OnboardingController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $mediaUploadService,
        private readonly GeocodingService $geocodingService,
    ) {}

    /**
     * GET /api/v1/onboarding — Get current user's onboarding.
     */
    public function show(Request $request): JsonResponse
    {
        $onboarding = ShopOnboarding::with(['certificates', 'documents'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $onboarding) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => $onboarding]);
    }

    /**
     * PUT /api/v1/onboarding/step/{n} — Upsert a wizard step.
     */
    public function updateStep(Request $request, string $step): JsonResponse
    {
        $validated = $this->validateStep($request, (int) $step);

        $onboarding = ShopOnboarding::firstOrNew(
            ['user_id' => $request->user()->id],
        );

        $onboarding->fill($validated);
        $onboarding->user_id = $request->user()->id;
        $onboarding->save();

        // Geocode address on Step 1 save
        if ((int) $step === 1) {
            $this->geocodingService->geocodeOnboarding($onboarding);
        }

        return response()->json([
            'data' => [
                'onboarding_id' => $onboarding->id,
                'step' => (int) $step,
                'status' => $onboarding->status,
            ],
        ]);
    }

    /**
     * POST /api/v1/onboarding/certificates — Add a halal certificate.
     */
    public function storeCertificate(StoreCertificateRequest $request): JsonResponse
    {
        $onboarding = ShopOnboarding::where('user_id', $request->user()->id)->firstOrFail();

        $certificate = $onboarding->certificates()->create($request->validated());

        return response()->json([
            'data' => [
                'id' => $certificate->id,
                'certification_body' => $certificate->certification_body,
                'expiry_date' => $certificate->expiry_date->toDateString(),
            ],
        ], 201);
    }

    /**
     * DELETE /api/v1/onboarding/certificates/{id} — Remove a certificate (pre-submission only).
     */
    public function destroyCertificate(Request $request, ShopOnboardingCertificate $certificate): JsonResponse
    {
        $onboarding = ShopOnboarding::where('user_id', $request->user()->id)->firstOrFail();

        if ($certificate->onboarding_id !== $onboarding->id) {
            abort(404);
        }

        if ($onboarding->status !== 'pending' && $onboarding->submitted_at !== null) {
            return response()->json([
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => 'Cannot delete certificates after submission.',
                ],
            ], 409);
        }

        $certificate->delete();

        return response()->json(null, 204);
    }

    /**
     * POST /api/v1/onboarding/documents — Upload a checklist document.
     */
    public function storeDocument(StoreDocumentRequest $request): JsonResponse
    {
        $onboarding = ShopOnboarding::where('user_id', $request->user()->id)->firstOrFail();

        $media = $this->mediaUploadService->store(
            $request->file('file'),
            $request->user()->id,
            $request->input('description', ''),
        );

        $onboarding->documents()->attach($media->id);

        return response()->json([
            'data' => [
                'media_id' => $media->id,
                'original_filename' => $media->original_filename,
                'storage_path' => $media->storage_path,
            ],
        ], 201);
    }

    /**
     * POST /api/v1/onboarding/submit — Finalize submission.
     */
    public function submit(Request $request): JsonResponse
    {
        $onboarding = ShopOnboarding::with(['certificates', 'documents'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $errors = $this->validateCompleteness($onboarding);

        if ($errors !== []) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Onboarding is incomplete.',
                    'fields' => $errors,
                ],
            ], 422);
        }

        $onboarding->update([
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        OnboardingSubmitted::dispatch($onboarding);

        return response()->json([
            'data' => [
                'onboarding_id' => $onboarding->id,
                'status' => 'pending',
                'submitted_at' => $onboarding->submitted_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Validate the request based on which step is being submitted.
     *
     * @return array<string, mixed>
     */
    private function validateStep(Request $request, int $step): array
    {
        $formRequest = match ($step) {
            1 => Step1Request::createFrom($request),
            2 => Step2Request::createFrom($request),
            3 => Step3Request::createFrom($request),
            4 => Step4Request::createFrom($request),
            5 => Step5Request::createFrom($request),
        };

        $formRequest->setContainer(app());
        $formRequest->setRedirector(app('redirect'));
        $formRequest->validateResolved();

        return $formRequest->validated();
    }

    /**
     * Validate that all required onboarding steps are complete for submission.
     *
     * @return array<string, list<string>>
     */
    private function validateCompleteness(ShopOnboarding $onboarding): array
    {
        $errors = [];

        // Step 1: Company profile
        if (! $onboarding->registered_business_name || ! $onboarding->storefront_name) {
            $errors['step_1'] = ['Company profile information is required.'];
        }

        // Step 2: Operations
        if (! $onboarding->product_categories) {
            $errors['step_2'] = ['Operation capacity information is required.'];
        }

        // Step 3: At least one certificate
        if ($onboarding->certificates->isEmpty()) {
            $errors['step_3'] = ['At least one halal certificate is required.'];
        }

        // Step 4: Value chain positions
        if (empty($onboarding->value_chain_positions)) {
            $errors['step_4'] = ['Value chain position information is required.'];
        }

        // Step 5: Documents - check all required docs uploaded and scanned clean
        $cleanDocCount = $onboarding->documents()
            ->wherePivot('media_id', '>', 0)
            ->count();
        if ($cleanDocCount < 1) {
            $errors['step_5'] = ['Required documents must be uploaded.'];
        }

        // Scan gates only apply when antivirus scanning is enabled.
        if ((bool) config('services.clamav.enabled', false)) {
            // Check if any documents are still pending scan
            $pendingDocs = $onboarding->documents()->where('scan_status', 'pending')->count();
            if ($pendingDocs > 0) {
                $errors['step_5'] = array_merge($errors['step_5'] ?? [], ['Some documents are still being scanned.']);
            }

            // Check for infected documents
            $infectedDocs = $onboarding->documents()->where('scan_status', 'infected')->count();
            if ($infectedDocs > 0) {
                $errors['step_5'] = array_merge($errors['step_5'] ?? [], ['Some documents failed the security scan.']);
            }
        }

        return $errors;
    }
}
