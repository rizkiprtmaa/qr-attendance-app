<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function scanQr($qr_token)
    {
        // Cari user berdasarkan QR token
        $user = User::where('qr_token', $qr_token)->firstOrFail();

        // Proses presensi
        $attendance = Attendance::recordAttendance($user->id, 'datang');

        // Bisa return view atau response
        return view('attendance.scan-result', [
            'user' => $user,
            'attendance' => $attendance
        ]);
    }
}
