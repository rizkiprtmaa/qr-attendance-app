<?php

namespace App\Console\Commands;

use App\Jobs\SendDailySummary;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarkStudentAbsentAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:mark-absent {type : Tipe presensi (datang/pulang)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tandai siswa yang tidak hadir otomatis berdasarkan waktu';

    // Maksimum delay dalam detik (untuk mencegah error)
    const MAX_DELAY_SECONDS = 3600; // 1 jam

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::now()->timezone('Asia/Jakarta')->format('Y-m-d');
        $type = $this->argument('type');
        $now = Carbon::now()->timezone('Asia/Jakarta');

        // Dapatkan waktu dari pengaturan
        $settingKey = "attendance_auto_absent_{$type}_time";
        $absentTime = SystemSetting::get($settingKey);

        if (!$absentTime) {
            $this->error("Pengaturan waktu absen otomatis untuk tipe '{$type}' tidak ditemukan");
            $absentTime = $type == 'datang' ? '08:30' : '14:30'; // Default fallback
            $this->info("Menggunakan waktu default: {$absentTime}");
        }

        $this->info("Memproses ketidakhadiran siswa untuk tanggal {$today}, tipe: {$type}");
        $this->info("Waktu auto absent: {$absentTime}");

        // Pengecekan apakah waktu sekarang sudah melewati waktu absen otomatis
        list($hour, $minute) = explode(':', $absentTime);
        $autoAbsentTime = Carbon::now()->timezone('Asia/Jakarta')->setHour((int)$hour)->setMinute((int)$minute)->setSecond(0);

        if ($now->lt($autoAbsentTime)) {
            $this->warn("Waktu saat ini belum mencapai waktu absen otomatis. Aborting...");
            return Command::SUCCESS;
        }

        // Hanya proses siswa
        $students = Student::with('user')->get();
        $studentsProcessed = 0;
        $this->info("Memproses {$students->count()} siswa...");

        // Generate a batch ID for this run
        $batchId = 'absent_batch_' . uniqid();

        foreach ($students as $index => $student) {
            if (!$student->user) {
                $this->warn("Siswa ID {$student->id} tidak memiliki user terkait. Skipping...");
                continue;
            }

            // Cek apakah siswa sudah memiliki record attendance hari ini
            $existingAttendance = Attendance::where('user_id', $student->user->id)
                ->where('attendance_date', $today)
                ->where('type', $type)
                ->first();

            // Jika belum presensi, tandai tidak hadir
            if (!$existingAttendance) {
                Attendance::create([
                    'user_id' => $student->user->id,
                    'attendance_date' => $today,
                    'type' => $type,
                    'status' => 'tidak_hadir',
                    'check_in_time' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                ]);
                $studentsProcessed++;

                // Only dispatch WhatsApp jobs if it's "pulang" type and WhatsApp is enabled
                if ($type === 'pulang') {
                    // Calculate staggered delay
                    $baseDelay = $index * 30; // 30 detik spacing

                    // Tambahkan randomness untuk mencegah timing yang sama persis
                    $randomDelay = rand(5, 15);
                    $totalDelay = $baseDelay + $randomDelay;

                    // Pastikan delay tidak melebihi batas maksimum
                    $totalDelay = min($totalDelay, self::MAX_DELAY_SECONDS);

                    // Dispatch the job dengan delay yang aman
                    SendDailySummary::dispatch($student->user->id, $today)
                        ->delay(now()->addSeconds($totalDelay));

                    $this->info("Scheduled WhatsApp message for {$student->user->name} with {$totalDelay}s delay");
                }
            }
        }

        $this->info("Total {$studentsProcessed} siswa ditandai tidak hadir.");

        if ($type === 'pulang') {
            $this->info("WhatsApp messages have been scheduled with delays to prevent banning.");
        }

        return Command::SUCCESS;
    }
}
