<?php

test('every translation key used by the legal views exists in all locale dictionaries', function () {
    $files = [
        'partials/legal/terms-body.blade.php',
        'partials/legal/privacy-body.blade.php',
        'partials/legal/doc-nav.blade.php',
        'livewire/legal/terms-page.blade.php',
        'livewire/legal/privacy-page.blade.php',
        'livewire/legal/faq-page.blade.php',
        // The FAQ text and the trust sentences it reuses live in PHP classes, not in the view.
        'app/Support/Seo/FaqEntries.php',
        'app/Support/Seo/TrustFacts.php',
    ];

    $keys = [];
    foreach ($files as $file) {
        $contents = file_get_contents(str_starts_with($file, 'app/') ? base_path($file) : resource_path('views/'.$file));
        preg_match_all("/__\(\'((?:[^\'\\\\]|\\\\.)*)\'\)/u", $contents, $single);
        preg_match_all('/__\("((?:[^"\\\\]|\\\\.)*)"\)/u', $contents, $double);
        foreach (array_merge($single[1], $double[1]) as $key) {
            $keys[$key] = true;
        }
    }

    expect($keys)->not->toBeEmpty();

    foreach (['en', 'ar', 'id', 'ms', 'tr'] as $locale) {
        $dictionary = json_decode(file_get_contents(lang_path($locale.'.json')), true);
        foreach (array_keys($keys) as $key) {
            $this->assertArrayHasKey($key, $dictionary, "Missing [{$key}] in {$locale}.json");
        }
    }
});

test('legal pages render translated content in every supported locale', function () {
    $cases = [
        'ar' => ['terms' => 'شروط الخدمة', 'privacy' => 'سياسة الخصوصية', 'faq' => 'الأسئلة الشائعة', 'section' => '1. نبذة عن 1HalalStore.com'],
        'id' => ['terms' => 'Syarat Layanan', 'privacy' => 'Kebijakan Privasi', 'faq' => 'Pertanyaan yang Sering Diajukan', 'section' => '1. Tentang 1HalalStore.com'],
        'ms' => ['terms' => 'Terma Perkhidmatan', 'privacy' => 'Dasar Privasi', 'faq' => 'Soalan-soalan Lazim', 'section' => '1. Tentang 1HalalStore.com'],
        'tr' => ['terms' => 'Hizmet Koşulları', 'privacy' => 'Gizlilik Politikası', 'faq' => 'Sık Sorulan Sorular', 'section' => '1. 1HalalStore.com Hakkında'],
    ];

    foreach ($cases as $locale => $expected) {
        $this->withSession(['locale' => $locale]);

        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee($expected['terms'], false)
            ->assertSee($expected['section'], false)
            ->assertDontSee('1. About 1HalalStore.com', false);

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee($expected['privacy'], false);

        $this->get(route('help.faq'))
            ->assertOk()
            ->assertSee($expected['faq'], false);
    }
});
