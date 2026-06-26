<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'status'     => $this->status,
            'total'      => $this->total,
            'items'      => OrderItemResource::collection($this->whenLoaded('items')),
            'payments'   => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
