<?php

namespace App\Http\Controllers\Api;

use App\Models\CustomerGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerGroupController extends ApiController
{
    public function index(): JsonResponse
    {
        $groups = CustomerGroup::orderBy('name')->get();
        return $this->success($groups);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'                => 'required|string|max:100',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'loyalty_multiplier'  => 'nullable|numeric|min:0',
        ]);

        $group = CustomerGroup::create($request->all());
        return $this->success($group, 'Customer Group তৈরি হয়েছে', 201);
    }

    public function show(CustomerGroup $customerGroup): JsonResponse
    {
        $customerGroup->load('customers');
        return $this->success($customerGroup);
    }

    public function update(Request $request, CustomerGroup $customerGroup): JsonResponse
    {
        $request->validate([
            'name'                => 'required|string|max:100',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'loyalty_multiplier'  => 'nullable|numeric|min:0',
        ]);

        $customerGroup->update($request->all());
        return $this->success($customerGroup, 'Customer Group আপডেট হয়েছে');
    }

    public function destroy(CustomerGroup $customerGroup): JsonResponse
    {
        $customerGroup->delete();
        return $this->success([], 'Customer Group মুছে ফেলা হয়েছে');
    }
}