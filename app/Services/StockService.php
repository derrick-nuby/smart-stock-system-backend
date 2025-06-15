<?php

namespace App\Services;

use App\Models\StockCondition;
use Illuminate\Support\Facades\Auth;

class StockService
{
    /**
     * Create a new stock condition for the authenticated user.
     */
    public function createStock(array $data): StockCondition
    {
        $stock = new StockCondition();
        $stock->fill($data);
        $stock->user_id = Auth::id();
        $stock->last_updated = now();
        $stock->save();

        return $stock->fresh();
    }

    /**
     * Update an existing stock condition.
     */
    public function updateStock(StockCondition $stock, array $data): StockCondition
    {
        $data['last_updated'] = now();
        $stock->update($data);

        return $stock;
    }
}
