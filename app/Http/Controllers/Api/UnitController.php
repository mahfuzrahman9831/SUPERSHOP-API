<?php

namespace App\Http\Controllers\Api;

use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends ApiController
{
    public function index(): JsonResponse
    {
        $units = Unit::orderBy('name')->get();
        return $this->success($units);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:50',
            'short_name' => 'required|string|max:20',
        ]);

        $unit = Unit::create($request->all());
        return $this->success($unit, 'Unit তৈরি হয়েছে', 201);
    }

    public function show(Unit $unit): JsonResponse
    {
        return $this->success($unit);
    }

    public function update(Request $request, Unit $unit): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:50',
            'short_name' => 'required|string|max:20',
        ]);

        $unit->update($request->all());
        return $this->success($unit, 'Unit আপডেট হয়েছে');
    }

    public function destroy(Unit $unit): JsonResponse
    {
        $unit->delete();
        return $this->success([], 'Unit মুছে ফেলা হয়েছে');
    }
}