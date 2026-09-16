<?php

declare(strict_types=1);

use Illuminate\Mail\Transport\SesTransport;

test('ses mailer resolves to the SES transport without network calls', function () {
    config()->set('services.ses', [
        'key' => 'testing-key',
        'secret' => 'testing-secret',
        'token' => null,
        'region' => 'ap-southeast-1',
    ]);
    config()->set('mail.mailers.ses', [
        'transport' => 'ses',
        'options' => [],
    ]);

    $transport = app('mail.manager')->mailer('ses')->getSymfonyTransport();

    expect($transport)->toBeInstanceOf(SesTransport::class);
});

test('ses configuration set is passed through mail options when set', function () {
    config()->set('mail.mailers.ses.options', [
        'ConfigurationSetName' => 'marketplace-tracking',
    ]);

    expect(config('mail.mailers.ses.options.ConfigurationSetName'))->toBe('marketplace-tracking');
});

test('ses credentials fall back to shared AWS env config', function () {
    config()->set('services.ses.key', 'shared-key');
    config()->set('services.ses.secret', 'shared-secret');
    config()->set('services.ses.region', 'ap-southeast-1');

    expect(config('services.ses.key'))->toBe('shared-key')
        ->and(config('services.ses.secret'))->toBe('shared-secret')
        ->and(config('services.ses.region'))->toBe('ap-southeast-1');
});
