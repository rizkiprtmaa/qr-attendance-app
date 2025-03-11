<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_date',
        'type',
        'status',
        'check_in_time',
        'check_out_time'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function recordAttendance($userId, $type)
    {
        $now = Carbon::now()->timezone('Asia/Jakarta');

        // Cek apakah sudah ada presensi hari ini
        $existingAttendance = self::where('user_id', $userId)
            ->where('attendance_date', $now->toDateString())
            ->where('type', $type)
            ->first();

        // Jika sudah ada, return existing
        if ($existingAttendance) {
            return $existingAttendance;
        }

        // Tentukan status
        $status = self::determineAttendanceStatus($type, $now);

        // Buat atau update presensi
        return self::create([
            'user_id' => $userId,
            'attendance_date' => $now->toDateString(),
            'type' => $type,
            'status' => $status,
            'check_in_time' => $now->toTimeString()
        ]);
    }

    protected static function determineAttendanceStatus($type, $checkTime = null)
    {
        if ($checkTime === null) {
            $checkTime = Carbon::now();
        }

        if ($type === 'datang') {
            $batasWaktuDatang = Carbon::createFromTime(7, 0, 0);
            return $checkTime->gt($batasWaktuDatang) ? 'terlambat' : 'hadir';
        }

        if ($type === 'pulang') {
            $batasWaktuPulang = Carbon::createFromTime(14, 0, 0);
            return $checkTime->gt($batasWaktuPulang) ? 'hadir' : 'pulang_cepat';
        }

        return 'hadir';
    }
}
