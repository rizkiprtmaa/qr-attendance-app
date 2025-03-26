<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectClassSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_class_id',
        'subject_title',
        'class_date',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'class_date' => 'datetime',
    ];

    /**
     * Get the subject class that owns the session
     */
    public function subjectClass()
    {
        return $this->belongsTo(SubjectClass::class);
    }

    /**
     * Get the attendances for the session
     */
    public function attendances()
    {
        return $this->hasMany(SubjectClassAttendance::class, 'subject_class_session_id');
    }

    /**
     * Get the count of students with each status
     */
    public function getStatusCountsAttribute()
    {
        $counts = [
            'hadir' => $this->attendances()->status('hadir')->count(),
            'tidak_hadir' => $this->attendances()->status('tidak_hadir')->count(),
            'sakit' => $this->attendances()->status('sakit')->count(),
            'izin' => $this->attendances()->status('izin')->count(),
        ];

        $counts['total'] = array_sum($counts);

        return $counts;
    }

    public function subjectClassAttendances()
    {
        return $this->hasMany(SubjectClassAttendance::class);
    }
}
