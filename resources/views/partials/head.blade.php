<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<meta name="robots" content="noindex, nofollow">
<x-seo.icons />

<link rel="preconnect" href="https://challenges.cloudflare.com" crossorigin>
<link rel="dns-prefetch" href="https://challenges.cloudflare.com">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

@livewireStyles
