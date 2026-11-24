@props(['fallbackTitle' => null])
@php
    $seo = app(\App\Support\Seo\SeoResolver::class)->get();
    $policy = app(\App\Support\Seo\IndexingPolicy::class);

    $title = filled($seo?->title) ? $seo->title : (filled($fallbackTitle) ? $fallbackTitle : config('seo.site_name'));
    $description = $seo?->description;
    $canonical = $seo?->canonical ?? \App\Support\Seo\CanonicalUrl::fromRequest(request());
    $robots = $seo?->robots ?? $policy->robotsDirective(request());
    $ogType = $seo?->ogType ?? 'website';

    $usesDefaultImage = blank($seo?->image);
    $image = $usesDefaultImage ? asset(config('seo.default_og_image')) : $seo->image;
    $imageWidth = $usesDefaultImage ? 1200 : $seo->imageWidth;
    $imageHeight = $usesDefaultImage ? 630 : $seo->imageHeight;
    // The alt text must describe the image shown: a page's own image carries its name, the default one the site.
    $imageAlt = $usesDefaultImage ? config('seo.site_name') : ($seo->imageAlt ?? config('seo.site_name'));

    $ogLocale = config('seo.og_locales.'.app()->getLocale());

    // Structured data must never take a page down: on failure the page renders without it.
    try {
        $schemaJson = app(\App\Actions\Seo\BuildSchemaGraph::class)->handle(
            seo: $seo,
            title: $title,
            description: $description,
            canonical: $canonical,
            imageUrl: $usesDefaultImage ? null : $image,
            imageWidth: $usesDefaultImage ? null : $imageWidth,
            imageHeight: $usesDefaultImage ? null : $imageHeight,
        )->toJson();
    } catch (\Throwable $e) {
        report($e);
        $schemaJson = null;
    }
@endphp
<title>{{ $title }}</title>
@if (filled($description))
<meta name="description" content="{{ $description }}">
@endif
<link rel="canonical" href="{{ $canonical }}">
@if ($robots)
<meta name="robots" content="{{ $robots }}">
@endif
@if ($seo?->isHome)
@foreach (app(\App\Support\Seo\Hreflang::class)->alternates() as $alternate)
<link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['href'] }}">
@endforeach
@endif

<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:title" content="{{ $title }}">
@if (filled($description))
<meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:image" content="{{ $image }}">
@if (str_starts_with($image, 'https://'))
<meta property="og:image:secure_url" content="{{ $image }}">
@endif
<meta property="og:image:alt" content="{{ $imageAlt }}">
@if ($imageWidth && $imageHeight)
<meta property="og:image:width" content="{{ $imageWidth }}">
<meta property="og:image:height" content="{{ $imageHeight }}">
@endif
<meta property="og:site_name" content="{{ config('seo.site_name') }}">
@if ($ogLocale)
<meta property="og:locale" content="{{ $ogLocale }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
@if (filled($description))
<meta name="twitter:description" content="{{ $description }}">
@endif
<meta name="twitter:image" content="{{ $image }}">
<meta name="twitter:image:alt" content="{{ $imageAlt }}">

@if ($schemaJson)
<script type="application/ld+json">{!! $schemaJson !!}</script>
@endif
