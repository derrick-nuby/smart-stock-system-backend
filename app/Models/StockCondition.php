<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}