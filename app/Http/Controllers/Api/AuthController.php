<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller{
    public function register(RegisterRequest $request){
        $data = $request->validated();
        
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => 2  // Default Farmer role_id
        ]);

        // Make sure role exists and assign it
        $farmerRole = \Spatie\Permission\Models\Role::findById(2);
        $user->assignRole($farmerRole);
        
        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => 2,
                'role' => 'Farmer'
            ]
        ], 201);
    }
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'roles' => $user->getRoleNames()
            ]
        ];

        // Add debug logging
        Log::info('User login successful:', [
            'user_id' => $user->id,
            'role_id' => $user->role_id,
            'roles' => $user->getRoleNames()->toArray()
        ]);

        return response()->json($response);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function createFarmer(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Add debug log
            \Log::info('User attempting to create farmer:', [
                'user_id' => $user->id,
                'roles' => $user->getRoleNames()
            ]);

            if (!$user->hasRole('Admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only admins can create farmers.',
                    'user_roles' => $user->getRoleNames()
                ], 403);
            }

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6',
            ]);

            $newUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $newUser->assignRole('Farmer');

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