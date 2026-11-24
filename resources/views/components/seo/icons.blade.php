{{-- Favicons and app signals shared by every layout (storefront, portal, auth, error pages). --}}
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon-32.png" type="image/png" sizes="32x32">
<link rel="icon" href="/icon-192.png" type="image/png" sizes="192x192">
<link rel="icon" href="/{{ config('seo.logo_path') }}" type="image/png" sizes="512x512">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="{{ url('/manifest.webmanifest') }}">
<meta name="application-name" content="{{ config('seo.site_name') }}">
<meta name="apple-mobile-web-app-title" content="{{ config('seo.site_name') }}">
<meta name="theme-color" content="{{ config('seo.theme_color') }}">
