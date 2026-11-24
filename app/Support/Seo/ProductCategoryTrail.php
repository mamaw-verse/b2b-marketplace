<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Models\Category;
use App\Models\Product;

/**
 * The category a product page shows as its primary chip: the first category
 * that has a parent (top-level "marketing" categories are never shown). The
 * page links only that category, so the breadcrumb uses only it; the parent
 * appears as text, never as a link (plan T2.3: schema must mirror the UI).
 */
final class ProductCategoryTrail
{
    public static function primary(Product $product): ?Category
    {
        $product->loadMissing('categories.parent');

        return $product->categories->first(static fn (Category $category): bool => $category->parent !== null);
    }

    /**
     * Names as the chip reads them: parent then category.
     *
     * @return list<string>
     */
    public static function names(Product $product): array
    {
        $category = self::primary($product);

        return $category === null ? [] : [$category->parent->name, $category->name];
    }
}
