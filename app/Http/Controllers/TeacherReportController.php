<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use App\Models\SubjectClass;
use App\Models\SubjectClassSession;
use App\Models\Attendance;
use App\Models\SubstitutionRequest;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TeacherReportController extends Controller
{
    public function generateTeacherAttendanceReport(Request $request)
    {
        // Validasi input
        $request->validate([
            'month' => 'required|numeric|min:1|max:12',
            'year' => 'required|numeric|min:2020|max:' . (date('Y') + 1),
        ]);

        $month = $request->month;
        $year = $request->year;

        // Format nama bulan dalam Bahasa Indonesia
        $monthNames = [
            1 => 'JANUARI',
            2 => 'FEBRUARI',
            3 => 'MARET',
            4 => 'APRIL',
            '01' => 'JANUARI',
            '02' => 'FEBRUARI',
            '03' => 'MARET',
            '04' => 'APRIL',
            '05' => 'MEI',
            '06' => 'JUNI',
            '07' => 'JULI',
            '08' => 'AGUSTUS',
            '09' => 'SEPTEMBER',
            '10' => 'OKTOBER',
            '11' => 'NOVEMBER',
            '12' => 'DESEMBER',
            5 => 'MEI',
            6 => 'JUNI',
            7 => 'JULI',
            8 => 'AGUSTUS',
            9 => 'SEPTEMBER',
            10 => 'OKTOBER',
            11 => 'NOVEMBER',
            12 => 'DESEMBER'
        ];

        $monthName = $monthNames[$month];

        // Ambil semua guru (bukan karyawan dan bukan kepala sekolah)
        $teachers = User::whereHas('roles', function ($query) {
            $query->where('name', 'teacher');
        })->whereDoesntHave('roles', function ($query) {
            $query->where('name', 'kepala_sekolah');
        })->whereHas('teacher', function ($query) {
            $query->where('is_karyawan', false);
        })
            ->with('teacher')
            ->get();

        $teacherData = [];

        foreach ($teachers as $index => $teacher) {
            // Ambil semua kelas yang diajar oleh guru ini
            $subjectClasses = SubjectClass::where('user_id', $teacher->id)->pluck('id')->toArray();

            // Hitung JP per bulan (sesi reguler yang tidak digantikan)
            $regularJP = SubjectClassSession::whereIn('subject_class_id', $subjectClasses)
                ->whereNull('created_by_substitute')
                ->whereMonth('class_date', $month)
                ->whereYear('class_date', $year)
                ->sum('jam_pelajaran');

            // Hitung JP kelas yang digantikan (tidak hadir)
            $substitutedJP = SubjectClassSession::whereIn('subject_class_id', $subjectClasses)
                ->whereNotNull('substitution_request_id')
                ->whereMonth('class_date', $month)
                ->whereYear('class_date', $year)
                ->sum('jam_pelajaran');

            // Hitung JP sebagai guru pengganti
            $substituteTeacherJP = SubjectClassSession::whereHas('substitutionRequest', function ($query) use ($teacher) {
                $query->where('substitute_teacher_id', $teacher->id)
                    ->whereIn('status', ['approved', 'completed']);
            })
                ->whereMonth('class_date', $month)
                ->whereYear('class_date', $year)
                ->sum('jam_pelajaran');

            // Hitung total JP bulan ini
            $monthlyJP = $regularJP + $substituteTeacherJP;

            // Hitung JP per minggu (JP per bulan dibagi 4)
            $weeklyJP = round($monthlyJP / 4);

            // Hitung persentase kehadiran
            $totalJP = $regularJP + $substitutedJP;
            $percentage = $totalJP > 0 ? round(($regularJP / $totalJP) * 100) : 0;

            $teacherData[] = [
                'no' => $index + 1,
                'name' => $teacher->name,
                'code' => $teacher->teacher->nuptk ?? '-',
                'weekly_jp' => $weeklyJP,
                'monthly_jp' => $monthlyJP,
                'absent_count' => $substitutedJP,
                'present_count' => $regularJP,
                'percentage' => $percentage
            ];
        }

        // Tambahkan logo
        $logoProvBase64 = base64_encode(file_get_contents(public_path('images/logo-prov.png')));
        $logoSekolahBase64 = base64_encode(file_get_contents(public_path('images/logo-sekolah.png')));

        $data = [
            'title' => 'PERSENTASE KEHADIRAN GURU MAPEL',
            'month' => $monthName,
            'year' => $year,
            'academic_year' => $this->getAcademicYear(),
            'teachers' => $teacherData,
            'logoProvData' => 'data:image/png;base64,' . $logoProvBase64,
            'logoSekolahData' => 'data:image/png;base64,' . $logoSekolahBase64,
        ];

        $pdf = PDF::loadView('pdfs.teacher-attendance-report', $data);

        return $pdf->download('laporan-kehadiran-guru-' . $monthName . '-' . $year . '.pdf');
    }

    // Untuk laporan absensi karyawan dan kepala sekolah
    public function generateStaffAttendanceReport(Request $request)
    {
        // Validasi input
        $request->validate([
            'month' => 'required|numeric|min:1|max:12',
            'year' => 'required|numeric|min:2020|max:' . (date('Y') + 1),
        ]);

        $month = $request->month;
        $year = $request->year;

        // Format nama bulan dalam Bahasa Indonesia
        $monthNames = [
            1 => 'JANUARI',
            2 => 'FEBRUARI',
            3 => 'MARET',
            4 => 'APRIL',
            5 => 'MEI',
            6 => 'JUNI',
            7 => 'JULI',
            8 => 'AGUSTUS',
            9 => 'SEPTEMBER',
            10 => 'OKTOBER',
            11 => 'NOVEMBER',
            12 => 'DESEMBER',
            '01' => 'JANUARI',
            '02' => 'FEBRUARI',
            '03' => 'MARET',
            '04' => 'APRIL',
            '05' => 'MEI',
            '06' => 'JUNI',
            '07' => 'JULI',
            '08' => 'AGUSTUS',
            '09' => 'SEPTEMBER',
            '10' => 'OKTOBER',
            '11' => 'NOVEMBER',
            '12' => 'DESEMBER',
        ];

        $monthName = $monthNames[$month];

        // Dapatkan jumlah hari dalam bulan
        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $days = range(1, $daysInMonth);

        // Tandai hari weekend (HANYA MINGGU)
        $isWeekend = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($year, $month, $day);
            // Hanya hari Minggu (0) yang dianggap weekend
            $isWeekend[$day] = $date->dayOfWeek === 0;
        }

        // Ambil karyawan dan kepala sekolah
        $staffUsers = User::where(function ($query) {
            $query->whereHas('teacher', function ($q) {
                $q->where('is_karyawan', true);
            })
                ->orWhereHas('roles', function ($q) {
                    $q->where('name', 'kepala_sekolah');
                });
        })->get();

        // Buat range tanggal untuk bulan yang dipilih
        $firstDayOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $lastDayOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Ambil semua data kehadiran untuk bulan ini
        $attendanceData = Attendance::whereIn('user_id', $staffUsers->pluck('id'))
            ->whereBetween('attendance_date', [$firstDayOfMonth->format('Y-m-d'), $lastDayOfMonth->format('Y-m-d')])
            ->get();



        // Struktur data untuk laporan
        $staffData = [];

        foreach ($staffUsers as $index => $staff) {
            $attendance = [];
            $summary = [
                'present' => 0, // Hadir
                'late' => 0,    // Terlambat
                'sick' => 0,    // Sakit
                'permission' => 0, // Izin
                'absent' => 0,  // Tidak hadir
            ];

            // Hitung hari kerja (Senin-Sabtu) dalam bulan ini
            $workingDays = [];
            $currentDay = $firstDayOfMonth->copy();
            while ($currentDay->lte($lastDayOfMonth)) {
                // Hanya Minggu yang dianggap bukan hari kerja
                if ($currentDay->dayOfWeek !== 0) {
                    $workingDays[] = $currentDay->format('Y-m-d');
                }
                $currentDay->addDay();
            }

            // Filter kehadiran staff ini
            $staffAttendances = $attendanceData->where('user_id', $staff->id);



            // Siapkan array untuk menyimpan status per hari
            $dailyStatus = [];

            // Update status kehadiran berdasarkan data yang ada
            foreach ($staffAttendances as $staffAttendance) {
                $date = $staffAttendance->attendance_date;
                $day = Carbon::parse($date)->day;

                // Prioritaskan status tertentu
                if ($staffAttendance->type === 'datang') {
                    $dailyStatus[$day] = $staffAttendance->status;
                }
                // Jika sudah ada status datang, jangan timpa dengan status pulang
                elseif ($staffAttendance->type === 'pulang' && !isset($dailyStatus[$day])) {
                    $dailyStatus[$day] = $staffAttendance->status;
                }
            }

            // Isi status kehadiran untuk setiap hari
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = Carbon::createFromDate($year, $month, $day);

                if ($date->dayOfWeek === 0) { // Hanya hari Minggu yang dilewati
                    continue;
                }

                $status = $dailyStatus[$day] ?? null;

                // Tentukan simbol yang akan ditampilkan
                if ($status === 'hadir' || $status === 'terlambat') {
                    $attendance[$day] = '✓'; // Gunakan v (ceklis) untuk hadir dan terlambat
                    if ($status === 'hadir') {
                        $summary['present']++;
                    } else {
                        $summary['late']++;
                    }
                } elseif ($status === 'sakit') {
                    $attendance[$day] = 'S';
                    $summary['sick']++;
                } elseif ($status === 'izin') {
                    $attendance[$day] = 'I';
                    $summary['permission']++;
                } elseif ($status === 'tidak_hadir') {
                    $attendance[$day] = 'A'; // Gunakan A untuk tidak hadir
                    $summary['absent']++;
                } else {
                    // Jika tidak ada data, biarkan kosong
                    $attendance[$day] = '';
                }
            }

            $staffData[] = [
                'name' => $staff->name,
                'days' => $attendance,
                'summary' => $summary
            ];
        }

        // Tambahkan logo
        $logoProvBase64 = base64_encode(file_get_contents(public_path('images/logo-prov.png')));
        $logoSekolahBase64 = base64_encode(file_get_contents(public_path('images/logo-sekolah.png')));

        $data = [
            'title' => 'ABSENSI KEHADIRAN KEPALA SEKOLAH DAN TENAGA KEPENDIDIKAN',
            'month' => $monthName,
            'year' => $year,
            'days' => $days,
            'isWeekend' => $isWeekend,
            'staffs' => $staffData,
            'teacher' => 'Admin TU',
            'class' => (object)['name' => '-'],
            'major' => '-',
            'logoProvData' => 'data:image/png;base64,' . $logoProvBase64,
            'logoSekolahData' => 'data:image/png;base64,' . $logoSekolahBase64,
        ];

        $pdf = PDF::loadView('pdfs.staff-attendance-report', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('laporan-absensi-staff-' . $monthName . '-' . $year . '.pdf');
    }

    private function getAcademicYear()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        if ($currentMonth > 6) {
            return $currentYear . '/' . ($currentYear + 1);
        } else {
            return ($currentYear - 1) . '/' . $currentYear;
        }
    }
}
