<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
    }

    // -----------------------------------------------------------------------
    // Index
    // -----------------------------------------------------------------------

    public function test_index_returns_paginated_orders(): void
    {
        Order::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'status', 'total']],
                'links',
                'meta',
            ]);
    }

    public function test_index_filters_by_status(): void
    {
        Order::factory()->pending()->create(['user_id' => $this->user->id]);
        Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        Order::factory()->cancelled()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->getJson('/api/orders?status=pending');

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $order) {
            $this->assertSame('pending', $order['status']);
        }
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/orders');
        $response->assertStatus(401);
    }

    // -----------------------------------------------------------------------
    // Store
    // -----------------------------------------------------------------------

    public function test_can_create_order_with_items(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/orders', [
            'notes' => 'Please handle with care',
            'items' => [
                ['product_name' => 'Widget A', 'quantity' => 2, 'price' => 10.00],
                ['product_name' => 'Widget B', 'quantity' => 1, 'price' => 25.00],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->assertEquals(45.00, $response->json('data.total'));
        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_store_validates_required_items(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/orders', [
            'notes' => 'Missing items array',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_store_validates_item_fields(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/orders', [
            'items' => [
                ['product_name' => '', 'quantity' => 0, 'price' => -1],
            ],
        ]);

        $response->assertStatus(422);
    }

    // -----------------------------------------------------------------------
    // Show
    // -----------------------------------------------------------------------

    public function test_can_retrieve_single_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_show_returns_404_for_missing_order(): void
    {
        $response = $this->withToken($this->token)->getJson('/api/orders/999999');
        $response->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // Update
    // -----------------------------------------------------------------------

    public function test_can_update_order_status(): void
    {
        $order = Order::factory()->pending()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->putJson("/api/orders/{$order->id}", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_can_update_order_items(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $response = $this->withToken($this->token)->putJson("/api/orders/{$order->id}", [
            'items' => [
                ['product_name' => 'New Product', 'quantity' => 3, 'price' => 20.00],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals(60.00, $response->json('data.total'));
    }

    // -----------------------------------------------------------------------
    // Destroy
    // -----------------------------------------------------------------------

    public function test_can_delete_order_without_payments(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->deleteJson("/api/orders/{$order->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_cannot_delete_order_with_payments(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->withToken($this->token)->deleteJson("/api/orders/{$order->id}");
        $response->assertStatus(422);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
