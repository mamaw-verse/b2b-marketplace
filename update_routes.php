<?php

declare(strict_types=1);

$content = file_get_contents('routes/web.php');

$content = str_replace(
    "    // Orders (buyer)\n    Route::livewire('orders', 'account.orders.order-list')->name('orders.index');\n    Route::livewire('orders/{order}', 'account.orders.order-detail')->name('orders.show');\n    Route::livewire('orders/{order}/pay', 'account.orders.payment-page')->name('orders.pay');",
    "    // Orders (buyer)\n    Route::middleware('feature:orders_enabled')->group(function () {\n        Route::livewire('orders', 'account.orders.order-list')->name('orders.index');\n        Route::livewire('orders/{order}', 'account.orders.order-detail')->name('orders.show');\n        Route::livewire('orders/{order}/pay', 'account.orders.payment-page')->name('orders.pay');\n    });",
    $content);

$content = str_replace(
    "    // RFQ management (seller)\n    Route::livewire('rfqs', 'company-hub.rfq-queue')->name('rfqs.index');\n    Route::livewire('rfqs/{rfqRecipient}', 'company-hub.rfq-detail')->name('rfqs.show');\n    Route::livewire('rfqs/{rfqRecipient}/quotations/create', 'company-hub.quotation-builder')->name('quotations.create');\n    Route::livewire('rfqs/{rfqRecipient}/quotations/{quotation}/edit', 'company-hub.quotation-builder')->name('quotations.edit');",
    "    // RFQ management (seller)\n    Route::middleware('feature:seller_rfq_queue_enabled')->group(function () {\n        Route::livewire('rfqs', 'company-hub.rfq-queue')->name('rfqs.index');\n        Route::livewire('rfqs/{rfqRecipient}', 'company-hub.rfq-detail')->name('rfqs.show');\n    });\n    Route::middleware('feature:quotations_enabled')->group(function () {\n        Route::livewire('rfqs/{rfqRecipient}/quotations/create', 'company-hub.quotation-builder')->name('quotations.create');\n        Route::livewire('rfqs/{rfqRecipient}/quotations/{quotation}/edit', 'company-hub.quotation-builder')->name('quotations.edit');\n    });",
    $content);

$content = str_replace(
    "    // Orders (seller)\n    Route::livewire('orders', 'company-hub.orders.order-queue')->name('orders.index');\n    Route::livewire('orders/create', 'company-hub.orders.order-builder')->name('orders.create');\n    Route::livewire('orders/{order}/edit', 'company-hub.orders.order-builder')->name('orders.edit');\n    Route::livewire('orders/{order}', 'company-hub.orders.order-detail')->name('orders.show');",
    "    // Orders (seller)\n    Route::middleware('feature:orders_enabled')->group(function () {\n        Route::livewire('orders', 'company-hub.orders.order-queue')->name('orders.index');\n        Route::livewire('orders/create', 'company-hub.orders.order-builder')->name('orders.create');\n        Route::livewire('orders/{order}/edit', 'company-hub.orders.order-builder')->name('orders.edit');\n        Route::livewire('orders/{order}', 'company-hub.orders.order-detail')->name('orders.show');\n    });",
    $content);

$content = str_replace(
    "    // Payout Account\n    Route::livewire('payout-account', 'company-hub.payout-account-form')->name('payout-account');\n\n    // Payouts (seller)\n    Route::livewire('payouts', 'company-hub.payouts.payout-history')->name('payouts.index');\n    Route::livewire('payouts/{payout}', 'company-hub.payouts.payout-detail')->name('payouts.show');",
    "    // Payouts (seller)\n    Route::middleware('feature:payouts_enabled')->group(function () {\n        Route::livewire('payout-account', 'company-hub.payout-account-form')->name('payout-account');\n        Route::livewire('payouts', 'company-hub.payouts.payout-history')->name('payouts.index');\n        Route::livewire('payouts/{payout}', 'company-hub.payouts.payout-detail')->name('payouts.show');\n    });",
    $content);

