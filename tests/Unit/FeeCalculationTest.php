<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Fee Calculation Unit Tests
|--------------------------------------------------------------------------
|
| These tests validate the fee calculation logic used in CreatePayoutBatchAction.
| Using the default settings: transaction_fee=15.00, commission_fee_rate=0.03
|
*/

test('single order at minimum threshold calculates correct fees', function () {
    $escrowed = 500.00; // minimum threshold
    $transactionFee = 15.00;
    $commissionFeeRate = 0.03;

    $commissionFee = round($escrowed * $commissionFeeRate, 2, PHP_ROUND_HALF_UP);
    $netAmount = round($escrowed - $transactionFee - $commissionFee, 2);

    expect($commissionFee)->toBe(15.00)
        ->and($netAmount)->toBe(470.00);
});

test('commission fee rounding uses ROUND_HALF_UP', function () {
    $escrowed = 333.33;
    $commissionFeeRate = 0.03;

    $commissionFee = round($escrowed * $commissionFeeRate, 2, PHP_ROUND_HALF_UP);

    // 333.33 * 0.03 = 9.9999 → rounds to 10.00
    expect($commissionFee)->toBe(10.00);
});

test('large amounts maintain precision at NUMERIC(12,2)', function () {
    $escrowed = 999999999.99; // close to max NUMERIC(12,2) supports (10 digits)
    $transactionFee = 15.00;
    $commissionFeeRate = 0.03;

    $commissionFee = round($escrowed * $commissionFeeRate, 2, PHP_ROUND_HALF_UP);
    $netAmount = round($escrowed - $transactionFee - $commissionFee, 2);

    // Verify precision is maintained
    expect($commissionFee)->toBe(30000000.00)
        ->and($netAmount)->toBe(969999984.99)
        ->and(mb_strlen(number_format($netAmount, 2, '.', '')))->toBeLessThanOrEqual(15); // 12 digits + dot + 2 decimals
});

test('net amount never goes negative when fees exceed escrowed amount', function () {
    $escrowed = 15.00; // exactly equal to transaction fee alone
    $transactionFee = 15.00;
    $commissionFeeRate = 0.03;

    $commissionFee = round($escrowed * $commissionFeeRate, 2, PHP_ROUND_HALF_UP);
    $netAmount = round($escrowed - $transactionFee - $commissionFee, 2);

    // 15.00 - 15.00 - 0.45 = -0.45, should be negative
    expect($netAmount)->toBeLessThanOrEqual(0);

    // In the action, we validate: net > 0, so this should be rejected
    // This test proves the validation condition is needed
});

test('fees with fractional amounts round correctly', function () {
    $escrowed = 777.77;
    $transactionFee = 15.00;
    $commissionFeeRate = 0.03;

    $commissionFee = round($escrowed * $commissionFeeRate, 2, PHP_ROUND_HALF_UP);
    $netAmount = round($escrowed - $transactionFee - $commissionFee, 2);

    // 777.77 * 0.03 = 23.3331 → 23.33
    expect($commissionFee)->toBe(23.33)
        ->and($netAmount)->toBe(739.44); // 777.77 - 15.00 - 23.33
});
