<?php

declare(strict_types=1);

use App\Support\Schema\JsonLdValidator;

/**
 * A small document that passes every rule, mutated by the cases below.
 *
 * @return array<string, mixed>
 */
function validJsonLdDocument(): array
{
    return [
        '@context' => 'https://schema.org',
        '@graph' => [
            ['@type' => 'Organization', '@id' => 'https://x.test/#organization', 'name' => 'Acme', 'url' => 'https://x.test/', 'sameAs' => ['https://www.facebook.com/acme']],
            ['@type' => 'WebSite', '@id' => 'https://x.test/#website', 'name' => 'Acme', 'url' => 'https://x.test/', 'publisher' => ['@id' => 'https://x.test/#organization']],
            ['@type' => 'BreadcrumbList', '@id' => 'https://x.test/products#breadcrumb', 'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://x.test/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Products', 'item' => 'https://x.test/products'],
            ]],
            ['@type' => 'Product', '@id' => 'https://x.test/products/a#product', 'url' => 'https://x.test/products/a', 'name' => 'A', 'image' => ['https://x.test/a.jpg'], 'offers' => ['@type' => 'Offer', 'price' => '10.00', 'priceCurrency' => 'PHP']],
        ],
    ];
}

/**
 * @param  array<string, mixed>  $document
 */
function jsonLdPage(array $document, string $head = '', string $body = ''): string
{
    $json = json_encode($document, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);

    return '<html><head>'.$head.'<script type="application/ld+json">'.$json.'</script></head><body>'.$body.'</body></html>';
}

test('a well-formed document has no problems', function () {
    expect((new JsonLdValidator)->validate(validJsonLdDocument()))->toBe([]);
});

