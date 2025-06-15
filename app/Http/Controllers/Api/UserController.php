<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Spatie\Permission\Models\Role as PermissionRole;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use App\Enums\Role;

class UserController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        $users = \App\Models\User::with('roles')->get();

        return UserResource::collection($users);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6',
                'role' => 'required|in:' . Role::ADMIN . ',' . Role::FARMER,
            ]);

            $user = $this->authService->createUser($validated);

            return (new UserResource($user->load('roles')))
                ->additional([
                    'success' => true,
                    'message' => 'User created successfully'
                ])
                ->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRoles(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => PermissionRole::all()
        ]);
    }
}