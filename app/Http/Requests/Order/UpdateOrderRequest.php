<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'                 => ['sometimes', 'in:pending,confirmed,cancelled'],
            'items'                  => ['sometimes', 'array', 'min:1'],
            'items.*.product_name'   => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity'       => ['required_with:items', 'integer', 'min:1'],
            'items.*.price'          => ['required_with:items', 'numeric', 'min:0.01'],
        ];
    }
}
