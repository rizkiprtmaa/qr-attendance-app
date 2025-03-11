<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function scanQr($token)
    {
        // Log untuk debugging
        Log::info('Scanning token in controller: ' . $token);

        // Cari user berdasarkan token
        $user = User::where('qr_token', $token)->first();

        if (!$user) {
            Log::error('Invalid QR token in controller: ' . $token);
            return response()->json(['error' => 'Token tidak valid'], 404);
        }

        // Tampilkan view scan atau kembalikan data user
        return view('admin.attendances.qr-scanner', [
            'user' => $user,
            'token' => $token
        ]);
    }
}
