<?php

declare(strict_types=1);

use App\Support\HumanPass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config(['humancheck.enabled' => true]);
    config(['humancheck.bind_ip' => false]);
    $this->withCredentials();

    Route::middleware(['web', 'human'])->get('/_test/protected', function () {
        return response()->json(['ok' => true]);
    });
});

test('middleware returns 403 json when cookie is missing for json request', function () {
    $this->getJson('/_test/protected')
        ->assertStatus(403)
        ->assertJson([
            'code' => 'HUMAN_CHECK_REQUIRED',
        ]);
});

test('middleware returns 403 json when cookie is missing for livewire request', function () {
    $this->withHeader('X-Livewire', 'true')
        ->get('/_test/protected')
        ->assertStatus(403)
        ->assertJson([
            'code' => 'HUMAN_CHECK_REQUIRED',
        ]);
});

test('middleware redirects back with error when cookie is missing for normal request', function () {
    $this->from('/previous')
        ->get('/_test/protected')
        ->assertRedirect('/previous')
        ->assertSessionHasErrors(['human_check']);
});

test('middleware passes when cookie is valid', function () {
    $req = Request::create('/_test/protected', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'TestingUA',
    ]);
    $cookie = HumanPass::issue($req);

    $this->withHeaders(['User-Agent' => 'TestingUA'])
        ->withUnencryptedCookie(HumanPass::COOKIE_NAME, $cookie->getValue())
        ->getJson('/_test/protected')
        ->assertOk()
        ->assertJson(['ok' => true]);
});

test('middleware rejects an expired cookie with 403', function () {
    $now = time();
    $payload = [
        'iat' => $now - 3600,
        'exp' => $now - 60, // Expired 1 minute ago
        'ua_hash' => hash('sha256', (string) request()->userAgent()),
    ];
    $expiredValue = Crypt::encryptString(json_encode($payload));

    $this->withUnencryptedCookie(HumanPass::COOKIE_NAME, $expiredValue)
        ->getJson('/_test/protected')
        ->assertStatus(403)
        ->assertJson(['code' => 'HUMAN_CHECK_REQUIRED']);
});

test('middleware rejects a tampered cookie with 403', function () {
    $this->withUnencryptedCookie(HumanPass::COOKIE_NAME, 'tampered-invalid-cookie-value')
        ->getJson('/_test/protected')
        ->assertStatus(403)
        ->assertJson(['code' => 'HUMAN_CHECK_REQUIRED']);
});

test('middleware rejects a cookie from a different user agent with 403', function () {
    $payload = [
        'iat' => time(),
        'exp' => time() + 1200,
        'ua_hash' => hash('sha256', 'Original-User-Agent'),
    ];
    $cookieVal = Crypt::encryptString(json_encode($payload));

    $this->withHeaders(['User-Agent' => 'Different-User-Agent'])
        ->withUnencryptedCookie(HumanPass::COOKIE_NAME, $cookieVal)
        ->getJson('/_test/protected')
        ->assertStatus(403)
        ->assertJson(['code' => 'HUMAN_CHECK_REQUIRED']);
});

test('middleware rejects a cookie from a different IP when bind_ip is enabled with 403', function () {
    config(['humancheck.bind_ip' => true]);

    $payload = [
        'iat' => time(),
        'exp' => time() + 1200,
        'ua_hash' => hash('sha256', (string) request()->userAgent()),
        'ip_hash' => hash('sha256', '192.168.1.1'),
    ];
    $cookieVal = Crypt::encryptString(json_encode($payload));

    // Request comes from default 127.0.0.1
    $this->withUnencryptedCookie(HumanPass::COOKIE_NAME, $cookieVal)
        ->getJson('/_test/protected')
        ->assertStatus(403)
        ->assertJson(['code' => 'HUMAN_CHECK_REQUIRED']);
});

test('middleware allows request when humancheck is disabled', function () {
    config(['humancheck.enabled' => false]);

    $this->getJson('/_test/protected')
        ->assertOk()
        ->assertJson(['ok' => true]);
});
