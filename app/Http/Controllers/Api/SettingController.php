<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends ApiController
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key');
        return $this->success($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $settings = Setting::all()->pluck('value', 'key');
        return $this->success($settings, 'Settings আপডেট হয়েছে');
    }

    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return $this->error('Setting পাওয়া যায়নি', 404);
        }

        return $this->success($setting);
    }
}