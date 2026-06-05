<?php

namespace App\Http\Controllers\Api;

use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends ApiController
{
    public function index(): JsonResponse
    {
        $methods = PaymentMethod::where('is_active', true)
            ->orderBy('name')
            ->get();
        return $this->success($methods);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'type'      => 'required|in:cash,card,mobile_banking,bank,store_credit',
            'is_active' => 'boolean',
        ]);

        $method = PaymentMethod::create($request->all());
        return $this->success($method, 'Payment Method তৈরি হয়েছে', 201);
    }

    public function show(PaymentMethod $paymentMethod): JsonResponse
    {
        return $this->success($paymentMethod);
    }

    public function update(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'type'      => 'required|in:cash,card,mobile_banking,bank,store_credit',
            'is_active' => 'boolean',
        ]);

        $paymentMethod->update($request->all());
        return $this->success($paymentMethod, 'Payment Method আপডেট হয়েছে');
    }

    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod->delete();
        return $this->success([], 'Payment Method মুছে ফেলা হয়েছে');
    }
}