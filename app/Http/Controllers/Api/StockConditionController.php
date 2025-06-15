<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CreateStockRequest;
use App\Http\Requests\UpdateStockRequest;
use App\Models\StockCondition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class StockConditionController extends Controller
{
    private const FARMER_ROLE = 'farmer';
    private const ADMIN_ROLE = 'Admin';

    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        if ($this->isFarmer($user)) {
            // Return paginated data for farmers
            return StockCondition::where('user_id', $user->id)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        // Admin gets summary
        return response()->json([
            'total_users' => StockCondition::distinct('user_id')->count('user_id'),
            'avg_temperature' => round(StockCondition::avg('temperature'), 2),
            'avg_humidity' => round(StockCondition::avg('humidity'), 2),
            'latest_condition' => StockCondition::latest()->with('user')->first(),
        ]);
    }

    public function show(StockCondition $stockCondition)
    {
        /** @var User $user */
        $user = Auth::user();

        // Allow farmers to view their own records
        if ($stockCondition->user_id === $user->id || $this->isAdmin($user)) {
            return $stockCondition;
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }

    public function store(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            $data = $request->validate([
                'bean_type' => 'required|string|max:255',
                'quantity' => 'required|numeric|min:0',
                'temperature' => 'required|numeric',
                'humidity' => 'required|numeric',
                'status' => 'required|string|in:Good,Warning,Critical',
                'location' => 'required|string|max:255',
                'air_condition' => 'required|string|max:255',
                'action_taken' => 'nullable|string|max:1000'
            ], [
                'bean_type.required' => 'The bean type field is required',
                'quantity.required' => 'The quantity field is required',
                'quantity.numeric' => 'The quantity must be a number',
                'quantity.min' => 'The quantity must be at least 0',
                'temperature.required' => 'The temperature field is required',
                'temperature.numeric' => 'The temperature must be a number',
                'humidity.required' => 'The humidity field is required',
                'humidity.numeric' => 'The humidity must be a number',
                'status.required' => 'The status field is required',
                'status.in' => 'The status must be Good, Warning, or Critical',
                'location.required' => 'The location field is required',
                'air_condition.required' => 'The air condition field is required'
            ]);

            // Format the data before creation
            $stockData = [
                'bean_type' => $data['bean_type'],
                'quantity' => $data['quantity'],
                'temperature' => $data['temperature'],
                'humidity' => $data['humidity'],
                'status' => $data['status'],
                'location' => $data['location'],
                'air_condition' => $data['air_condition'],
                'action_taken' => $data['action_taken'] ?? null,
                'user_id' => $user->id,
                'last_updated' => now()
            ];

            $stock = StockCondition::create($stockData);
            
            return response()->json([
                'success' => true,
                'data' => $stock,
                'message' => 'Stock condition created successfully'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'fields_required' => [
                    'bean_type' => 'String, required',
                    'quantity' => 'Number, minimum 0',
                    'temperature' => 'Number',
                    'humidity' => 'Number',
                    'status' => 'String (Good/Warning/Critical)',
                    'location' => 'String',
                    'air_condition' => 'String',
                    'action_taken' => 'String, optional'
                ]
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock condition',
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ], 500);
        }
    }

    public function update(Request $request, StockCondition $stockCondition)
    {
        /** @var User $user */
        $user = Auth::user();

        // Allow farmers to update their own records
        if ($stockCondition->user_id !== $user->id && !$this->isAdmin($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'temperature' => 'sometimes|required|numeric',
            'humidity' => 'sometimes|required|numeric',
            'air_condition' => 'sometimes|required|string|max:255',
            'action_taken' => 'nullable|string',
            'bean_type' => 'sometimes|required|string',
            'quantity' => 'sometimes|required|numeric',
            'status' => 'sometimes|required|string',
            'location' => 'sometimes|required|string',
            'last_updated' => 'sometimes|required|date'
        ]);

        $stockCondition->update($data);
        return $stockCondition;
    }

    public function destroy(StockCondition $stockCondition)
    {
        /** @var User $user */
        $user = Auth::user();

        // Allow farmers to delete their own records
        if ($stockCondition->user_id !== $user->id && !$this->isAdmin($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $stockCondition->delete();
        return response()->json(['message' => 'Stock condition deleted successfully']);
    }

    public function getAllStockConditions(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Log attempt
            Log::info('Attempting to fetch all stocks', ['user_id' => $user->id]);

            $query = StockCondition::query();
            $query->with('user');
            $query->orderBy('created_at', 'desc');

            $stockConditions = $query->get();

            if ($stockConditions->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No stock conditions found',
                    'data' => [],
                    'count' => 0
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $stockConditions,
                'count' => $stockConditions->count(),
                'message' => 'Stock conditions retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch stock conditions:', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve stock conditions',
                'error_details' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ]
            ], 500);
        }
    }

    public function getStockConditionsByUserId($user_id)
    {
        $stockConditions = StockCondition::query()
            ->where('user_id', (int)$user_id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stockConditions,
            'message' => 'Stock conditions retrieved successfully'
        ]);
    }

    public function getStocks()
    {
        $user = Auth::user();
        $query = StockCondition::with('user');
        
        if ($this->isFarmer($user)) {
            $query->where('user_id', $user->id);
        }
        
        return response()->json([
            'success' => true,
            'data' => $query->orderBy('created_at', 'desc')->get()
        ]);
    }

    public function createStock(CreateStockRequest $request)
    {
        try {
            $data = $request->validated();

            $stock = new StockCondition();
            $stock->fill($data);
            $stock->user_id = Auth::id();
            $stock->last_updated = now();
            $stock->save();

            return response()->json([
                'success' => true,
                'data' => $stock->fresh(),
                'message' => 'Stock created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Stock creation error:', ['error' => $e->getMessage(), 'data' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStock($id)
    {
        $user = Auth::user();
        $stock = StockCondition::with('user')->find($id);

        if (!$stock || (!$this->isAdmin($user) && $stock->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Stock not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $stock
        ]);
    }

    public function updateStock(UpdateStockRequest $request, $id)
    {
        $user = Auth::user();
        $stock = StockCondition::find($id);

        if (!$stock || (!$this->isAdmin($user) && $stock->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Stock not found'
            ], 404);
        }

        $data = $request->validated();

        $data['last_updated'] = now();
        $stock->update($data);

        return response()->json([
            'success' => true,
            'data' => $stock,
            'message' => 'Stock updated successfully'
        ]);
    }

    public function deleteStock($id)
    {
        $user = Auth::user();
        $stock = StockCondition::find($id);

        if (!$stock || (!$this->isAdmin($user) && $stock->user_id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Stock not found'
            ], 404);
        }

        $stock->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stock deleted successfully'
        ]);
    }

    protected function isFarmer(User $user): bool
    {
        return $user->hasRole(self::FARMER_ROLE);
    }

    protected function isAdmin(User $user): bool
    {
        return $user->hasRole(self::ADMIN_ROLE);
    }
}