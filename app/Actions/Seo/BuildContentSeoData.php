<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Support\Seo\SeoData;

/**
 * Generic public page (home, directories, about, legal). Title is optional:
 * when omitted the layout's Livewire #[Title] is used.
 */
final class BuildContentSeoData
{
    public function handle(string $description, ?string $title = null): SeoData
    {
        return new SeoData(title: $title, description: $description);
    }
}
