<?php

declare(strict_types=1);

namespace App\Support\Schema;

use Illuminate\Support\Str;

/**
 * Stable `@id` values for schema.org nodes.
 *
 * Site-wide ids hang off the deployment's own origin (never a hard-coded
 * domain); per-page ids are the page's canonical URL plus a fragment, so the
 * same entity always gets the same id from any page that mentions it.
 */
final class SchemaId
{
    public static function base(): string
    {
        return rtrim(url('/'), '/');
    }

    public static function organization(): string
    {
        return self::base().'/#organization';
    }

    public static function website(): string
    {
        return self::base().'/#website';
    }

    public static function logo(): string
    {
        return self::base().'/#logo';
    }

    public static function webPage(string $pageUrl): string
    {
        return self::withFragment($pageUrl, 'webpage');
    }

    public static function breadcrumb(string $pageUrl): string
    {
        return self::withFragment($pageUrl, 'breadcrumb');
    }

    public static function itemList(string $pageUrl): string
    {
        return self::withFragment($pageUrl, 'itemlist');
    }

    /**
     * A supplier company, from its public profile URL.
     */
    public static function company(string $companyUrl): string
    {
        return self::withFragment($companyUrl, 'organization');
    }

    public static function product(string $productUrl): string
    {
        return self::withFragment($productUrl, 'product');
    }

    public static function variant(string $productUrl, string $sku): string
    {
        $slug = Str::slug($sku);

        return self::withFragment($productUrl, 'variant-'.($slug !== '' ? $slug : md5($sku)));
    }

    private static function withFragment(string $url, string $fragment): string
    {
        $hash = strpos($url, '#');

        if ($hash !== false) {
            $url = substr($url, 0, $hash);
        }

        return $url.'#'.$fragment;
    }
}
