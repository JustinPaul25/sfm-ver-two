<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sample extends Model
{
    use HasFactory;
    protected $fillable = [
        'investor_id',
        'sampling_id',
        'sample_no',
        'weight',
    ];

    public function investor() {
        return $this->belongsTo(Investor::class);
    }

    public function sampling() {
        return $this->belongsTo(Sampling::class);
    }
}
