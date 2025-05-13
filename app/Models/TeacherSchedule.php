<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherSchedule extends Model
{
    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'notes'
    ];

    // Relasi ke User/Guru
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Method untuk mendapatkan jadwal guru berdasarkan tanggal
    public static function getScheduleByDate($userId, $date)
    {
        $dayOfWeek = \Carbon\Carbon::parse($date)->locale('id')->dayName;

        return self::where('user_id', $userId)
            ->where('day_of_week', $dayOfWeek)
            ->first();
    }
}
