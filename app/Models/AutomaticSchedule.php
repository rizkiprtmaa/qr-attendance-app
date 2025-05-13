<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomaticSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'day_of_week',
        'is_active',
    ];

    /**
     * Get the schedule details for this automatic schedule
     */
    public function scheduleDetails()
    {
        return $this->hasMany(AutomaticScheduleDetail::class);
    }
}
