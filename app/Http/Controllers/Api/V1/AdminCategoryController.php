<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class AdminCategoryController extends Controller
{
    /**
     * GET /api/v1/admin/categories — List all categories (flat, with parent).
     */
    public function index(): JsonResponse
    {
        $categories = Category::with('parent:id,name')
            ->orderByRaw('COALESCE((SELECT p.sort_order FROM categories p WHERE p.id = categories.parent_id), categories.sort_order)')
            ->orderByRaw('parent_id IS NOT NULL')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }

    /**
     * POST /api/v1/admin/categories — Create a new category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:categories,name',
            'description' => 'nullable|string|max:1000',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('parent_id'),
            ],
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'slug' => Str::slug($validated['name']),
            'parent_id' => $validated['parent_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json(['data' => $category], 201);
    }

    /**
     * PUT /api/v1/admin/categories/{category} — Update an existing category.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'description' => 'nullable|string|max:1000',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->whereNull('parent_id'),
                Rule::notIn([$category->id]),
            ],
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if (($validated['parent_id'] ?? null) !== null && $category->isGroup()) {
            throw ValidationException::withMessages([
                'parent_id' => ['A category with subcategories cannot be nested.'],
            ]);
        }

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'slug' => Str::slug($validated['name']),
            'parent_id' => $validated['parent_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $category->sort_order,
        ]);

        return response()->json(['data' => $category->fresh()]);
    }

    /**
     * DELETE /api/v1/admin/categories/{category} — Delete a category.
     */
    public function destroy(Category $category): JsonResponse
    {
        if ($category->isGroup()) {
            return response()->json([
                'error' => [
                    'code' => 'CATEGORY_HAS_CHILDREN',
                    'message' => 'Delete its subcategories first.',
                    'fields' => new \stdClass,
                ],
            ], 422);
        }

        $category->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }
}
