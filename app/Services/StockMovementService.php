<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    public function paginate(Product $product, int $perPage = 15): LengthAwarePaginator
    {
        return $product->stockMovements()
            ->with('user')
            ->latest()
            ->paginate($perPage);
    }

    public function create(Product $product, array $data, Request $request): StockMovement
    {
        return DB::transaction(function () use ($product, $data, $request) {
            // Lock the row to prevent race conditions
            $product = Product::lockForUpdate()->findOrFail($product->id);

            if ($data['type'] === 'salida' && $product->stock < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => ["Insufficient stock. Available: {$product->stock}"],
                ]);
            }

            $delta = $data['type'] === 'entrada' ? $data['quantity'] : -$data['quantity'];
            $product->increment('stock', $delta);

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'type'       => $data['type'],
                'quantity'   => $data['quantity'],
                'reason'     => $data['reason'] ?? null,
                'user_id'    => $request->user()?->id,
            ]);

            AuditService::log(
                'stock.' . $data['type'],
                Product::class,
                $product->id,
                ['stock' => $product->stock - $delta],
                ['stock' => $product->stock, 'quantity' => $data['quantity']],
                $request
            );

            return $movement->load('product');
        });
    }
}
