<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\RecordSponsoredContentEventJob;
use App\Models\SponsoredContent;
use Illuminate\Http\RedirectResponse;

final class SponsoredContentClickController extends Controller
{
    /**
     * Record a click event and redirect to the content's destination URL.
     *
     * Public route — no auth required.
     */
    public function __invoke(SponsoredContent $sponsoredContent): RedirectResponse
    {
        RecordSponsoredContentEventJob::dispatch(
            $sponsoredContent->id,
            'click',
        );

        return redirect()->away($sponsoredContent->destination_url);
    }
}
