<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Enums\Role;

use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => Role::FARMER,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $response = $this->authService->login($request->validated());

        return response()->json($response);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function createFarmer(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Add debug log
            \Log::info('User attempting to create farmer:', [
                'user_id' => $user->id,
                'roles' => $user->getRoleNames()
            ]);

            if (!$user->hasRole(Role::ADMIN)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only admins can create farmers.',
                    'user_roles' => $user->getRoleNames()
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6',
            ]);

            $newUser = $this->authService->createFarmer($validated);

            return response()->json([
                'success' => true,
                'message' => 'Farmer created successfully',
                'data' => [
                    'id' => $newUser->id,
                    'name' => $newUser->name,
                    'email' => $newUser->email
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating farmer: ' . $e->getMessage()
            ], 500);
        }
    }
}