<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'timezone',
        'config',
        'slug',
    ];

    protected $casts = [
        'config' => 'array',  
    ];

    public function schedules()
    {
        return $this->hasMany(AvailabilitySchedule::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function blockedSlots()
    {
        return $this->hasMany(BlockedSlot::class);
    }
}
