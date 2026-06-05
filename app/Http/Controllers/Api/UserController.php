<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles')
            ->orderBy('name')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($users);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users',
            'phone'    => 'nullable|string|unique:users',
            'password' => 'required|string|min:6',
            'role'     => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
            'is_active' => true,
        ]);

        $role = Role::findByName($request->role, 'sanctum');
        $user->assignRole($role);

        $user->load('roles');
        return $this->success($user, 'User তৈরি হয়েছে', 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->load('roles');
        return $this->success($user);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'phone'     => 'nullable|string|unique:users,phone,' . $user->id,
            'password'  => 'nullable|string|min:6',
            'role'      => 'nullable|string|exists:roles,name',
            'is_active' => 'boolean',
        ]);

        $user->update([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'is_active' => $request->is_active ?? $user->is_active,
            'password'  => $request->password
                ? Hash::make($request->password)
                : $user->password,
        ]);

        if ($request->role) {
            $role = Role::findByName($request->role, 'sanctum');
            $user->syncRoles([$role]);
        }

        $user->load('roles');
        return $this->success($user, 'User আপডেট হয়েছে');
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return $this->error('নিজেকে delete করা যাবে না', 400);
        }

        $user->delete();
        return $this->success([], 'User মুছে ফেলা হয়েছে');
    }

    public function toggleActive(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return $this->error('নিজেকে নিষ্ক্রিয় করা যাবে না', 400);
        }

        $user->update(['is_active' => !$user->is_active]);
        $msg = $user->is_active ? 'User সক্রিয় করা হয়েছে' : 'User নিষ্ক্রিয় করা হয়েছে';
        return $this->success($user, $msg);
    }
}