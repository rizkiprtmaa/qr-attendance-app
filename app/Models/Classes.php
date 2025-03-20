<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    protected $guarded = [];


    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function student()
    {
        return $this->hasMany(Student::class);
    }

    public function school_year()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject_class()
    {
        return $this->hasMany(SubjectClass::class);
    }
}
