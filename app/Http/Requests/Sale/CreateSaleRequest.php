<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;

class CreateSaleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'warehouse_id'          => 'required|exists:warehouses,id',
            'customer_id'           => 'nullable|exists:customers,id',
            'shift_id'              => 'nullable|exists:shifts,id',
            'sale_date'             => 'nullable|date',
            'discount_amount'       => 'nullable|numeric|min:0',
            'discount_type'         => 'nullable|in:fixed,percent',
            'notes'                 => 'nullable|string|max:500',

            'items'                         => 'required|array|min:1',
            'items.*.product_id'            => 'required|exists:products,id',
            'items.*.variant_id'            => 'nullable|exists:product_variants,id',
            'items.*.quantity'              => 'required|numeric|min:0.01',
            'items.*.unit_price'            => 'required|numeric|min:0',
            'items.*.cost_price'            => 'nullable|numeric|min:0',
            'items.*.tax_amount'            => 'nullable|numeric|min:0',
            'items.*.discount_amount'       => 'nullable|numeric|min:0',

            'payments'                      => 'nullable|array',
            'payments.*.payment_method_id'  => 'required|exists:payment_methods,id',
            'payments.*.amount'             => 'required|numeric|min:0.01',
            'payments.*.reference'          => 'nullable|string|max:100',
        ];
    }
}