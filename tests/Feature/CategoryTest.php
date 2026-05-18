<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    public function test_list_categories_requires_auth(): void
    {
        $this->getJson('/api/categories')->assertStatus(401);
    }

    public function test_list_categories_is_paginated(): void
    {
        Category::factory()->count(5)->create();

        $this->withToken($this->token)
            ->getJson('/api/categories')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['data', 'current_page', 'total']]);
    }

    public function test_create_category(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/categories', [
                'name'        => 'Electronics',
                'description' => 'Electronic devices',
                'status'      => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Electronics');
    }

    public function test_create_category_requires_unique_name(): void
    {
        Category::factory()->create(['name' => 'Duplicated']);

        $this->withToken($this->token)
            ->postJson('/api/categories', ['name' => 'Duplicated'])
            ->assertStatus(422);
    }

    public function test_show_category(): void
    {
        $category = Category::factory()->create();

        $this->withToken($this->token)
            ->getJson("/api/categories/{$category->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $category->id);
    }

    public function test_update_category(): void
    {
        $category = Category::factory()->create(['name' => 'Old Name']);

        $this->withToken($this->token)
            ->putJson("/api/categories/{$category->id}", ['name' => 'New Name'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_delete_category(): void
    {
        $category = Category::factory()->create();

        $this->withToken($this->token)
            ->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
