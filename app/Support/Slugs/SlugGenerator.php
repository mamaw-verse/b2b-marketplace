<?php

declare(strict_types=1);

namespace App\Support\Slugs;

use App\Models\Product;
use App\Models\Shop;
use App\Models\SlugRedirect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

final class SlugGenerator
{
    private const int BASE_MAX_LENGTH = 90;

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function generate(string $modelClass, string $source, ?int $ignoreId = null): string
    {
        $fallback = match ($modelClass) {
            Shop::class => 'store',
            Product::class => 'product',
            default => throw new \InvalidArgumentException('Slugs are only supported for shops and products.'),
        };

        $base = Str::of(Str::slug($source, '-', app()->getLocale()))
            ->limit(self::BASE_MAX_LENGTH, '')
            ->trim('-')
            ->toString();

        if ($base === '' || ctype_digit($base) || in_array($base, config('marketplace.slugs.reserved', []), true)) {
            $base = $fallback.'-'.Str::lower(Str::random(6));
        }

        for ($suffix = 1; ; $suffix++) {
            $candidate = $suffix === 1 ? $base : $this->appendSuffix($base, $suffix);

            if (! $this->isTaken($modelClass, $candidate, $ignoreId)) {
                return $candidate;
            }
        }
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function isTaken(string $modelClass, string $slug, ?int $ignoreId): bool
    {
        $model = new $modelClass;
        $query = $model->newQuery();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        if ($query
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($builder) => $builder->where($model->getKeyName(), '!=', $ignoreId))
            ->exists()) {
            return true;
        }

        return SlugRedirect::query()
            ->where('sluggable_type', $modelClass)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($builder) => $builder->where('sluggable_id', '!=', $ignoreId))
            ->exists();
    }

    private function appendSuffix(string $base, int $suffix): string
    {
        $tail = '-'.$suffix;
        $prefix = rtrim(Str::substr($base, 0, 100 - strlen($tail)), '-');

        return $prefix.$tail;
    }
}
