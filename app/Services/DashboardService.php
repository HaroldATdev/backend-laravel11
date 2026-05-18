<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const STATS_TTL = 60; // seconds

    public function stats(): array
    {
        return Cache::remember('dashboard.stats', self::STATS_TTL, function () {
            return [
                'total_products'   => Product::count(),
                'active_products'  => Product::active()->count(),
                'total_categories' => \App\Models\Category::count(),
                'low_stock_count'  => Product::where('stock', '<=', 10)->active()->count(),
                'total_movements'  => StockMovement::count(),
            ];
        });
    }

    public function lowStockProducts(int $threshold = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Product::with('category')
            ->active()
            ->where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->limit(20)
            ->get();
    }

    public function lastMovements(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return StockMovement::with(['product', 'user'])
            ->latest()
            ->limit($limit)
            ->get();
    }
}
