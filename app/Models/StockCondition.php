<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'temperature', 'humidity', 'air_condition', 'action_taken'
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}