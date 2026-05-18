<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'price'       => fake()->randomFloat(2, 1, 999),
            'stock'       => fake()->numberBetween(0, 200),
            'category_id' => null,
            'status'      => true,
        ];
    }
}
