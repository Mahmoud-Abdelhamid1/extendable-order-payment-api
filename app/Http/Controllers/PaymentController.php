<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    public function process(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        try {
            $payment = $this->paymentService->process(
                $order,
                $request->validated('payment_method')
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    public function indexForOrder(Order $order): AnonymousResourceCollection
    {
        $payments = $this->paymentService->listForOrder($order);

        return PaymentResource::collection($payments);
    }

    public function index(): AnonymousResourceCollection
    {
        $payments = $this->paymentService->listAll();

        return PaymentResource::collection($payments);
    }
}
