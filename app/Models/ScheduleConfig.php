<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'days',
        'valid_from',
        'valid_to',
        'interval'
    ];
    protected $casts = [
        'days' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    public function availabilitySchedules()
    {
        return $this->hasMany(AvailabilitySchedule::class);
    }
}
