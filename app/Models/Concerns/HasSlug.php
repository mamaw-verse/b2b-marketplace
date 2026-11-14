<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\SlugRedirect;
use App\Support\Slugs\SlugGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

trait HasSlug
{
    abstract public function slugSource(): string;

    protected static function bootHasSlug(): void
    {
        static::updating(function (Model $model): void {
            $oldSlug = $model->getOriginal('slug');
            $newSlug = $model->getAttribute('slug');

            if (! $model->isDirty('slug') || blank($oldSlug)) {
                return;
            }

            SlugRedirect::query()->updateOrCreate(
                ['sluggable_type' => static::class, 'slug' => $oldSlug],
                ['sluggable_id' => $model->getKey()],
            );

            SlugRedirect::query()
                ->where('sluggable_type', static::class)
                ->where('sluggable_id', $model->getKey())
                ->where('slug', $newSlug)
                ->delete();
        });
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            if ($this->isDirty('slug')) {
                return DB::transaction(fn (): bool => parent::save($options));
            }

            return parent::save($options);
        }

        if (filled($this->getAttribute('slug'))) {
            return parent::save($options);
        }

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->setAttribute(
                'slug',
                app(SlugGenerator::class)->generate(static::class, $this->slugSource()),
            );

            try {
                return DB::transaction(fn (): bool => parent::save($options));
            } catch (UniqueConstraintViolationException $exception) {
                $this->setAttribute('slug', null);

                if ($attempt === 5) {
                    throw $exception;
                }
            }
        }

        return false;
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== 'slug') {
            if (($field === null || $field === $this->getKeyName()) && ! ctype_digit((string) $value)) {
                return null;
            }

            return parent::resolveRouteBinding($value, $field);
        }

        $current = $this->newQuery()->where('slug', $value)->first();

        if ($current !== null) {
            return $current;
        }

        $redirect = SlugRedirect::query()
            ->where('sluggable_type', static::class)
            ->where('slug', $value)
            ->first();

        if ($redirect !== null) {
            $model = $this->newQuery()->find($redirect->sluggable_id);

            if ($model !== null) {
                return $model;
            }
        }

        return ctype_digit((string) $value)
            ? $this->newQuery()->find((int) $value)
            : null;
    }
}
