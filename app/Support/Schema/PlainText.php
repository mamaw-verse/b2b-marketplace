<?php

declare(strict_types=1);

namespace App\Support\Schema;

/**
 * Turns user-supplied rich text (the WYSIWYG/Trix descriptions) into safe plain
 * text for structured data: no markup, decoded entities, collapsed whitespace.
 *
 * Framework-free so it can be unit tested without booting Laravel.
 */
final class PlainText
{
    public static function from(?string $html, ?int $limit = null): ?string
    {
        if ($html === null) {
            return null;
        }

        $text = mb_scrub($html);

        // Drop script/style blocks together with their content.
        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', ' ', $text) ?? $text;

        // Keep words apart where block-level markup used to separate them.
        $text = preg_replace('#<\s*br\b[^>]*>|<\s*/?\s*(p|div|li|ul|ol|h[1-6]|tr|td|th|table|blockquote|figure|figcaption)\b[^>]*>#i', ' ', $text) ?? $text;

        // Strip, decode, then strip again so escaped markup cannot survive.
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? $text;
        $text = trim(preg_replace('/[\p{Z}\s]+/u', ' ', $text) ?? $text);

        if ($text === '') {
            return null;
        }

        if ($limit !== null && $limit > 1 && mb_strlen($text) > $limit) {
            $text = rtrim(mb_substr($text, 0, $limit - 1)).'…';
        }

        return $text;
    }
}
