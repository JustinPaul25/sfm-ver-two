<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CageFeedConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'cage_id',
        'day_number',
        'feed_amount',
        'consumption_date',
        'notes',
    ];

    protected $casts = [
        'consumption_date' => 'date',
        'feed_amount' => 'decimal:2',
    ];

    public function cage()
    {
        return $this->belongsTo(Cage::class);
    }
}
