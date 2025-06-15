<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StockConditionController;
use App\Http\Controllers\Api\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Farmer and Admin shared routes
    Route::prefix('stocks')->group(function () {
        Route::get('/', [StockConditionController::class, 'getStocks']);
        Route::post('/', [StockConditionController::class, 'createStock']);
        Route::get('/{stock}', [StockConditionController::class, 'getStock']);
        Route::put('/{stock}', [StockConditionController::class, 'updateStock']);
        Route::delete('/{stock}', [StockConditionController::class, 'deleteStock']);
        Route::get('stock-conditions/all', [StockConditionController::class, 'getAllStockConditions']);

    });

    // Admin only routes
    Route::middleware('role:Admin')->group(function () {
        Route::get('/Admin/users', [UserController::class, 'index']);
        Route::get('stock-conditions/all', [StockConditionController::class, 'getAllStockConditions']);
        Route::post('/admin/create-farmer', [AuthController::class, 'createFarmer']);
    });

    Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/roles', [UserController::class, 'getRoles']);
    });
});