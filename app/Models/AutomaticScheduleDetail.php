<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomaticScheduleDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'automatic_schedule_id',
        'subject_class_id',
        'start_time',
        'end_time',
        'jam_pelajaran',
        'session_title_template',
        'is_active',
    ];

    /**
     * Get the automatic schedule this detail belongs to
     */
    public function automaticSchedule()
    {
        return $this->belongsTo(AutomaticSchedule::class);
    }

    /**
     * Get the subject class for this schedule detail
     */
    public function subjectClass()
    {
        return $this->belongsTo(SubjectClass::class);
    }
}
