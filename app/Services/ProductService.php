<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    public function paginate(
        array $filters = [],
        ?string $sort = null,
        string $direction = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return Product::with('category')
            ->filter($filters)
            ->sortBy($sort, $direction)
            ->paginate($perPage);
    }

    public function create(array $data, Request $request): Product
    {
        $product = Product::create($data);

        AuditService::log('product.created', Product::class, $product->id, null, $data, $request);

        return $product->load('category');
    }

    public function update(Product $product, array $data, Request $request): Product
    {
        $old = $product->toArray();

        $product->update($data);

        AuditService::log('product.updated', Product::class, $product->id, $old, $data, $request);

        return $product->refresh()->load('category');
    }

    public function delete(Product $product, Request $request): void
    {
        $old = $product->toArray();

        $product->delete();

        AuditService::log('product.deleted', Product::class, $product->id, $old, null, $request);
    }
}