$content = str_replace(
    "    // Reviews (seller)\n    Route::livewire('reviews', 'company-hub.reviews.review-list')->name('reviews.index');",
    "    // Reviews (seller)\n    Route::middleware('feature:reviews_enabled')->group(function () {\n        Route::livewire('reviews', 'company-hub.reviews.review-list')->name('reviews.index');\n    });",
    $content);

$content = str_replace(
    "    // Disputes (admin)\n    Route::livewire('disputes', 'admin.disputes.dispute-queue')->name('disputes.index');\n    Route::livewire('disputes/{dispute}', 'admin.disputes.dispute-arbitration')->name('disputes.show');",
    "    // Disputes (admin)\n    Route::middleware('feature:disputes_enabled')->group(function () {\n        Route::livewire('disputes', 'admin.disputes.dispute-queue')->name('disputes.index');\n        Route::livewire('disputes/{dispute}', 'admin.disputes.dispute-arbitration')->name('disputes.show');\n    });",
    $content);

$content = str_replace(
    "    // Payout Settings\n    Route::livewire('payout-settings', 'admin.payout-settings')->name('payout-settings');\n\n    // Financial Ledger (admin)\n    Route::livewire('ledger', 'admin.finance.financial-ledger')->name('ledger');\n\n    // Payout Accounts\n    Route::livewire('payout-accounts', 'admin.payout-accounts.payout-account-queue')->name('payout-accounts.index');\n\n    // Payouts\n    Route::livewire('payouts', 'admin.payouts.payout-queue')->name('payouts.index');\n    Route::livewire('payouts/eligible', 'admin.payouts.eligible-payouts')->name('payouts.eligible');\n    Route::livewire('payouts/{payout}', 'admin.payouts.payout-detail')->name('payouts.show');",
    "    // Payouts & Ledger (admin)\n    Route::middleware('feature:payouts_enabled')->group(function () {\n        Route::livewire('payout-settings', 'admin.payout-settings')->name('payout-settings');\n        Route::livewire('payout-accounts', 'admin.payout-accounts.payout-account-queue')->name('payout-accounts.index');\n        Route::livewire('payouts', 'admin.payouts.payout-queue')->name('payouts.index');\n        Route::livewire('payouts/eligible', 'admin.payouts.eligible-payouts')->name('payouts.eligible');\n        Route::livewire('payouts/{payout}', 'admin.payouts.payout-detail')->name('payouts.show');\n        Route::livewire('ledger', 'admin.finance.financial-ledger')->name('ledger');\n    });",
    $content);

$content = str_replace(
    "    // Review Reports\n    Route::livewire('review-reports', 'admin.reviews.review-report-queue')->name('review-reports.index');",
    "    // Review Reports\n    Route::middleware('feature:reviews_enabled')->group(function () {\n        Route::livewire('review-reports', 'admin.reviews.review-report-queue')->name('review-reports.index');\n    });",
    $content);

$content = str_replace(
    "    Route::get('/orders/{order}/payment/success', [App\Http\Controllers\OrderPaymentCallbackController::class, 'success'])\n        ->name('orders.payment.success');\n    Route::get('/orders/{order}/payment/failure', [App\Http\Controllers\OrderPaymentCallbackController::class, 'failure'])\n        ->name('orders.payment.failure');",
    "    Route::middleware('feature:orders_enabled')->group(function () {\n        Route::get('/orders/{order}/payment/success', [App\Http\Controllers\OrderPaymentCallbackController::class, 'success'])\n            ->name('orders.payment.success');\n        Route::get('/orders/{order}/payment/failure', [App\Http\Controllers\OrderPaymentCallbackController::class, 'failure'])\n            ->name('orders.payment.failure');\n    });",
    $content);

file_put_contents('routes/web.php', $content);
