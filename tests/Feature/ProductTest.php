<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user  = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_list_products_requires_auth(): void
    {
        $this->getJson('/api/products')->assertStatus(401);
    }

    public function test_list_products_is_paginated(): void
    {
        Product::factory()->count(3)->create();

        $this->withToken($this->token)
            ->getJson('/api/products')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['data', 'current_page', 'total']]);
    }

    public function test_create_product(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/products', [
                'name'  => 'Widget Pro',
                'price' => 29.99,
                'stock' => 50,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Widget Pro');
    }

    public function test_create_product_validates_required_fields(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/products', [])
            ->assertStatus(422);
    }

    public function test_show_product(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->token)
            ->getJson("/api/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_update_product(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);

        $this->withToken($this->token)
            ->putJson("/api/products/{$product->id}", ['price' => 25.00])
            ->assertStatus(200)
            ->assertJsonPath('data.price', '25.00');
    }

    public function test_delete_product(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->token)
            ->deleteJson("/api/products/{$product->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_stock_movement_entrada_increases_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->withToken($this->token)
            ->postJson("/api/products/{$product->id}/stock-movements", [
                'type'     => 'entrada',
                'quantity' => 5,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 15]);
    }

    public function test_stock_movement_salida_decreases_stock(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $this->withToken($this->token)
            ->postJson("/api/products/{$product->id}/stock-movements", [
                'type'     => 'salida',
                'quantity' => 4,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 6]);
    }

    public function test_stock_movement_salida_fails_on_insufficient_stock(): void
    {
        $product = Product::factory()->create(['stock' => 2]);

        $this->withToken($this->token)
            ->postJson("/api/products/{$product->id}/stock-movements", [
                'type'     => 'salida',
                'quantity' => 10,
            ])
            ->assertStatus(422);
    }

    public function test_product_filter_by_name(): void
    {
        Product::factory()->create(['name' => 'Needle in Haystack']);
        Product::factory()->create(['name' => 'Something Else']);

        $response = $this->withToken($this->token)
            ->getJson('/api/products?name=Needle')
            ->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Needle', $data[0]['name']);
    }
}
