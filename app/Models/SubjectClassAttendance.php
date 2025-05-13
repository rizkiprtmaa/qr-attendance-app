<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectClassAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_class_session_id',
        'student_id',
        'status',
        'check_in_time',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
    ];

    /**
     * Get the session that owns the attendance record
     */
    public function session()
    {
        return $this->belongsTo(SubjectClassSession::class, 'subject_class_session_id');
    }

    /**
     * Get the student that owns the attendance record
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }


    /**
     * Scope a query to filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
