<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'investor_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the cages managed by this farmer.
     */
    public function cages()
    {
        return $this->hasMany(Cage::class, 'farmer_id');
    }

    /**
     * Get all feed consumptions across all cages managed by this farmer.
     */
    public function feedConsumptions()
    {
        return $this->hasManyThrough(
            CageFeedConsumption::class,
            Cage::class,
            'farmer_id', // Foreign key on cages table
            'cage_id',   // Foreign key on cage_feed_consumptions table
            'id',        // Local key on users table
            'id'         // Local key on cages table
        );
    }

    /**
     * Get the investor that this farmer belongs to.
     */
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    /**
     * Check if user is a farmer.
     */
    public function isFarmer(): bool
    {
        return $this->role === 'farmer';
    }

    /**
     * Check if user is an investor.
     */
    public function isInvestor(): bool
    {
        return $this->role === 'investor';
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get total feed consumption for this farmer across all cages.
     *
     * @param \Carbon\Carbon|null $startDate
     * @param \Carbon\Carbon|null $endDate
     * @return float
     */
    public function getTotalFeedConsumption($startDate = null, $endDate = null): float
    {
        $query = $this->feedConsumptions();
        
        if ($startDate) {
            $query->where('consumption_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('consumption_date', '<=', $endDate);
        }
        
        return (float) $query->sum('feed_amount');
    }

    /**
     * Get average daily feed consumption for this farmer.
     *
     * @param \Carbon\Carbon|null $startDate
     * @param \Carbon\Carbon|null $endDate
     * @return float
     */
    public function getAverageFeedConsumption($startDate = null, $endDate = null): float
    {
        $query = $this->feedConsumptions();
        
        if ($startDate) {
            $query->where('consumption_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('consumption_date', '<=', $endDate);
        }
        
        return (float) $query->avg('feed_amount') ?? 0.0;
    }

    /**
     * Get feed consumption count for this farmer.
     *
     * @param \Carbon\Carbon|null $startDate
     * @param \Carbon\Carbon|null $endDate
     * @return int
     */
    public function getFeedConsumptionCount($startDate = null, $endDate = null): int
    {
        $query = $this->feedConsumptions();
        
        if ($startDate) {
            $query->where('consumption_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('consumption_date', '<=', $endDate);
        }
        
        return $query->count();
    }
}
