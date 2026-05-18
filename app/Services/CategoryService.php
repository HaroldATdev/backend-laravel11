<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Category::filter($filters)
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data, Request $request): Category
    {
        $category = Category::create($data);

        AuditService::log('category.created', Category::class, $category->id, null, $data, $request);

        return $category;
    }

    public function update(Category $category, array $data, Request $request): Category
    {
        $old = $category->toArray();

        $category->update($data);

        AuditService::log('category.updated', Category::class, $category->id, $old, $data, $request);

        return $category->refresh();
    }

    public function delete(Category $category, Request $request): void
    {
        $old = $category->toArray();

        $category->delete();

        AuditService::log('category.deleted', Category::class, $category->id, $old, null, $request);
    }
}
