<?php

namespace App\Http\Controllers;

use App\Models\SubjectClass;
use App\Models\SubjectClassSession;
use App\Models\SubjectClassAttendance;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;


class ClassReportController extends Controller
{
    public function generateAgendaReport(SubjectClass $subjectClass)
    {
        // Dapatkan sesi kelas
        $sessions = SubjectClassSession::where('subject_class_id', $subjectClass->id)
            ->orderBy('class_date', 'asc')
            ->get();

        $className = $subjectClass->classes->name;
        $majorName = $subjectClass->classes->major->name;
        $semester = now()->month > 6 ? 'Ganjil' : 'Genap';
        $tahunAjaran = now()->month > 6
            ? now()->year . "/" . (now()->year + 1)
            : (now()->year - 1) . "/" . now()->year;

        // Dalam fungsi downloadAttendanceReport(), tambahkan:
        $logoProvBase64 = base64_encode(file_get_contents(public_path('images/logo-prov.png')));
        $logoSekolahBase64 = base64_encode(file_get_contents(public_path('images/logo-sekolah.png')));

        $data['logoProvData'] = 'data:image/png;base64,' . $logoProvBase64;
        $data['logoSekolahData'] = 'data:image/png;base64,' . $logoSekolahBase64;

        $data = [
            'title' => 'Agenda Kegiatan Belajar Mengajar',
            'className' => $className . ' - ' . $majorName,
            'subjectName' => $subjectClass->class_name,
            'semester' => $semester . ' - ' . $tahunAjaran,
            'sessions' => $sessions,
            'teacherName' => $subjectClass->user->name,
            'logoProvData' => $data['logoProvData'],
            'logoSekolahData' => $data['logoSekolahData'],
        ];

        $pdf = PDF::loadView('pdfs.agenda-report', $data);

        return $pdf->download('agenda-kbm-' . $subjectClass->class_name . '.pdf');
    }

    public function generateAttendanceReport(SubjectClass $subjectClass)
    {
        // Dapatkan sesi kelas
        $sessions = SubjectClassSession::where('subject_class_id', $subjectClass->id)
            ->orderBy('class_date', 'asc')
            ->get();

        // Dapatkan daftar siswa
        $students = $subjectClass->classes->student()
            ->with('user')
            ->get();

        // Data absensi untuk setiap siswa pada setiap sesi
        $attendanceData = [];
        $attendanceSummary = [];

        foreach ($students as $student) {
            $attendanceData[$student->id] = [];
            $attendanceSummary[$student->id] = [
                'hadir' => 0,
                'sakit' => 0,
                'izin' => 0,
                'tidak_hadir' => 0
            ];

            foreach ($sessions as $session) {
                $attendance = SubjectClassAttendance::where('subject_class_session_id', $session->id)
                    ->where('student_id', $student->id)
                    ->first();

                $status = $attendance ? $attendance->status : 'tidak_hadir';
                $attendanceData[$student->id][$session->id] = $status;

                // Update summary
                if ($attendance) {
                    $attendanceSummary[$student->id][$status]++;
                } else {
                    $attendanceSummary[$student->id]['tidak_hadir']++;
                }
            }
        }

        $className = $subjectClass->classes->name;
        $majorName = $subjectClass->classes->major->name;
        $semester = now()->month > 6 ? 'Ganjil' : 'Genap';
        $tahunAjaran = now()->month > 6
            ? now()->year . "/" . (now()->year + 1)
            : (now()->year - 1) . "/" . now()->year;

        // Dalam fungsi downloadAttendanceReport(), tambahkan:
        $logoProvBase64 = base64_encode(file_get_contents(public_path('images/logo-prov.png')));
        $logoSekolahBase64 = base64_encode(file_get_contents(public_path('images/logo-sekolah.png')));

        $data['logoProvData'] = 'data:image/png;base64,' . $logoProvBase64;
        $data['logoSekolahData'] = 'data:image/png;base64,' . $logoSekolahBase64;

        $data = [
            'title' => 'Laporan Kehadiran Mata Pelajaran',
            'className' => $className . ' - ' . $majorName,
            'subjectName' => $subjectClass->class_name,
            'semester' => $semester . ' - ' . $tahunAjaran,
            'sessions' => $sessions,
            'students' => $students,
            'attendanceData' => $attendanceData,
            'attendanceSummary' => $attendanceSummary,
            'teacherName' => $subjectClass->user->name,
            'logoProvData' => $data['logoProvData'],
            'logoSekolahData' => $data['logoSekolahData'],
        ];

        $pdf = PDF::loadView('pdfs.session-summary-report', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download('laporan-kehadiran-' . $subjectClass->class_name . '.pdf');
    }
}
