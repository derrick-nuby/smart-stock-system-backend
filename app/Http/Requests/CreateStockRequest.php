<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bean_type' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'temperature' => 'required|numeric',
            'humidity' => 'required|numeric|min:0|max:100',
            'status' => 'required|string|in:Good,Warning,Critical',
            'location' => 'required|string|max:255',
            'air_condition' => 'required|string|max:255',
            'action_taken' => 'nullable|string',
        ];
    }
}
