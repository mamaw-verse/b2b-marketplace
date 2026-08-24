<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

test('search endpoint returns 429 after exceeding rate limit', function () {
    // Override the search-limiter with a low threshold for testing
    RateLimiter::for('search-limiter', function () {
        return Limit::perMinute(3)->by('test-ip')
            ->response(function () {
                return response()->json([
                    'error' => [
                        'code' => 'RATE_LIMITED',
                        'message' => 'Too many search requests. Please try again later.',
                    ],
                ], 429);
            });
    });

    // First 3 requests should succeed
    for ($i = 1; $i <= 3; $i++) {
        $response = $this->getJson('/api/v1/search/products');
        $response->assertOk();
    }

    // 4th request should be rate limited
    $response = $this->getJson('/api/v1/search/products');

    $response->assertStatus(429)
        ->assertJsonPath('error.code', 'RATE_LIMITED');
});

test('search companies endpoint also respects rate limit', function () {
    // Override the search-limiter with a low threshold for testing
    RateLimiter::for('search-limiter', function () {
        return Limit::perMinute(3)->by('test-ip')
            ->response(function () {
                return response()->json([
                    'error' => [
                        'code' => 'RATE_LIMITED',
                        'message' => 'Too many search requests. Please try again later.',
                    ],
                ], 429);
            });
    });

    // First 3 requests should succeed
    for ($i = 1; $i <= 3; $i++) {
        $response = $this->getJson('/api/v1/search/companies');
        $response->assertOk();
    }

    // 4th request should be rate limited
    $response = $this->getJson('/api/v1/search/companies');

    $response->assertStatus(429)
        ->assertJsonPath('error.code', 'RATE_LIMITED');
});

test('rate limit response includes proper error envelope', function () {
    // Override with very low threshold
    RateLimiter::for('search-limiter', function () {
        return Limit::perMinute(1)->by('test-ip')
            ->response(function () {
                return response()->json([
                    'error' => [
                        'code' => 'RATE_LIMITED',
                        'message' => 'Too many search requests. Please try again later.',
                    ],
                ], 429);
            });
    });

    // First request succeeds
    $this->getJson('/api/v1/search/products')->assertOk();

    // Second request is rate limited
    $response = $this->getJson('/api/v1/search/products');

    $response->assertStatus(429)
        ->assertJsonStructure([
            'error' => ['code', 'message'],
        ])
        ->assertJsonPath('error.code', 'RATE_LIMITED')
        ->assertJsonPath('error.message', 'Too many search requests. Please try again later.');
});
