<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'bean_type',
        'quantity',
        'temperature',
        'humidity',
        'status',
        'location',
        'air_condition',
        'action_taken',
        'user_id',
        'last_updated'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}