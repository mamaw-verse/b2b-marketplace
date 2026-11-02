<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TurnstileVerifier;
use App\Support\HumanPass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HumanCheckController extends Controller
{
    public function __construct(
        private readonly TurnstileVerifier $verifier,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = (string) $request->input('token');
        $result = $this->verifier->verify($token, $request->ip());

        if (! $result->success) {
            return response()->json([
                'error' => [
                    'code' => 'HUMAN_CHECK_FAILED',
                    'message' => 'Verification failed. Please try again.',
                    'error_codes' => $result->errorCodes,
                ],
            ], 422);
        }

        $cookie = HumanPass::issue($request);
        $expiresAt = time() + (int) config('humancheck.ttl', 1200);

        return response()->json([
            'ok' => true,
            'expires_at' => $expiresAt,
        ])->withCookie($cookie);
    }
}
