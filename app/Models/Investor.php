<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investor extends Model
{
    use HasFactory;
    use SoftDeletes;
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

    public function farmers()
    {
        return $this->hasMany(User::class)->where('role', 'farmer');
    }
}
