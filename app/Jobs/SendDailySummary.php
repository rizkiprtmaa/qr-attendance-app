<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\SubjectClass;
use Illuminate\Bus\Queueable;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use App\Models\SubjectClassAttendance;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendDailySummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $date;

    public function __construct($userId, $date)
    {
        $this->userId = $userId;
        $this->date = $date;
    }

    public function handle(WhatsAppService $whatsAppService): void
    {
        Log::info("Starting SendDailySummary job for user: {$this->userId} on date: {$this->date}");

        // Ambil siswa dan nomor orang tua
        $user = \App\Models\User::find($this->userId);
        if (!$user) {
            Log::error("User not found for ID: {$this->userId}");
            return;
        }
        Log::info("Found user: {$user->name}");

        $student = $user->student;
        if (!$student) {
            Log::error("Student data not found for user: {$user->name}");
            return;
        }
        Log::info("Found student data");

        if (!$student->parent_number) {
            Log::error("No parent number found for student: {$user->name}");
            return;
        }
        Log::info("Found parent number: {$student->parent_number}");

        // Ambil data kehadiran QR hari ini (datang & pulang)
        $qrAttendances = Attendance::where('user_id', $this->userId)
            ->where('attendance_date', $this->date)
            ->get()
            ->groupBy('type');

        // Tampilkan log tentang kehadiran yang ditemukan
        Log::info("Found " . ($qrAttendances->count() ? $qrAttendances->count() : 0) . " attendance records for today");

        // Format data untuk ringkasan
        $attendanceData = [];

        // Tambahkan data kehadiran sekolah (datang & pulang)
        if ($qrAttendances->has('datang')) {
            $datang = $qrAttendances['datang']->first();
            $attendanceData[] = [
                'subject' => 'Kehadiran Sekolah',
                'time' => $datang->check_in_time,
                'status' => $datang->status
            ];
            Log::info("Added school attendance (datang) to summary: " . $datang->check_in_time);
        }

        $classAttendances = SubjectClassAttendance::where('student_id', $student->id)
            ->whereDate('check_in_time', $this->date)
            ->with('session')
            ->get();

        foreach ($classAttendances as $classAttendance) {
            $attendanceData[] = [
                'subject' => $classAttendance->session->subjectClass->class_name,
                'time' => $classAttendance->check_in_time->format('H:i'),
                'status' => "kelas" . " - " . $classAttendance->status
            ];
        }


        // Log attendance data array
        Log::info("Compiled attendance data: " . json_encode($attendanceData));

        if ($qrAttendances->has('pulang')) {
            $pulang = $qrAttendances['pulang']->first();
            $attendanceData[] = [
                'subject' => 'Kepulangan Sekolah',
                'time' => $pulang->check_in_time,
                'status' => "pulang" . " - " . $pulang->status
            ];
            Log::info("Added school departure (pulang) to summary: " . $pulang->check_in_time);
        }

        // Jika sistem presensi QR dan presensi kelas terpisah, Anda bisa menambahkan kode untuk 
        // mengambil data presensi kelas di sini, misalnya dari tabel subject_class_attendances

        // Contoh (sesuaikan dengan struktur database Anda):



        // Jika tidak ada data sama sekali
        if (empty($attendanceData)) {
            Log::warning("No attendance data available for summary. Adding default message.");
            $attendanceData[] = [
                'subject' => 'Tidak Ada Data Kehadiran',
                'time' => '-',
                'status' => 'tidak_hadir'
            ];
        }

        // Kirim ringkasan harian
        $result = $whatsAppService->sendDailySummary(
            $student->parent_number,
            $user->name,
            $attendanceData
        );

        if ($result) {
            Log::info("Successfully sent daily summary for student: {$user->name}");
        } else {
            Log::error("Failed to send daily summary for student: {$user->name}");
        }
    }
}
