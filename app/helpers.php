<?php

if (!function_exists('setting')) {
    function setting(string $key, $default = null): mixed
    {
        try {
            return \App\Models\Setting::where('key', $key)->value('value') ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}
