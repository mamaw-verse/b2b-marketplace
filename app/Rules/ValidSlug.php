<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\SlugRedirect;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Translation\PotentiallyTranslatedString;

final class ValidSlug implements ValidationRule
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function __construct(private readonly string $modelClass, private readonly ?int $ignoreId = null) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || strlen($value) > 100 || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) !== 1 || ctype_digit($value)) {
            $fail(__('The :attribute must contain lowercase letters, numbers, and single hyphens, and cannot be numeric.'));

            return;
        }

        if (in_array($value, config('marketplace.slugs.reserved', []), true)) {
            $fail(__('This :attribute is reserved.'));

            return;
        }

        $model = new $this->modelClass;
        $query = $model->newQuery()->where('slug', $value);

        if (in_array(SoftDeletes::class, class_uses_recursive($this->modelClass), true)) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        if ($query->when($this->ignoreId !== null, fn ($builder) => $builder->whereKeyNot($this->ignoreId))->exists()) {
            $fail(__('This :attribute is already in use.'));

            return;
        }

        if (SlugRedirect::query()
            ->where('sluggable_type', $this->modelClass)
            ->where('slug', $value)
            ->when($this->ignoreId !== null, fn ($builder) => $builder->where('sluggable_id', '!=', $this->ignoreId))
            ->exists()) {
            $fail(__('This :attribute is already in use.'));
        }
    }
}
