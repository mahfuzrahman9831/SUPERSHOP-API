<?php

namespace App\Http\Controllers\Api;

use App\Models\TaxRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxRateController extends ApiController
{
    public function index(): JsonResponse
    {
        $taxRates = TaxRate::orderBy('rate')->get();
        return $this->success($taxRates);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'rate'       => 'required|numeric|min:0|max:100',
            'is_default' => 'boolean',
            'is_active'  => 'boolean',
        ]);

        if ($request->is_default) {
            TaxRate::where('is_default', true)->update(['is_default' => false]);
        }

        $taxRate = TaxRate::create($request->all());
        return $this->success($taxRate, 'Tax Rate তৈরি হয়েছে', 201);
    }

    public function show(TaxRate $taxRate): JsonResponse
    {
        return $this->success($taxRate);
    }

    public function update(Request $request, TaxRate $taxRate): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'rate'       => 'required|numeric|min:0|max:100',
            'is_default' => 'boolean',
            'is_active'  => 'boolean',
        ]);

        if ($request->is_default) {
            TaxRate::where('is_default', true)
                ->where('id', '!=', $taxRate->id)
                ->update(['is_default' => false]);
        }

        $taxRate->update($request->all());
        return $this->success($taxRate, 'Tax Rate আপডেট হয়েছে');
    }

    public function destroy(TaxRate $taxRate): JsonResponse
    {
        $taxRate->delete();
        return $this->success([], 'Tax Rate মুছে ফেলা হয়েছে');
    }
}