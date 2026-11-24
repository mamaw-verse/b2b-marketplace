<?php

declare(strict_types=1);

namespace App\Support\Schema;

/**
 * Collects schema.org nodes for one page into a single `@context` + `@graph`
 * document.
 *
 * Nodes that share an `@id` are merged (later values win), empty values are
 * stripped recursively, and the output is encoded so user-supplied text can
 * never break out of the surrounding <script> element.
 *
 * Framework-free on purpose so it can be unit tested without booting Laravel.
 */
final class SchemaGraph
{
    public const CONTEXT = 'https://schema.org';

    private const JSON_FLAGS = JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
        | JSON_INVALID_UTF8_SUBSTITUTE
        | JSON_THROW_ON_ERROR;

    /** @var array<string, array<string, mixed>> */
    private array $nodes = [];

    private int $anonymous = 0;

    /**
     * A bare reference to another node in the graph.
     *
     * @return array{'@id': string}
     */
    public static function ref(string $id): array
    {
        return ['@id' => $id];
    }

    /**
     * Add a top-level node. Empty values are dropped; a node left with nothing
     * but `@id` / `@type` is ignored; a node whose `@id` is already present is
     * merged into the existing one.
     *
     * @param  array<string, mixed>  $node
     */
    public function add(array $node): self
    {
        $cleaned = self::clean($node);

        if (! is_array($cleaned) || array_diff_key($cleaned, ['@id' => true, '@type' => true]) === []) {
            return $this;
        }

        $id = $cleaned['@id'] ?? null;

        if (is_string($id)) {
            $this->nodes[$id] = isset($this->nodes[$id])
                ? array_replace($this->nodes[$id], $cleaned)
                : $cleaned;

            return $this;
        }

        $this->nodes['anonymous-'.$this->anonymous++] = $cleaned;

        return $this;
    }

    public function has(string $id): bool
    {
        return isset($this->nodes[$id]);
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return array_values(array_filter(
            array_map(static fn (array $node): ?string => is_string($node['@id'] ?? null) ? $node['@id'] : null, $this->nodes),
        ));
    }

    /**
     * @return array{'@context': string, '@graph': list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            '@context' => self::CONTEXT,
            '@graph' => array_values($this->nodes),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), self::JSON_FLAGS);
    }

    /**
     * Recursively remove null, blank strings, empty arrays and non-finite
     * numbers. Zero and false are real values and are kept. A typed node that
     * has no properties left besides `@type` collapses to nothing.
     */
    private static function clean(mixed $value): mixed
    {
        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }

        if (is_float($value)) {
            return is_finite($value) ? $value : null;
        }

        if (! is_array($value)) {
            return is_scalar($value) ? $value : null;
        }

        $out = [];

        foreach ($value as $key => $item) {
            $cleaned = self::clean($item);

            if ($cleaned !== null) {
                $out[$key] = $cleaned;
            }
        }

        if ($out === []) {
            return null;
        }

        if (array_is_list($value)) {
            return array_values($out);
        }

        if (isset($out['@type']) && array_diff_key($out, ['@type' => true, '@context' => true]) === []) {
            return null;
        }

        return $out;
    }
}
