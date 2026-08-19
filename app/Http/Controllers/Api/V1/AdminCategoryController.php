<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class AdminCategoryController extends Controller
{
    /**
     * GET /api/v1/admin/categories — List all categories.
     */
    public function index(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();

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
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'slug' => Str::slug($validated['name']),
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
        ]);

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json(['data' => $category->fresh()]);
    }

    /**
     * DELETE /api/v1/admin/categories/{category} — Delete a category.
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }
}
