<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\TeacherSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TeacherAttendanceReportController extends Controller
{
    public function generateDailyReport(Request $request)
    {
        // Validasi input
        $request->validate([
            'date' => 'nullable|date',
        ]);

        // Jika tanggal tidak disediakan, gunakan hari ini
        $date = $request->date ? Carbon::parse($request->date) : Carbon::today()->timezone('Asia/Jakarta');

        // Format untuk tampilan
        $formattedDate = $date->locale('id')->isoFormat('dddd, D MMMM Y');
        $dayOfWeek = $date->locale('id')->dayName; // Senin, Selasa, dst.

        // Ambil semua guru
        $teachers = User::whereHas('roles', function ($query) {
            $query->where('name', 'teacher');
        })->with(['teacher'])->get();

        // Data untuk laporan
        $reportData = [];

        foreach ($teachers as $index => $teacher) {
            // Ambil jadwal mengajar guru untuk hari tersebut
            $schedule = TeacherSchedule::where('user_id', $teacher->id)
                ->where('day_of_week', $dayOfWeek)
                ->first();

            // Gunakan jadwal default jika tidak ada
            $scheduleTime = $schedule
                ? Carbon::parse($schedule->start_time)->format('H:i') . '-' . Carbon::parse($schedule->end_time)->format('H:i')
                : '07:30-13:50';

            // Ambil data presensi guru
            $attendance = Attendance::where('user_id', $teacher->id)
                ->where('attendance_date', $date->toDateString())
                ->where('type', 'datang')
                ->first();

            $reportData[] = [
                'no' => $index + 1,
                'name' => $teacher->name,
                'schedule_time' => $scheduleTime,
                'attendance_time' => $attendance ? Carbon::parse($attendance->check_in_time)->format('H:i') : '-',
                'status' => $attendance
                    ? ($attendance->status == 'hadir' ? 'TEPAT WAKTU' : strtoupper($attendance->status))
                    : 'TIDAK HADIR',
            ];
        }

        // Buat PDF
        $pdf = PDF::loadView('pdfs.teacher-attendance-daily', [
            'date' => $formattedDate,
            'teachers' => $reportData,
        ]);

        return $pdf->download('laporan-kehadiran-guru-' . $date->format('Y-m-d') . '.pdf');
    }
}
