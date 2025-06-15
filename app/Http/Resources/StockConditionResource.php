<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockConditionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'bean_type' => $this->bean_type,
            'quantity' => $this->quantity,
            'temperature' => $this->temperature,
            'humidity' => $this->humidity,
            'status' => $this->status,
            'location' => $this->location,
            'air_condition' => $this->air_condition,
            'action_taken' => $this->action_taken,
            'last_updated' => $this->last_updated,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
