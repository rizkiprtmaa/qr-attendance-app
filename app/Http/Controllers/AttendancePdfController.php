<?php

namespace App\Http\Controllers;

use App\Models\SubjectClassSession;
use App\Models\SubjectClassAttendance;
use Illuminate\Http\Request;
use PDF;

class AttendancePdfController extends Controller
{
    public function downloadAttendancePdf($sessionId)
    {
        // Ambil data sesi pertemuan
        $session = SubjectClassSession::with(['subjectClass.classes.major'])->findOrFail($sessionId);

        // Ambil data absensi
        $attendances = SubjectClassAttendance::where('subject_class_session_id', $sessionId)
            ->with(['student.user', 'student.classes'])
            ->get();

        // Statistik kehadiran
        $stats = [
            'total' => $attendances->count(),
            'hadir' => $attendances->where('status', 'hadir')->count(),
            'tidak_hadir' => $attendances->where('status', 'tidak_hadir')->count(),
            'sakit' => $attendances->where('status', 'sakit')->count(),
            'izin' => $attendances->where('status', 'izin')->count(),
        ];

        // Format nama file
        $fileName = 'presensi_' . str_replace(' ', '_', $session->subject_title) . '_' .
            \Carbon\Carbon::parse($session->class_date)->format('d-m-Y') . '.pdf';

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.attendance', [
            'session' => $session,
            'attendances' => $attendances,
            'stats' => $stats,
            'date' => \Carbon\Carbon::now()->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i'),
        ]);

        // Sesuaikan untuk layout A4
        $pdf->setPaper('a4', 'portrait');

        // Download PDF
        return $pdf->stream($fileName);
    }
}
