<?php

declare(strict_types=1);

use App\Support\Schema\SchemaGraph;

test('the graph has a single context and a graph list', function () {
    $graph = (new SchemaGraph)->add(['@type' => 'Organization', '@id' => 'https://x.test/#organization', 'name' => 'Acme']);

    expect($graph->toArray())->toBe([
        '@context' => 'https://schema.org',
        '@graph' => [['@type' => 'Organization', '@id' => 'https://x.test/#organization', 'name' => 'Acme']],
    ]);
});

test('nodes with the same id are merged instead of duplicated', function () {
    $graph = (new SchemaGraph)
        ->add(['@type' => 'Organization', '@id' => 'https://x.test/#o', 'name' => 'Acme', 'url' => 'https://x.test/'])
        ->add(['@type' => 'Organization', '@id' => 'https://x.test/#o', 'name' => 'Acme Ltd', 'description' => 'Halal supplier']);

    $nodes = $graph->toArray()['@graph'];

    expect($nodes)->toHaveCount(1)
        ->and($nodes[0])->toMatchArray(['name' => 'Acme Ltd', 'url' => 'https://x.test/', 'description' => 'Halal supplier'])
        ->and($graph->ids())->toBe(['https://x.test/#o'])
        ->and($graph->has('https://x.test/#o'))->toBeTrue()
        ->and($graph->has('https://x.test/#missing'))->toBeFalse();
});

test('null, blank and empty values are stripped recursively but zero and false are kept', function () {
    $graph = (new SchemaGraph)->add([
        '@type' => 'Product',
        '@id' => 'https://x.test/p#product',
        'name' => 'Rice',
        'description' => '   ',
        'sku' => null,
        'image' => [],
        'offers' => ['@type' => 'Offer', 'price' => '', 'priceCurrency' => null],
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => null],
        'additionalProperty' => [
            ['@type' => 'PropertyValue', 'name' => 'Grade', 'value' => 'A'],
            ['@type' => 'PropertyValue', 'name' => null, 'value' => null],
        ],
        'isFamilyFriendly' => false,
        'minValue' => 0,
    ]);

    expect($graph->toArray()['@graph'][0])->toBe([
        '@type' => 'Product',
        '@id' => 'https://x.test/p#product',
        'name' => 'Rice',
        'additionalProperty' => [['@type' => 'PropertyValue', 'name' => 'Grade', 'value' => 'A']],
        'isFamilyFriendly' => false,
        'minValue' => 0,
    ]);
});

test('lists are re-indexed after empty entries are removed', function () {
    $graph = (new SchemaGraph)->add([
        '@type' => 'Organization',
        '@id' => 'https://x.test/#o',
        'sameAs' => ['', 'https://example.com/a', null, 'https://example.com/b'],
    ]);

    expect($graph->toArray()['@graph'][0]['sameAs'])->toBe(['https://example.com/a', 'https://example.com/b']);
});

test('nodes with nothing but an id and a type are not added', function () {
    $graph = (new SchemaGraph)
        ->add(['@type' => 'ItemList', '@id' => 'https://x.test/#list', 'itemListElement' => []])
        ->add(SchemaGraph::ref('https://x.test/#o'));

    expect($graph->toArray()['@graph'])->toBe([])
        ->and($graph->ids())->toBe([]);
});

test('ref returns a bare id reference', function () {
    expect(SchemaGraph::ref('https://x.test/#o'))->toBe(['@id' => 'https://x.test/#o']);
});

test('references inside a node survive cleaning', function () {
    $graph = (new SchemaGraph)->add([
        '@type' => 'WebSite',
        '@id' => 'https://x.test/#website',
        'publisher' => SchemaGraph::ref('https://x.test/#organization'),
    ]);

    expect($graph->toArray()['@graph'][0]['publisher'])->toBe(['@id' => 'https://x.test/#organization']);
});

test('hostile strings cannot break out of the script element', function () {
    $hostile = '</script><script>alert(1)</script> & "quoted" <!-- x -->';

    $json = (new SchemaGraph)
        ->add(['@type' => 'Product', '@id' => 'https://x.test/p#product', 'name' => $hostile])
        ->toJson();

    expect($json)->not->toContain('</script>')
        ->and($json)->not->toContain('<script>')
        ->and($json)->not->toContain('<!--')
        ->and($json)->not->toContain('&')
        ->and(json_decode($json, true)['@graph'][0]['name'])->toBe($hostile);
});

test('slashes and unicode are left readable', function () {
    $json = (new SchemaGraph)
        ->add(['@type' => 'Organization', '@id' => 'https://x.test/#o', 'name' => 'شركة حلال', 'url' => 'https://x.test/a/b'])
        ->toJson();

    expect($json)->toContain('https://x.test/a/b')->and($json)->toContain('شركة حلال');
});

test('invalid utf-8 is substituted instead of throwing', function () {
    $json = (new SchemaGraph)
        ->add(['@type' => 'Product', '@id' => 'https://x.test/p#product', 'name' => "Bad \xB1 bytes"])
        ->toJson();

    expect(json_decode($json, true))->toBeArray();
});

test('non-finite numbers are dropped', function () {
    $graph = (new SchemaGraph)->add(['@type' => 'Offer', '@id' => 'https://x.test/#offer', 'price' => NAN, 'priceCurrency' => 'PHP']);

    expect($graph->toArray()['@graph'][0])->not->toHaveKey('price');
});
