<?php

declare(strict_types=1);

use App\Support\HumanPass;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['humancheck.enabled' => true]);
});

test('human-check endpoint succeeds with valid token and sets signed cookie', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response([
            'success' => true,
            'challenge_ts' => now()->toIso8601String(),
            'hostname' => 'example.com',
        ], 200),
    ]);

    $response = $this->postJson(route('human-check'), [
        'token' => 'dummy-valid-turnstile-token',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['ok', 'expires_at'])
        ->assertJson(['ok' => true])
        ->assertCookie(HumanPass::COOKIE_NAME);

    $expiresAt = $response->json('expires_at');
    expect($expiresAt)->toBeGreaterThan(time());
});

test('human-check endpoint fails and returns 422 on invalid token', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ], 200),
    ]);

    $response = $this->postJson(route('human-check'), [
        'token' => 'dummy-invalid-token',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'error' => [
                'code' => 'HUMAN_CHECK_FAILED',
                'message' => 'Verification failed. Please try again.',
                'error_codes' => ['invalid-input-response'],
            ],
        ])
        ->assertCookieMissing(HumanPass::COOKIE_NAME);
});

test('human-check endpoint fails and returns 422 on cloudflare timeout', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    $response = $this->postJson(route('human-check'), [
        'token' => 'dummy-timeout-token',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'error' => [
                'code' => 'HUMAN_CHECK_FAILED',
                'error_codes' => ['timeout-or-connection-error'],
            ],
        ])
        ->assertCookieMissing(HumanPass::COOKIE_NAME);
});

test('human-check endpoint requires a token', function () {
    $response = $this->postJson(route('human-check'), []);

    $response->assertStatus(422);
});

test('human-check endpoint is rate limited to 20 requests per minute', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
    ]);

    for ($i = 0; $i < 20; $i++) {
        $this->postJson(route('human-check'), ['token' => 'valid-token'])
            ->assertOk();
    }

    $this->postJson(route('human-check'), ['token' => 'valid-token'])
        ->assertStatus(429);
});
