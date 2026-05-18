<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'stock', 'category_id', 'status'];

    protected function casts(): array
    {
        return [
            'price'  => 'decimal:2',
            'stock'  => 'integer',
            'status' => 'boolean',
        ];
    }

    // ---- Relations ----

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // ---- Scopes ----

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['name'] ?? null, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($filters['category_id'] ?? null, fn($q, $v) => $q->where('category_id', $v))
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when($filters['price_min'] ?? null, fn($q, $v) => $q->where('price', '>=', $v))
            ->when($filters['price_max'] ?? null, fn($q, $v) => $q->where('price', '<=', $v))
            ->when($filters['stock_min'] ?? null, fn($q, $v) => $q->where('stock', '>=', $v))
            ->when($filters['stock_max'] ?? null, fn($q, $v) => $q->where('stock', '<=', $v));
    }

    public function scopeSortBy(Builder $query, ?string $sort, string $direction = 'asc'): Builder
    {
        $allowed = ['name', 'price', 'stock', 'created_at'];
        $col = in_array($sort, $allowed, true) ? $sort : 'created_at';

        return $query->orderBy($col, $direction === 'desc' ? 'desc' : 'asc');
    }
}
