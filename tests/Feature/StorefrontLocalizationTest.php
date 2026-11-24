<?php

test('sourcing form, seller copy and interaction hints are translated in every locale', function () {
    $keys = [
        'Request Submitted',
        'We have received your sourcing request and will review it shortly.',
        'A confirmation email is on its way to',
        'Product Details',
        'Product Name / Type',
        'Product name',
        'Quantity',
        'optional',
        'e.g. 500 kg',
        'Target Budget',
        'e.g. PHP 50,000',
        'Product Description & Specifications',
        'Tell us about the size, material, color, features, technical specifications, or any other requirements.',
        'Reference Image / File',
        'Upload a product photo, specification sheet, catalog, or other reference. Max file size: 10MB',
        'Use my registered details',
        'We will use the contact details on your account:',
        'Email Address',
        'John Smith',
        'Leave this field empty',
        'Submit Sourcing Request',
        'Your Details',
        'How It Works',
        'Provide as much detail as possible about the product you need.',
        'A product name, description, photo, or reference link is enough to get started.',
        'Our sourcing team will search our supplier network for matching products.',
        'We will contact you with suitable options based on your requirements.',
        'If you have any questions or need assistance with the form, please reach out to our support team.',
        'We can source it for you. Tell us what you need, and our sourcing team will work with our supplier network to find suitable options based on your requirements, quantity, and target budget.',
        "Can't Find What You're Looking For?",
        'Showcase your products to verified buyers',
        'Supplier directory listing',
        'Tap to enlarge',
        'Hover to zoom · Click to enlarge',
        'Free',
        'Apply for Certificate',
        'Download the Letter of Intent',
        'Upload the Completed Letter',
        'Important Notes',
        'Need Help?',
        'Your Details',
        'Business Name',
        'Acme Foods Inc.',
        'Only DOC, DOCX, PDF, JPG, PNG and HEIC files are accepted.',
        'The Letter of Intent must not exceed 10 MB.',
    ];

    foreach (['en', 'ar', 'id', 'ms', 'tr'] as $locale) {
        $dictionary = json_decode(file_get_contents(lang_path($locale.'.json')), true);

        foreach ($keys as $key) {
            expect(array_key_exists($key, $dictionary))->toBeTrue("Missing [{$key}] in {$locale}.json");
            expect($dictionary[$key])->not->toBeEmpty();
        }
    }
});

test('product directory stats pluralize correctly in every locale', function () {
    $cases = [
        'en' => ['product|products', [1 => 'product', 0 => 'products', 3 => 'products']],
        'id' => ['product|products', [0 => 'produk', 1 => 'produk', 3 => 'produk']],
        'ms' => ['product|products', [0 => 'produk', 1 => 'produk', 3 => 'produk']],
        'tr' => ['product|products', [0 => 'ürün', 1 => 'ürün', 3 => 'ürün']],
        'ar' => ['product|products', [
            0 => 'منتج',
            1 => 'منتج',
            2 => 'منتجان',
            3 => 'منتجات',
            11 => 'منتجًا',
            100 => 'منتج',
        ]],
    ];

    foreach ($cases as $locale => [$key, $expected]) {
        app()->setLocale($locale);

        foreach ($expected as $count => $translation) {
            expect(trans_choice($key, $count))->toBe($translation);
        }
    }

    app()->setLocale('en');
});

test('legacy all-caps brand alias is removed from translated privacy copy', function () {
    foreach (['en', 'ar', 'id', 'ms', 'tr'] as $locale) {
        $dictionary = json_decode(file_get_contents(lang_path($locale.'.json')), true);

        $legacyValues = array_filter(
            $dictionary,
            fn (string $value): bool => str_contains($value, '1HALALSTORE'),
        );

        expect($legacyValues)->toBeEmpty("Legacy 1HALALSTORE alias still present in {$locale}.json");
    }
});

test('every translated storefront string exists in all locale dictionaries', function () {
    $files = [];

    foreach ([
        resource_path('views/livewire/storefront'),
        resource_path('views/components/shared'),
        resource_path('views/emails'),
        resource_path('views/partials'),
        app_path('Livewire/Storefront'),
    ] as $directory) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }

    $files[] = resource_path('views/layouts/storefront.blade.php');
    $files = array_merge($files, glob(resource_path('views/components/hs-*.blade.php')) ?: []);

    $keys = [];

    foreach ($files as $file) {
        $source = file_get_contents($file);

        preg_match_all("/__\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $source, $single);
        preg_match_all('/__\(\s*"((?:[^"\\\\]|\\\\.)*)"/', $source, $double);

        foreach ([...$single[1], ...$double[1]] as $key) {
            $key = stripcslashes(str_replace(["\\'", '\\"'], ["'", '"'], $key));

            if (trim($key) !== '') {
                $keys[$key] = true;
            }
        }
    }

    expect($keys)->not->toBeEmpty();

    foreach (['en', 'ar', 'id', 'ms', 'tr'] as $locale) {
        $dictionary = json_decode(file_get_contents(lang_path($locale.'.json')), true);

        foreach (array_keys($keys) as $key) {
            expect(array_key_exists($key, $dictionary))->toBeTrue("Missing [{$key}] in {$locale}.json");
            expect($dictionary[$key])->not->toBeEmpty();
        }
    }
});

test('sourcing request page renders translated copy', function () {
    $this->withSession(['locale' => 'ar'])
        ->get(route('sourcing-request.create'))
        ->assertOk()
        ->assertSee('لم تجد ما تبحث عنه؟', false)
        ->assertSee('الكمية', false)
        ->assertSee('الميزانية المستهدفة', false);
});
