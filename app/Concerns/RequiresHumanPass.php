<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Support\HumanPass;
use Illuminate\Validation\ValidationException;

trait RequiresHumanPass
{
    /**
     * Ensure the current session has a valid human verification pass.
     *
     * @throws ValidationException
     */
    protected function guardHuman(): void
    {
        if (! (bool) config('humancheck.enabled', true)) {
            return;
        }

        if (HumanPass::valid(request())) {
            return;
        }

        $this->dispatch('human-check-required');

        throw ValidationException::withMessages([
            'human_check' => __('Please complete the security check and try again.'),
        ]);
    }
}
