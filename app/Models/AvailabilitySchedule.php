<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailabilitySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'schedule_config_id',
        'valid_from',
        'valid_to',
        'status',
        'day',
        'time',
        'config',
        'is_active'
    ];

    protected $casts = [
        'config' => 'array',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
    public function scheduleConfig()
    {
        return $this->belongsTo(ScheduleConfig::class);
    }
}
