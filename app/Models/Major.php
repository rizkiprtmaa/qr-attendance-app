<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Classes;
use App\Models\Student;

class Major extends Model
{
    protected $guarded = [];

    public function classes()
    {
        return $this->hasMany(Classes::class);
    }

    public function student()
    {
        return $this->hasManyThrough(Student::class, Classes::class);
    }
}
