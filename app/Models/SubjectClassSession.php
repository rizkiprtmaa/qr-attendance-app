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
        'created_by_substitute',
        'substitution_request_id',
        'notes',
        'jam_pelajaran'
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

    // Relasi ke substitution request
    public function substitutionRequest()
    {
        return $this->belongsTo(SubstitutionRequest::class, 'substitution_request_id');
    }

    // Relasi ke guru pengganti
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_substitute');
    }

    // Helper method untuk menghitung siswa hadir
    public function getPresentCountAttribute()
    {
        return $this->attendances()->where('status', 'hadir')->count();
    }

    // Helper method untuk menghitung siswa tidak hadir
    public function getAbsentCountAttribute()
    {
        return $this->attendances()->where('status', 'tidak_hadir')->count();
    }

    // Helper method untuk menghitung siswa izin/sakit
    public function getPermissionCountAttribute()
    {
        return $this->attendances()->whereIn('status', ['izin', 'sakit'])->count();
    }

    // Total siswa di sesi ini
    public function getTotalStudentsAttribute()
    {
        return $this->attendances()->count();
    }
}
