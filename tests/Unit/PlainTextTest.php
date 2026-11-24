<?php

declare(strict_types=1);

use App\Support\Schema\PlainText;

test('markup is stripped and block elements keep words apart', function () {
    expect(PlainText::from('<p>Premium <strong>halal</strong> rice.</p><p>Packed in 25kg bags.</p>'))
        ->toBe('Premium halal rice. Packed in 25kg bags.')
        ->and(PlainText::from('Line one<br>Line two<div>Line three</div>'))
        ->toBe('Line one Line two Line three');
});

test('entities are decoded and whitespace is collapsed', function () {
    expect(PlainText::from("Fish &amp; chips&nbsp;&nbsp;\n\n  for   all &#8211; always"))->toBe('Fish & chips for all – always');
});

test('script and style blocks are removed with their content', function () {
    expect(PlainText::from('Safe<script>alert(1)</script> text<style>p{color:red}</style>'))->toBe('Safe text');
});

test('escaped markup does not survive as markup', function () {
    expect(PlainText::from('&lt;script&gt;alert(1)&lt;/script&gt;Halal'))->toBe('alert(1)Halal');
});

test('null and blank input become null', function () {
    expect(PlainText::from(null))->toBeNull()
        ->and(PlainText::from("  \n "))->toBeNull()
        ->and(PlainText::from('<p>&nbsp;</p>'))->toBeNull();
});

test('text is truncated on a character boundary with an ellipsis', function () {
    $text = PlainText::from(str_repeat('ا', 50), 20);

    expect(mb_strlen((string) $text))->toBe(20)->and($text)->toEndWith('…');
});

test('invalid utf-8 is scrubbed', function () {
    expect(PlainText::from("Halal \xB1 rice"))->toBeString()->and(mb_check_encoding((string) PlainText::from("Halal \xB1 rice"), 'UTF-8'))->toBeTrue();
});
