<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user  = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
    }

    // -----------------------------------------------------------------------
    // Store (process payment)
    // -----------------------------------------------------------------------

    public function test_can_process_payment_for_confirmed_order(): void
    {
        $order = Order::factory()->confirmed()->create([
            'user_id' => $this->user->id,
            'total'   => 100.00,
        ]);

        $response = $this->withToken($this->token)->postJson('/api/payments', [
            'order_id'        => $order->id,
            'payment_method'  => 'stripe',
            'idempotency_key' => 'unique-key-001',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'order_id', 'idempotency_key', 'payment_method', 'status', 'gateway_response'],
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id'        => $order->id,
            'idempotency_key' => 'unique-key-001',
        ]);
    }

    public function test_cannot_process_payment_for_pending_order(): void
    {
        $order = Order::factory()->pending()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->postJson('/api/payments', [
            'order_id'        => $order->id,
            'payment_method'  => 'paypal',
            'idempotency_key' => 'unique-key-002',
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_process_payment_for_cancelled_order(): void
    {
        $order = Order::factory()->cancelled()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->postJson('/api/payments', [
            'order_id'        => $order->id,
            'payment_method'  => 'credit_card',
            'idempotency_key' => 'unique-key-003',
        ]);

        $response->assertStatus(422);
    }

    public function test_payment_validates_order_id_exists(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/payments', [
            'order_id'        => 999999,
            'payment_method'  => 'stripe',
            'idempotency_key' => 'unique-key-004',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    public function test_payment_rejects_unsupported_method(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->postJson('/api/payments', [
            'order_id'        => $order->id,
            'payment_method'  => 'bitcoin',
            'idempotency_key' => 'unique-key-005',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_credit_card_payment_stores_gateway_response(): void
    {
        $order = Order::factory()->confirmed()->create([
            'user_id' => $this->user->id,
            'total'   => 100.00, // even → success
        ]);

        $response = $this->withToken($this->token)->postJson('/api/payments', [
            'order_id'        => $order->id,
            'payment_method'  => 'credit_card',
            'idempotency_key' => 'unique-key-006',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.payment_method', 'credit_card')
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.idempotency_key', 'unique-key-006');
    }

    // -----------------------------------------------------------------------
    // Idempotency
    // -----------------------------------------------------------------------

    public function test_duplicate_idempotency_key_returns_original_payment(): void
    {
        $order = Order::factory()->confirmed()->create([
            'user_id' => $this->user->id,
            'total'   => 100.00,
        ]);

        $payload = [
            'order_id'        => $order->id,
            'payment_method'  => 'stripe',
            'idempotency_key' => 'idem-replay-test',
        ];

        // First request — should create and return 201
        $first = $this->withToken($this->token)->postJson('/api/payments', $payload);
        $first->assertStatus(201);
        $firstPaymentId = $first->json('data.id');

        // Second request (replay) — should return 200 with the SAME payment
        $second = $this->withToken($this->token)->postJson('/api/payments', $payload);
        $second->assertStatus(200);
        $this->assertSame($firstPaymentId, $second->json('data.id'));

        // Only one payment row should exist in the database
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_idempotency_key_is_required(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->postJson('/api/payments', [
            'order_id'       => $order->id,
            'payment_method' => 'stripe',
            // idempotency_key intentionally omitted
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['idempotency_key']);
    }

    public function test_different_idempotency_keys_create_separate_payments(): void
    {
        $order = Order::factory()->confirmed()->create([
            'user_id' => $this->user->id,
            'total'   => 100.00,
        ]);

        $this->withToken($this->token)->postJson('/api/payments', [
            'order_id'        => $order->id,
            'payment_method'  => 'stripe',
            'idempotency_key' => 'key-alpha',
        ])->assertStatus(201);

        $this->withToken($this->token)->postJson('/api/payments', [
            'order_id'        => $order->id,
            'payment_method'  => 'stripe',
            'idempotency_key' => 'key-beta',
        ])->assertStatus(201);

        $this->assertDatabaseCount('payments', 2);
    }

    // -----------------------------------------------------------------------
    // Index
    // -----------------------------------------------------------------------

    public function test_can_list_all_payments(): void
    {
        Payment::factory()->count(3)->create();

        $response = $this->withToken($this->token)->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'order_id', 'idempotency_key', 'payment_method', 'status']],
                'meta',
            ]);
    }

    // -----------------------------------------------------------------------
    // Show
    // -----------------------------------------------------------------------

    public function test_can_show_single_payment(): void
    {
        $payment = Payment::factory()->create();

        $response = $this->withToken($this->token)->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $payment->id)
            ->assertJsonPath('data.idempotency_key', $payment->idempotency_key);
    }

    // -----------------------------------------------------------------------
    // Order-scoped payments
    // -----------------------------------------------------------------------

    public function test_can_list_payments_for_order(): void
    {
        $order = Order::factory()->confirmed()->create(['user_id' => $this->user->id]);
        Payment::factory()->count(2)->create(['order_id' => $order->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/orders/{$order->id}/payments");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    // -----------------------------------------------------------------------
    // Auth guard
    // -----------------------------------------------------------------------

    public function test_payment_endpoints_require_authentication(): void
    {
        $this->getJson('/api/payments')->assertStatus(401);
        $this->postJson('/api/payments')->assertStatus(401);
    }
}
