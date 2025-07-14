<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedSlot extends Model
{
    use HasFactory;

    protected $table = 'blocked_slots';

    protected $fillable = [
        'business_id',
        'valid_from',
        'valid_to',
        'reason',
    ];
}
