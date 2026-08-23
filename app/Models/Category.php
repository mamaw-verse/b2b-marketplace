<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $slug
 * @property array<string, array<string, mixed>> $variant_attributes_schema
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'variant_attributes_schema',
    ];

    /**
     * Shops with this category as a primary category.
     */
    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(Shop::class, 'shop_categories');
    }

    /**
     * Products assigned to this category.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    protected function casts(): array
    {
        return [
            'variant_attributes_schema' => 'array',
        ];
    }
}
