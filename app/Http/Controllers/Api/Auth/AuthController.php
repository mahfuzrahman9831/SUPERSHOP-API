<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    // Login
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Email অথবা Password ভুল হয়েছে', 401);
        }

        if (!$user->is_active) {
            return $this->error('আপনার account টি নিষ্ক্রিয় করা হয়েছে', 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'image' => $user->image,
                'roles' => $user->getRoleNames(),
            ],
        ], 'Login সফল হয়েছে');
    }

    // Logout
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success([], 'Logout সফল হয়েছে');
    }

    // Me
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'image' => $user->image,
            'roles' => $user->getRoleNames(),
        ]);
    }
}