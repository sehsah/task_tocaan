<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\Exceptions\DuplicatePaymentException;
use App\Services\Payment\Exceptions\UnsupportedGatewayException;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Display a paginated list of all payments.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = Payment::with('order')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return PaymentResource::collection($payments);
    }

    /**
     * Process a payment for an order.
     *
     * When the same idempotency_key is replayed the original payment is
     * returned with HTTP 200 (instead of 201) — no duplicate charge occurs.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $order = Order::findOrFail($request->validated('order_id'));

        try {
            $payment = $this->paymentService->processPayment(
                $order,
                $request->validated('payment_method'),
                $request->validated('idempotency_key'),
            );

            return $this->createdResponse(
                data: new PaymentResource($payment),
                message: 'Payment processed.',
            );
        } catch (DuplicatePaymentException $e) {
            // Idempotent replay: return the original payment, no new charge.
            return $this->successResponse(
                data: new PaymentResource($e->getExistingPayment()),
                message: 'Duplicate request — original payment returned.',
            );
        } catch (UnsupportedGatewayException $e) {
            return $this->unprocessableResponse($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->unprocessableResponse($e->getMessage());
        }
    }

    /**
     * Display a single payment.
     */
    public function show(Payment $payment): JsonResponse
    {
        return $this->successResponse(data: new PaymentResource($payment));
    }

    /**
     * Display all payments for a specific order.
     */
    public function orderPayments(Request $request, Order $order): AnonymousResourceCollection
    {
        $payments = $order->payments()
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return PaymentResource::collection($payments);
    }
}
