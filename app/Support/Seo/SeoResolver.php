<?php

declare(strict_types=1);

namespace App\Support\Seo;

/**
 * Request-scoped holder for the current page's SeoData.
 *
 * Livewire full-page components render before the layout, so a component can
 * call set() from render() and the layout's <x-seo.head> will read it.
 */
final class SeoResolver
{
    private ?SeoData $data = null;

    public function set(SeoData $data): void
    {
        $this->data = $data;
    }

    public function get(): ?SeoData
    {
        return $this->data;
    }
}
