<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Scheduling\Schedule;

class DynamicSchedulerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }



    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            try {
                // Ambil pengaturan waktu absen otomatis
                $datangTime = SystemSetting::get('attendance_auto_absent_datang_time', '08:30');
                $pulangTime = SystemSetting::get('attendance_auto_absent_pulang_time', '14:30');

                Log::info("Dynamic scheduler loaded with datang time: {$datangTime}, pulang time: {$pulangTime}");

                // Schedule untuk absen datang
                $schedule->command('attendance:mark-student-absent datang')
                    ->days([1, 2, 3, 4, 5, 6]) // Senin-Sabtu
                    ->dailyAt($datangTime)
                    ->timezone('Asia/Jakarta')
                    ->withoutOverlapping()
                    ->appendOutputTo(storage_path('logs/attendance-auto-absent.log'));

                // Schedule untuk absen pulang
                $schedule->command('attendance:mark-student-absent pulang')
                    ->days([1, 2, 3, 4, 5, 6]) // Senin-Sabtu
                    ->dailyAt($pulangTime)
                    ->timezone('Asia/Jakarta')
                    ->withoutOverlapping()
                    ->appendOutputTo(storage_path('logs/attendance-auto-absent.log'));
            } catch (\Exception $e) {
                Log::error("Error setting up dynamic scheduler: " . $e->getMessage());
            }
        });
    }
}
