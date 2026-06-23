<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a paginated list of orders.
     * Supports filtering by ?status=pending|confirmed|cancelled
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::with(['items'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return OrderResource::collection($orders);
    }

    /**
     * Store a new order with its items.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $order = DB::transaction(function () use ($validated) {
            $order = Order::create([
                'user_id' => Auth::id(),
                'status' => $validated['status'] ?? 'pending',
                'notes' => $validated['notes'] ?? null,
                'total' => 0,
            ]);

            $order->items()->createMany($validated['items']);
            $order->recalculateTotal();

            return $order;
        });

        $order->load('items');

        return $this->createdResponse(
            data: new OrderResource($order),
            message: 'Order created successfully.',
        );
    }

    /**
     * Display a single order with its items and payments.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['items', 'payments']);

        return $this->successResponse(data: new OrderResource($order));
    }

    /**
     * Update an existing order.
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($order, $validated) {
            $order->update(array_filter([
                'status' => $validated['status'] ?? null,
                'notes' => array_key_exists('notes', $validated) ? $validated['notes'] : $order->notes,
            ], fn ($v) => $v !== null));

            if (isset($validated['items'])) {
                $order->items()->delete();
                $order->items()->createMany($validated['items']);
                $order->recalculateTotal();
            }
        });

        $order->load('items');

        return $this->successResponse(
            data: new OrderResource($order),
            message: 'Order updated successfully.',
        );
    }

    /**
     * Delete an order — only if it has no associated payments.
     */
    public function destroy(Order $order): JsonResponse
    {
        if (! $order->canBeDeleted()) {
            return $this->unprocessableResponse('Cannot delete an order that has associated payments.');
        }

        $order->delete();

        return $this->messageResponse('Order deleted successfully.');
    }
}
