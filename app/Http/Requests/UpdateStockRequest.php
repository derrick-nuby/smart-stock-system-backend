<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bean_type' => 'sometimes|required|string',
            'quantity' => 'sometimes|required|numeric',
            'temperature' => 'sometimes|required|numeric',
            'humidity' => 'sometimes|required|numeric',
            'status' => 'sometimes|required|string',
            'location' => 'sometimes|required|string',
            'air_condition' => 'sometimes|required|string',
            'action_taken' => 'nullable|string',
        ];
    }
}
