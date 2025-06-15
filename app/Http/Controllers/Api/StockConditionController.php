<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Resources\StockConditionResource;
use App\Http\Requests\CreateStockRequest;
use App\Http\Requests\UpdateStockRequest;
use App\Models\StockCondition;
use App\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Enums\Role;

class StockConditionController extends Controller
{
    private const FARMER_ROLE = Role::FARMER;
    private const ADMIN_ROLE = Role::ADMIN;

    public function __construct(private StockService $stockService)
    {
    }

    public function index(): JsonResponse|AnonymousResourceCollection
    {
        /** @var User $user */
        $user = Auth::user();

        if ($this->isFarmer($user)) {
            // Return paginated data for farmers
            $stocks = StockCondition::where('user_id', $user->id)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->paginate(config('app.pagination.per_page'));

            return StockConditionResource::collection($stocks);
        }

        // Admin gets summary
        return response()->json([
            'total_users' => StockCondition::distinct('user_id')->count('user_id'),
            'avg_temperature' => round(StockCondition::avg('temperature'), 2),
            'avg_humidity' => round(StockCondition::avg('humidity'), 2),
            'latest_condition' => new StockConditionResource(
                StockCondition::latest()->with('user')->first()
            ),
        ]);
    }

    public function show(StockCondition $stockCondition): StockConditionResource|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Allow farmers to view their own records
        if ($stockCondition->user_id === $user->id || $this->isAdmin($user)) {
            return new StockConditionResource($stockCondition->load('user'));
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }


    public function getAllStockConditions(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Log attempt
            Log::info('Attempting to fetch all stocks', ['user_id' => $user->id]);

            $query = StockCondition::query();
            $query->with('user');
            $query->orderBy('created_at', 'desc');

            $stockConditions = $query->paginate(config('app.pagination.per_page'));

            return StockConditionResource::collection($stockConditions)
                ->additional([
                    'success' => true,
                    'count' => $stockConditions->total(),
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

    public function getStockConditionsByUserId(int $user_id): AnonymousResourceCollection
    {
        $stockConditions = StockCondition::query()
            ->where('user_id', (int)$user_id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(config('app.pagination.per_page'));

        return StockConditionResource::collection($stockConditions)
            ->additional([
                'success' => true,
                'message' => 'Stock conditions retrieved successfully'
            ]);
    }

    public function getStocks(): AnonymousResourceCollection
    {
        $user = Auth::user();
        $query = StockCondition::with('user');
        
        if ($this->isFarmer($user)) {
            $query->where('user_id', $user->id);
        }
        
        $stocks = $query->orderBy('created_at', 'desc')
            ->paginate(config('app.pagination.per_page'));

        return StockConditionResource::collection($stocks)
            ->additional(['success' => true]);
    }

    public function createStock(CreateStockRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $stock = $this->stockService->createStock($data);

            return (new StockConditionResource($stock->load('user')))
                ->additional([
                    'success' => true,
                    'message' => 'Stock created successfully',
                ])
                ->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            Log::error('Stock creation error:', ['error' => $e->getMessage(), 'data' => $request->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create stock',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getStock(StockCondition $stock): JsonResponse
    {
        $user = Auth::user();
        $stock->load('user');

        if (!$this->isAdmin($user) && $stock->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Stock not found'
            ], 404);
        }

        return (new StockConditionResource($stock))
            ->additional(['success' => true]);
    }

    public function updateStock(UpdateStockRequest $request, StockCondition $stock): JsonResponse
    {
        $user = Auth::user();
        if (!$this->isAdmin($user) && $stock->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Stock not found',
            ], 404);
        }

        $data = $request->validated();

        $stock = $this->stockService->updateStock($stock, $data);

        return (new StockConditionResource($stock->load('user')))
            ->additional([
                'success' => true,
                'message' => 'Stock updated successfully',
            ]);
    }

    public function deleteStock(StockCondition $stock): JsonResponse
    {
        $user = Auth::user();
        if (!$this->isAdmin($user) && $stock->user_id !== $user->id) {
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