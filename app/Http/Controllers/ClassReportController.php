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
    public function generateAgendaReport(Request $request, SubjectClass $subjectClass)
    {
        // Filter berdasarkan bulan dan tahun jika ada
        $query = SubjectClassSession::where('subject_class_id', $subjectClass->id);

        if ($request->has('month') && $request->has('year')) {
            $month = $request->month;
            $year = $request->year;
            $query->whereMonth('class_date', $month)
                ->whereYear('class_date', $year);
        }

        // Dapatkan sesi kelas yang sudah difilter
        $sessions = $query->orderBy('class_date', 'asc')->get();

        $className = $subjectClass->classes->name;
        $majorName = $subjectClass->classes->major->name;
        $semester = now()->month > 6 ? 'Ganjil' : 'Genap';
        $tahunAjaran = now()->month > 6
            ? now()->year . "/" . (now()->year + 1)
            : (now()->year - 1) . "/" . now()->year;

        // Format periode laporan
        $periode = '';
        if ($request->has('month') && $request->has('year')) {
            $periode = Carbon::createFromDate($request->year, $request->month, 1)->locale('id')->isoFormat('MMMM Y');
        } else {
            $periode = 'Semua Periode';
        }

        // Tambahkan logo
        $logoProvBase64 = base64_encode(file_get_contents(public_path('images/logo-prov.png')));
        $logoSekolahBase64 = base64_encode(file_get_contents(public_path('images/logo-sekolah.png')));

        $data = [
            'title' => 'Agenda Kegiatan Belajar Mengajar',
            'className' => $className . ' - ' . $majorName,
            'subjectName' => $subjectClass->class_name,
            'semester' => $semester . ' - ' . $tahunAjaran,
            'periode' => $periode, // Tambahkan periode laporan
            'sessions' => $sessions,
            'teacherName' => $subjectClass->user->name,
            'logoProvData' => 'data:image/png;base64,' . $logoProvBase64,
            'logoSekolahData' => 'data:image/png;base64,' . $logoSekolahBase64,
        ];

        $pdf = PDF::loadView('pdfs.agenda-report', $data);

        // Tambahkan periode ke nama file jika ada
        $filename = 'agenda-kbm-' . $subjectClass->class_name;
        if ($request->has('month') && $request->has('year')) {
            $filename .= '-' . $periode;
        }
        $filename .= '.pdf';

        return $pdf->download($filename);
    }

    public function generateAttendanceReport(Request $request, SubjectClass $subjectClass)
    {
        // Filter berdasarkan bulan dan tahun jika ada
        $query = SubjectClassSession::where('subject_class_id', $subjectClass->id);

        if ($request->has('month') && $request->has('year')) {
            $month = $request->month;
            $year = $request->year;
            $query->whereMonth('class_date', $month)
                ->whereYear('class_date', $year);
        }

        // Dapatkan sesi kelas yang sudah difilter
        $sessions = $query->orderBy('class_date', 'asc')->get();

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

        // Format periode laporan
        $periode = '';
        if ($request->has('month') && $request->has('year')) {
            $periode = Carbon::createFromDate($request->year, $request->month, 1)->locale('id')->isoFormat('MMMM Y');
        } else {
            $periode = 'Semua Periode';
        }

        // Tambahkan logo
        $logoProvBase64 = base64_encode(file_get_contents(public_path('images/logo-prov.png')));
        $logoSekolahBase64 = base64_encode(file_get_contents(public_path('images/logo-sekolah.png')));

        $data = [
            'title' => 'Laporan Kehadiran Mata Pelajaran',
            'className' => $className . ' - ' . $majorName,
            'subjectName' => $subjectClass->class_name,
            'semester' => $semester . ' - ' . $tahunAjaran,
            'periode' => $periode, // Tambahkan periode laporan
            'sessions' => $sessions,
            'students' => $students,
            'attendanceData' => $attendanceData,
            'attendanceSummary' => $attendanceSummary,
            'teacherName' => $subjectClass->user->name,
            'logoProvData' => 'data:image/png;base64,' . $logoProvBase64,
            'logoSekolahData' => 'data:image/png;base64,' . $logoSekolahBase64,
        ];

        $pdf = PDF::loadView('pdfs.session-summary-report', $data)
            ->setPaper('a4', 'landscape');

        // Tambahkan periode ke nama file jika ada
        $filename = 'laporan-kehadiran-' . $subjectClass->class_name;
        if ($request->has('month') && $request->has('year')) {
            $filename .= '-' . $periode;
        }
        $filename .= '.pdf';

        return $pdf->download($filename);
    }
}
