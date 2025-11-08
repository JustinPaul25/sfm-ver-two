<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investor extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'address',
        'phone',
    ];

    // Relationships
    public function samplings()
    {
        return $this->hasMany(Sampling::class);
    }

    public function cages()
    {
        return $this->hasMany(Cage::class);
    }

    public function samples()
    {
        return $this->hasMany(Sample::class);
    }
}
