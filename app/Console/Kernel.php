<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Untuk siswa - presensi datang
        $schedule->command('attendance:mark-student-absent datang')
            ->dailyAt('08:00')
            ->timezone('Asia/Jakarta');

        // Untuk siswa - presensi pulang
        $schedule->command('attendance:mark-student-absent pulang')
            ->dailyAt('15:00')
            ->timezone('Asia/Jakarta');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
