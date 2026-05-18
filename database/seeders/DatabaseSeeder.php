<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin user ─────────────────────────────────────────────────
        DB::table('users')->insertOrIgnore([
            'name'       => 'Admin',
            'email'      => 'admin@inventory.test',
            'password'   => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── 100 categories (single batch) ──────────────────────────────
        $categories = [];
        for ($i = 1; $i <= 100; $i++) {
            $categories[] = [
                'name'        => "Categoría {$i}",
                'description' => "Descripción de categoría {$i}",
                'status'      => $i % 7 !== 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }
        DB::table('categories')->insert($categories);

        // ── 10,000 products (chunks of 500) ────────────────────────────
        $chunkSize = 500;
        $productBatch = [];
        for ($i = 1; $i <= 10000; $i++) {
            $productBatch[] = [
                'name'        => "Producto {$i}",
                'description' => "Descripción del producto número {$i}",
                'price'       => round(rand(100, 300000) / 100, 2),
                'stock'       => rand(0, 200),
                'category_id' => rand(1, 100),
                'status'      => $i % 9 !== 0,
                'created_at'  => now()->subDays(rand(0, 365)),
                'updated_at'  => now(),
            ];

            if (count($productBatch) === $chunkSize) {
                DB::table('products')->insert($productBatch);
                $productBatch = [];
            }
        }
        if ($productBatch) {
            DB::table('products')->insert($productBatch);
        }

        // ── 30,000 stock movements (chunks of 500) ─────────────────────
        $movementBatch = [];
        for ($i = 1; $i <= 30000; $i++) {
            $movementBatch[] = [
                'product_id' => rand(1, 10000),
                'type'       => rand(0, 1) ? 'entrada' : 'salida',
                'quantity'   => rand(1, 20),
                'reason'     => "Movimiento inicial #{$i}",
                'user_id'    => 1,
                'created_at' => now()->subDays(rand(0, 180)),
                'updated_at' => now(),
            ];

            if (count($movementBatch) === $chunkSize) {
                DB::table('stock_movements')->insert($movementBatch);
                $movementBatch = [];
            }
        }
        if ($movementBatch) {
            DB::table('stock_movements')->insert($movementBatch);
        }
    }
}
