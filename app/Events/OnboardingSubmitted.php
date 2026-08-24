<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ShopOnboarding;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OnboardingSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ShopOnboarding $onboarding,
    ) {}
}
