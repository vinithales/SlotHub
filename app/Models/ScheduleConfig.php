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

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

}
