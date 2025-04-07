<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkStudentAbsentAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:mark-student-absent {type=datang : Tipe presensi (datang/pulang)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tandai hanya siswa yang tidak hadir';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::now()->format('Y-m-d');
        $type = $this->argument('type');

        $this->info("Memproses ketidakhadiran siswa untuk tanggal {$today}, tipe: {$type}");

        // Hanya proses siswa
        $students = Student::with('user')->get();
        $studentsProcessed = 0;

        foreach ($students as $student) {
            // Cek apakah siswa sudah memiliki record attendance hari ini
            $existingAttendance = Attendance::where('user_id', $student->user_id)
                ->where('attendance_date', $today)
                ->where('type', $type)
                ->first();

            // Jika belum presensi, tandai tidak hadir
            if (!$existingAttendance) {
                Attendance::create([
                    'user_id' => $student->user_id,
                    'attendance_date' => $today,
                    'type' => $type,
                    'status' => 'tidak_hadir',
                    'check_in_time' => null,
                ]);
                $studentsProcessed++;
            }
        }

        $this->info("Total {$studentsProcessed} siswa ditandai tidak hadir.");

        return Command::SUCCESS;
    }
}
