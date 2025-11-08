<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CageFeedingSchedule extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'cage_id',
        'schedule_name',
        'feeding_time_1',
        'feeding_time_2',
        'feeding_time_3',
        'feeding_time_4',
        'feeding_amount_1',
        'feeding_amount_2',
        'feeding_amount_3',
        'feeding_amount_4',
        'frequency',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'feeding_time_1' => 'datetime:H:i',
        'feeding_time_2' => 'datetime:H:i',
        'feeding_time_3' => 'datetime:H:i',
        'feeding_time_4' => 'datetime:H:i',
        'feeding_amount_1' => 'decimal:2',
        'feeding_amount_2' => 'decimal:2',
        'feeding_amount_3' => 'decimal:2',
        'feeding_amount_4' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function cage()
    {
        return $this->belongsTo(Cage::class);
    }

    /**
     * Get the total daily feeding amount
     */
    public function getTotalDailyAmountAttribute()
    {
        return $this->feeding_amount_1 + $this->feeding_amount_2 + $this->feeding_amount_3 + $this->feeding_amount_4;
    }

    /**
     * Get feeding times as an array
     */
    public function getFeedingTimesAttribute()
    {
        $times = [];
        if ($this->feeding_time_1) $times[] = $this->feeding_time_1->format('H:i');
        if ($this->feeding_time_2) $times[] = $this->feeding_time_2->format('H:i');
        if ($this->feeding_time_3) $times[] = $this->feeding_time_3->format('H:i');
        if ($this->feeding_time_4) $times[] = $this->feeding_time_4->format('H:i');
        return $times;
    }

    /**
     * Get feeding amounts as an array
     */
    public function getFeedingAmountsAttribute()
    {
        return [
            $this->feeding_amount_1,
            $this->feeding_amount_2,
            $this->feeding_amount_3,
            $this->feeding_amount_4,
        ];
    }

    /**
     * Get the next feeding time
     */
    public function getNextFeedingTimeAttribute()
    {
        $now = now();
        $today = $now->format('Y-m-d');
        
        foreach ($this->feeding_times as $time) {
            $feedingDateTime = $today . ' ' . $time;
            if (strtotime($feedingDateTime) > $now->timestamp) {
                return $time;
            }
        }
        
        // If no more feedings today, return first feeding of tomorrow
        return $this->feeding_times[0] ?? null;
    }

    /**
     * Check if it's feeding time
     */
    public function isFeedingTime()
    {
        $now = now();
        $currentTime = $now->format('H:i');
        
        return in_array($currentTime, $this->feeding_times);
    }
} 