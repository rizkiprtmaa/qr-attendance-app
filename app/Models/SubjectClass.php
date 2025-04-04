<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectClass extends Model
{
    protected $guarded = ['id'];

    public function classes()
    {
        return $this->belongsTo(Classes::class);
    }

    // Define the relationship with SubjectClassSession
    public function subjectClassSessions()
    {
        return $this->hasMany(SubjectClassSession::class, 'subject_class_id');
    }

    public function major()
    {
        return $this->belongsTo(Major::class);
    }


    // Metode untuk mengecek apakah user bisa mengakses kelas
    public function canManageClass(User $user)
    {
        return $user->id === $this->teacher_id ||
            $this->substituteTeachers()->where('users.id', $user->id)->exists();
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
