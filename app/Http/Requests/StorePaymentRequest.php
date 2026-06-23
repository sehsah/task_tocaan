<?php

namespace App\Http\Requests;

use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $supportedMethods = app(PaymentGatewayFactory::class)->supported();

        return [
            'order_id'        => ['required', 'integer', 'exists:orders,id'],
            'payment_method'  => ['required', 'string', Rule::in($supportedMethods)],
            'idempotency_key' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.in'       => 'The selected payment method is not supported. '
                .'Supported methods: '.implode(', ', app(PaymentGatewayFactory::class)->supported()).'.',
            'idempotency_key.required' => 'An idempotency key is required to safely process payments.',
        ];
    }
}
