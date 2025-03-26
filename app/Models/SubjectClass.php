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

    // Guru utama
    public function mainTeacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Guru pengganti (bisa multiple)
    public function substituteTeachers()
    {
        return $this->belongsToMany(User::class, 'substitute_teachers', 'subject_class_id', 'user_id');
    }

    // Metode untuk mengecek apakah user bisa mengakses kelas
    public function canManageClass(User $user)
    {
        return $user->id === $this->teacher_id ||
            $this->substituteTeachers()->where('users.id', $user->id)->exists();
    }

    public function teacher()
    {
        return $this->hasMany(User::class, 'teacher_id');
    }
}
