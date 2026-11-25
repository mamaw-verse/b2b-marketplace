<?php

declare(strict_types=1);

namespace App\Support\Schema;

/**
 * Structural validator for the JSON-LD documents the storefront emits (T6.1).
 *
 * It checks the rules this project promises (one `@context` + `@graph`, typed
 * nodes, unique and resolvable `@id`s, absolute URLs, no empty values) plus the
 * properties Google's structured-data documentation requires for the types we
 * emit. It does not replace the Rich Results Test (T6.2); it keeps regressions
 * out of CI and is reusable by the scheduled audit (T6.4).
 *
 * Framework-free on purpose so it can be unit tested without booting Laravel.
 */
final class JsonLdValidator
{
    /**
     * Properties that must be present and non-empty, per `@type`.
     *
     * @var array<string, list<string>>
     */
    private const array REQUIRED = [
        'Organization' => ['name'],
        'WebSite' => ['name', 'url'],
        'WebPage' => ['url'],
        'CollectionPage' => ['url'],
        'AboutPage' => ['url'],
        'FAQPage' => ['url', 'mainEntity'],
        'ItemPage' => ['url'],
        'BreadcrumbList' => ['itemListElement'],
        'ItemList' => ['itemListElement'],
        'Product' => ['name'],
        'ProductGroup' => ['name', 'productGroupID', 'hasVariant'],
        'Offer' => ['price', 'priceCurrency'],
        'UnitPriceSpecification' => ['price', 'priceCurrency'],
        'AggregateRating' => ['ratingValue'],
        'Certification' => ['name'],
        'ImageObject' => ['url'],
        'PropertyValue' => ['name', 'value'],
        'Question' => ['name', 'acceptedAnswer'],
        'Answer' => ['text'],
        'ContactPoint' => ['contactType'],
    ];

    /**
     * Keys whose string value (or each string in a list) must be an absolute http(s) URL.
     *
     * @var list<string>
     */
    private const array URL_KEYS = ['@id', 'url', 'contentUrl', 'thumbnailUrl', 'sameAs', 'image', 'item'];

    /**
     * Validate the single JSON-LD script of a rendered page.
     *
     * @return list<string> Human-readable problems; empty when the page is valid.
     */
    public function validateHtml(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches, PREG_OFFSET_CAPTURE);

        if (count($matches[1]) !== 1) {
            return ['Expected exactly one JSON-LD script, found '.count($matches[1]).'.'];
        }

        $headEnd = stripos($html, '</head>');

        if ($headEnd !== false && $matches[0][0][1] > $headEnd) {
            return ['The JSON-LD script is rendered outside the document head.'];
        }

        $document = json_decode($matches[1][0][0], true);

        if (! is_array($document)) {
            return ['The JSON-LD script is not valid JSON: '.json_last_error_msg().'.'];
        }

