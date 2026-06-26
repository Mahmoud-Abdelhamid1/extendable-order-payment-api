<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supportedMethods = implode(',', array_keys(app('payment.gateways')));

        return [
            'payment_method' => ['required', 'string', 'in:' . $supportedMethods],
        ];
    }

    public function messages(): array
    {
        $supported = implode(', ', array_keys(app('payment.gateways')));

        return [
            'payment_method.in' => "Unsupported payment method. Supported: {$supported}.",
        ];
    }
}
