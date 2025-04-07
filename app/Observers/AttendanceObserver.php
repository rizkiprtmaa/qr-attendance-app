<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Jobs\SendAttendanceNotification;
use App\Jobs\SendDailySummary;

class AttendanceObserver
{
    /**
     * Handle the Attendance "created" event.
     */
    public function created(Attendance $attendance): void
    {
        // Cek jika ini attendance datang, kirim notifikasi
        if ($attendance->type == 'datang') {
            // Dispatch job untuk kirim notifikasi ke orang tua
            SendAttendanceNotification::dispatch($attendance);
        }

        // Jika ini attendance pulang dan terakhir dari hari ini, kirim ringkasan
        if ($attendance->type == 'pulang') {
            // Cek apakah ini adalah pelajaran terakhir untuk siswa hari ini
            SendDailySummary::dispatch($attendance->user_id, $attendance->attendance_date);
        }
    }

    /**
     * Cek dan kirim ringkasan harian jika ini adalah pelajaran terakhir
     */
    private function checkAndSendDailySummary(Attendance $attendance)
    {
        // Logika untuk menentukan apakah ini pelajaran terakhir
        // Contoh sederhana: cek waktu (jika sudah sore)
        $now = now();
        if ($now->hour >= 15) { // Asumsi pelajaran terakhir selesai sekitar jam 3 sore
            SendDailySummary::dispatch($attendance->user_id, $attendance->attendance_date);
        }
    }
}
