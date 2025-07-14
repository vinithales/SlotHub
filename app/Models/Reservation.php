<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'availability_schedule_id',
        'user_id',
        'status',
        'notes'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function schedule()
    {
        return $this->belongsTo(AvailabilitySchedule::class, 'availability_schedule_id');
    }

}
