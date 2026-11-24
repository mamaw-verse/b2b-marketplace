<?php

declare(strict_types=1);

namespace App\Support\Seo;

use DateTimeInterface;

/**
 * Renders the sitemaps.org XML documents. Values are XML-escaped; nothing else
 * is ever interpolated.
 */
final class SitemapXml
{
    private const NAMESPACE = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    /**
     * @param  iterable<array{loc: string, lastmod?: DateTimeInterface|null}>  $entries
     */
    public static function urlset(iterable $entries): string
    {
        return self::document('urlset', 'url', $entries);
    }

    /**
     * @param  iterable<array{loc: string, lastmod?: DateTimeInterface|null}>  $entries
     */
    public static function index(iterable $entries): string
    {
        return self::document('sitemapindex', 'sitemap', $entries);
    }

    /**
     * Site origin used for every sitemap URL: APP_URL, never the request host,
     * so a cached file cannot carry an internal or spoofed hostname.
     */
    public static function origin(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * Absolute URL for a path (`/products/rice`) or a named route's path.
     */
    public static function url(string $path): string
    {
        return self::origin().($path === '' || $path === '/' ? '/' : '/'.ltrim($path, '/'));
    }

    /**
     * @param  iterable<array{loc: string, lastmod?: DateTimeInterface|null}>  $entries
     */
    private static function document(string $root, string $item, iterable $entries): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<'.$root.' xmlns="'.self::NAMESPACE.'">'."\n";

        foreach ($entries as $entry) {
            $xml .= '  <'.$item.'><loc>'.self::escape($entry['loc']).'</loc>';

            if (($entry['lastmod'] ?? null) instanceof DateTimeInterface) {
                $xml .= '<lastmod>'.$entry['lastmod']->format(DATE_ATOM).'</lastmod>';
            }

            $xml .= '</'.$item.">\n";
        }

        return $xml.'</'.$root.">\n";
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