        return $this->validate($document);
    }

    /**
     * @param  array<mixed>  $document
     * @return list<string>
     */
    public function validate(array $document): array
    {
        $errors = [];

        if (($document['@context'] ?? null) !== SchemaGraph::CONTEXT) {
            $errors[] = '@context must be exactly '.SchemaGraph::CONTEXT.'.';
        }

        $extra = array_diff(array_keys($document), ['@context', '@graph']);

        if ($extra !== []) {
            $errors[] = 'Unexpected top-level keys: '.implode(', ', $extra).'.';
        }

        $graph = $document['@graph'] ?? null;

        if (! is_array($graph) || $graph === [] || ! array_is_list($graph)) {
            return [...$errors, '@graph must be a non-empty list of nodes.'];
        }

        $declared = [];
        $references = [];

        foreach ($graph as $index => $node) {
            $path = '@graph['.$index.']';

            if (! is_array($node) || array_is_list($node)) {
                $errors[] = $path.' must be an object.';

                continue;
            }

            if (isset($node['@id']) && is_string($node['@id'])) {
                if (isset($declared[$node['@id']])) {
                    $errors[] = $path.' repeats @id '.$node['@id'].'.';
                }

                $declared[$node['@id']] = true;
            }

            if (! isset($node['@type'])) {
                $errors[] = $path.' has no @type.';
            }
        }

        $this->walk($graph, '@graph', $errors, $declared, $references);

        foreach ($references as $path => $id) {
            if (! isset($declared[$id])) {
                $errors[] = $path.' references '.$id.', which is not declared in the graph.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<mixed>  $value
     * @param  list<string>  $errors
     * @param  array<string, true>  $declared
     * @param  array<string, string>  $references
     */
    private function walk(array $value, string $path, array &$errors, array &$declared, array &$references, ?string $parentType = null, string $key = ''): void
    {
        if ($value === []) {
            $errors[] = $path.' is empty.';

            return;
        }

        if (array_is_list($value)) {
            foreach ($value as $index => $item) {
                $this->visit($item, $path.'['.$index.']', $errors, $declared, $references, $parentType, $key);
            }

            return;
        }

        if (array_keys($value) === ['@id']) {
            if (is_string($value['@id'])) {
                $this->checkUrl($value['@id'], $path.'.@id', $errors);
                $references[$path] = $value['@id'];
            }

            return;
        }

        $types = $this->types($value);

        if (isset($value['@id']) && is_string($value['@id'])) {
            $declared[$value['@id']] = true;
        }

        foreach ($types as $type) {
            foreach (self::REQUIRED[$type] ?? [] as $required) {
                if (! array_key_exists($required, $value)) {
                    $errors[] = $path.' ('.$type.') is missing required property '.$required.'.';
                }
            }

            $this->checkType($type, $value, $path, $errors, $parentType);
        }

        foreach ($value as $key => $item) {
            if ($key === '@type') {
                continue;
            }

            $this->visit($item, $path.'.'.$key, $errors, $declared, $references, $types[0] ?? null, (string) $key);
        }
    }

    /**
     * @param  list<string>  $errors
     * @param  array<string, true>  $declared
     * @param  array<string, string>  $references
     */
    private function visit(mixed $item, string $path, array &$errors, array &$declared, array &$references, ?string $parentType, string $key = ''): void
    {
        if (is_array($item)) {
            $this->walk($item, $path, $errors, $declared, $references, $parentType, $key);

            return;
        }

        if ($item === null || (is_string($item) && trim($item) === '')) {
            $errors[] = $path.' is empty.';

            return;
        }

        if (in_array($key, self::URL_KEYS, true) && is_string($item)) {
            $this->checkUrl($item, $path, $errors);
        }

        if ($key === 'dateModified' && is_string($item) && preg_match('/^\d{4}-\d{2}-\d{2}/', $item) !== 1) {
            $errors[] = $path.' must be an ISO 8601 date.';
        }
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<string>  $errors
     */
    private function checkType(string $type, array $node, string $path, array &$errors, ?string $parentType): void
    {
        $label = $path.' ('.$type.')';

        if (in_array($type, ['Offer', 'UnitPriceSpecification'], true)) {
            if (isset($node['price']) && (! is_numeric($node['price']) || (float) $node['price'] <= 0)) {
                $errors[] = $label.' price must be a positive number.';
            }

            if (isset($node['priceCurrency']) && (! is_string($node['priceCurrency']) || preg_match('/^[A-Z]{3}$/', $node['priceCurrency']) !== 1)) {
                $errors[] = $label.' priceCurrency must be a three-letter ISO 4217 code.';
            }
        }

        if ($type === 'AggregateRating') {
            if (! isset($node['reviewCount']) && ! isset($node['ratingCount'])) {
                $errors[] = $label.' needs reviewCount or ratingCount.';
            }

            $value = $node['ratingValue'] ?? null;
            $worst = (float) ($node['worstRating'] ?? 1);
            $best = (float) ($node['bestRating'] ?? 5);

            if (isset($node['ratingValue']) && (! is_numeric($value) || (float) $value < $worst || (float) $value > $best)) {
                $errors[] = $label.' ratingValue must be a number between worstRating and bestRating.';
            }
        }

        if ($type === 'ListItem') {
            if (! isset($node['position']) || ! is_int($node['position']) || $node['position'] < 1) {
                $errors[] = $label.' position must be a positive integer.';
            }

            if ($parentType === 'BreadcrumbList' && ! isset($node['name'])) {
                $errors[] = $label.' needs a name inside a BreadcrumbList.';
            }

            if (! isset($node['item'])) {
                $errors[] = $label.' needs an item.';
            }
        }

        if (in_array($type, ['BreadcrumbList', 'ItemList'], true) && is_array($node['itemListElement'] ?? null)) {
            $positions = array_column($node['itemListElement'], 'position');

            if ($type === 'BreadcrumbList' && $positions !== range(1, count($positions))) {
                $errors[] = $label.' positions must run 1..n in order.';
            }

            if ($positions !== [] && $positions !== array_values(array_unique($positions))) {
                $errors[] = $label.' positions must be unique.';
            }
        }

        if ($type === 'FAQPage' && isset($node['mainEntity']) && ! array_is_list((array) $node['mainEntity']) && array_keys((array) $node['mainEntity']) !== ['@id']) {
            $errors[] = $label.' mainEntity must be a list of questions.';
        }
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<string>
     */
    private function types(array $node): array
    {
        $type = $node['@type'] ?? null;

        if (is_string($type)) {
            return [$type];
        }

        return is_array($type) ? array_values(array_filter($type, 'is_string')) : [];
    }

    /**
     * @param  list<string>  $errors
     */
    private function checkUrl(string $url, string $path, array &$errors): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false || preg_match('#^https?://#i', $url) !== 1) {
            $errors[] = $path.' must be an absolute http(s) URL, got "'.$url.'".';
        }
    }
}
