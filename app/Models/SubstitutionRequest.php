<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubstitutionRequest extends Model
{
    protected $fillable = [
        'user_id',
        'substitute_teacher_id',
        'subject_class_id',
        'start_date',
        'end_date',
        'status',
        'reason',
        'admin_notes',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function substituteTeacher()
    {
        return $this->belongsTo(User::class, 'substitute_teacher_id');
    }

    public function subjectClass()
    {
        return $this->belongsTo(SubjectClass::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