test('each rule reports the matching problem', function (Closure $mutate, string $expected) {
    $errors = (new JsonLdValidator)->validate($mutate(validJsonLdDocument()));

    expect(implode("\n", $errors))->toContain($expected);
})->with([
    'wrong context' => [function (array $d): array {
        $d['@context'] = 'http://schema.org';

        return $d;
    }, '@context must be exactly https://schema.org'],
    'extra top-level key' => [function (array $d): array {
        $d['extra'] = 1;

        return $d;
    }, 'Unexpected top-level keys: extra'],
    'empty graph' => [function (array $d): array {
        $d['@graph'] = [];

        return $d;
    }, '@graph must be a non-empty list'],
    'node without a type' => [function (array $d): array {
        unset($d['@graph'][1]['@type']);

        return $d;
    }, 'has no @type'],
    'repeated top-level id' => [function (array $d): array {
        $d['@graph'][1]['@id'] = $d['@graph'][0]['@id'];

        return $d;
    }, 'repeats @id https://x.test/#organization'],
    'dangling reference' => [function (array $d): array {
        $d['@graph'][1]['publisher'] = ['@id' => 'https://x.test/#missing'];

        return $d;
    }, 'references https://x.test/#missing, which is not declared'],
    'empty string' => [function (array $d): array {
        $d['@graph'][0]['name'] = '';

        return $d;
    }, '@graph[0].name is empty'],
    'empty list' => [function (array $d): array {
        $d['@graph'][0]['sameAs'] = [];

        return $d;
    }, '@graph[0].sameAs is empty'],
    'relative url' => [function (array $d): array {
        $d['@graph'][0]['url'] = '/home';

        return $d;
    }, 'absolute http(s) URL'],
    'relative url inside a list' => [function (array $d): array {
        $d['@graph'][0]['sameAs'] = ['/profile'];

        return $d;
    }, '@graph[0].sameAs[0] must be an absolute http(s) URL'],
    'non-http scheme' => [function (array $d): array {
        $d['@graph'][0]['sameAs'] = ['ftp://x.test/file'];

        return $d;
    }, 'absolute http(s) URL'],
    'relative image' => [function (array $d): array {
        $d['@graph'][3]['image'] = ['a.jpg'];

        return $d;
    }, '@graph[3].image[0] must be an absolute http(s) URL'],
    'missing required property' => [function (array $d): array {
        unset($d['@graph'][3]['name']);

        return $d;
    }, '(Product) is missing required property name'],
    'bad currency' => [function (array $d): array {
        $d['@graph'][3]['offers']['priceCurrency'] = 'php';

        return $d;
    }, 'priceCurrency must be a three-letter ISO 4217 code'],
    'zero price' => [function (array $d): array {
        $d['@graph'][3]['offers']['price'] = '0.00';

        return $d;
    }, 'price must be a positive number'],
    'rating without a count' => [function (array $d): array {
        $d['@graph'][3]['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => 4.5];

        return $d;
    }, 'needs reviewCount or ratingCount'],
    'rating out of range' => [function (array $d): array {
        $d['@graph'][3]['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => 7, 'reviewCount' => 1];

        return $d;
    }, 'ratingValue must be a number between worstRating and bestRating'],
    'breadcrumb positions out of order' => [function (array $d): array {
        $d['@graph'][2]['itemListElement'][1]['position'] = 3;

        return $d;
    }, 'positions must run 1..n in order'],
    'breadcrumb item without a url' => [function (array $d): array {
        unset($d['@graph'][2]['itemListElement'][0]['item']);

        return $d;
    }, 'needs an item'],
    'breadcrumb item without a name' => [function (array $d): array {
        unset($d['@graph'][2]['itemListElement'][0]['name']);

        return $d;
    }, 'needs a name inside a BreadcrumbList'],
    'malformed date' => [function (array $d): array {
        $d['@graph'][3]['dateModified'] = 'yesterday';

        return $d;
    }, 'must be an ISO 8601 date'],
]);

test('a nested id may be referenced, and a reference to a top-level id resolves', function () {
    $document = validJsonLdDocument();
    $document['@graph'][0]['logo'] = ['@type' => 'ImageObject', '@id' => 'https://x.test/#logo', 'url' => 'https://x.test/logo.png'];
    $document['@graph'][1]['image'] = ['@id' => 'https://x.test/#logo'];

    expect((new JsonLdValidator)->validate($document))->toBe([]);
});

test('list items under an item list may carry an object instead of a url, and positions may continue across pages', function () {
    $document = validJsonLdDocument();
    $document['@graph'][] = ['@type' => 'ItemList', '@id' => 'https://x.test/products?page=2#list', 'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 25, 'item' => ['@type' => 'Product', '@id' => 'https://x.test/products/b#product', 'url' => 'https://x.test/products/b', 'name' => 'B']],
        ['@type' => 'ListItem', 'position' => 26, 'item' => ['@type' => 'Product', '@id' => 'https://x.test/products/c#product', 'url' => 'https://x.test/products/c', 'name' => 'C']],
    ]];

    expect((new JsonLdValidator)->validate($document))->toBe([]);
});

test('a FAQ page needs question nodes with an accepted answer text', function () {
    $faq = static fn (array $question): array => [
        '@context' => 'https://schema.org',
        '@graph' => [
            ['@type' => 'FAQPage', '@id' => 'https://x.test/faq#webpage', 'url' => 'https://x.test/faq', 'mainEntity' => [['@id' => 'https://x.test/faq#q1']]],
            ['@type' => 'Question', '@id' => 'https://x.test/faq#q1', ...$question],
        ],
    ];

    $valid = $faq(['name' => 'What is it?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'A marketplace.']]);
    $noAnswerText = $faq(['name' => 'What is it?', 'acceptedAnswer' => ['@type' => 'Answer', 'url' => 'https://x.test/faq']]);
    $noAnswer = $faq(['name' => 'What is it?']);

    expect((new JsonLdValidator)->validate($valid))->toBe([])
        ->and(implode("\n", (new JsonLdValidator)->validate($noAnswerText)))->toContain('(Answer) is missing required property text')
        ->and(implode("\n", (new JsonLdValidator)->validate($noAnswer)))->toContain('(Question) is missing required property acceptedAnswer');
});

test('a rendered page passes only with exactly one JSON-LD script inside the head', function () {
    $validator = new JsonLdValidator;
    $document = validJsonLdDocument();

    expect($validator->validateHtml(jsonLdPage($document)))->toBe([])
        ->and($validator->validateHtml('<html><head></head><body></body></html>'))->toBe(['Expected exactly one JSON-LD script, found 0.']);

    $script = '<script type="application/ld+json">'.json_encode($document, JSON_UNESCAPED_SLASHES).'</script>';

    expect($validator->validateHtml('<html><head>'.$script.$script.'</head></html>'))->toBe(['Expected exactly one JSON-LD script, found 2.'])
        ->and($validator->validateHtml('<html><head></head><body>'.$script.'</body></html>'))->toBe(['The JSON-LD script is rendered outside the document head.'])
        ->and($validator->validateHtml('<html><head><script type="application/ld+json">{nope</script></head></html>')[0])->toStartWith('The JSON-LD script is not valid JSON');
});

test('hex-escaped hostile text still decodes and validates', function () {
    $document = validJsonLdDocument();
    $document['@graph'][3]['name'] = '</script><script>alert(1)</script> & "quotes"';

    $html = jsonLdPage($document);

    expect(substr_count($html, '<script'))->toBe(1)
        ->and((new JsonLdValidator)->validateHtml($html))->toBe([]);
});
