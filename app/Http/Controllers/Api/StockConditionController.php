<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCondition;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class StockConditionController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasRole('Farmer')) {
            return $user->stockConditions;
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

        if ($user->hasRole('Admin') || $stockCondition->user_id === $user->id) {
            return $stockCondition;
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'temperature' => 'required|numeric',
            'humidity' => 'required|numeric',
            'air_condition' => 'required|string|max:255',
            'action_taken' => 'nullable|string',
        ]);

        $data['user_id'] = $user->id;

        return StockCondition::create($data);
    }

    public function update(Request $request, StockCondition $stockCondition)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasRole('Farmer') && $stockCondition->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'temperature' => 'sometimes|required|numeric',
            'humidity' => 'sometimes|required|numeric',
            'air_condition' => 'sometimes|required|string|max:255',
            'action_taken' => 'nullable|string',
        ]);

        $stockCondition->update($data);

        return $stockCondition;
    }

    public function destroy(StockCondition $stockCondition)
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasRole('Farmer') && $stockCondition->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $stockCondition->delete();

        return response()->json(['message' => 'Stock condition deleted']);
    }
}