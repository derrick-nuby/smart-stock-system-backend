<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Spatie\Permission\Models\Role as PermissionRole;
use Illuminate\Support\Facades\Log;
use App\Enums\Role;

class UserController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function index()
    {
        return \App\Models\User::with('roles')->get();
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6',
                'role' => 'required|in:' . Role::ADMIN . ',' . Role::FARMER,
            ]);

            $user = $this->authService->createUser($validated);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $validated['role']
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRoles()
    {
        return response()->json([
            'success' => true,
            'data' => PermissionRole::all()
        ]);
    }
}