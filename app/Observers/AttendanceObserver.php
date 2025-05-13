<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Jobs\SendAttendanceNotification;
use App\Jobs\SendDailySummary;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AttendanceObserver
{
    // Kunci cache untuk rate limiting
    const RATE_LIMIT_CACHE_KEY = 'whatsapp_observer_rate_limit';

    // Jumlah maksimal job yang bisa diproses per menit
    const MAX_JOBS_PER_MINUTE = 10;

    // Maksimum delay dalam detik (untuk mencegah error)
    // 1 jam = 3600 detik, seharusnya cukup
    const MAX_DELAY_SECONDS = 3600;

    /**
     * Handle the Attendance "created" event.
     */
    public function created(Attendance $attendance): void
    {

        // Jika ini attendance pulang, kirim ringkasan
        if ($attendance->type == 'pulang') {
            // Tambahkan rate limiting dan delay
            $delay = $this->calculateDelay();

            Log::info("Scheduling SendDailySummary for user {$attendance->user_id} with delay of {$delay} seconds");

            // Dispatch job dengan delay yang dihitung
            SendDailySummary::dispatch($attendance->user_id, $attendance->attendance_date)
                ->delay(now()->addSeconds($delay));
        }
    }

    /**
     * Hitung delay untuk rate limiting
     * 
     * @return int Delay dalam detik
     */
    private function calculateDelay(): int
    {
        // Inisialisasi rate limit jika belum ada
        if (!Cache::has(self::RATE_LIMIT_CACHE_KEY)) {
            Cache::put(self::RATE_LIMIT_CACHE_KEY, [
                'count' => 0,
                'reset_at' => now()->addMinute(),
                'last_delay' => 0
            ], 120); // 2 menit TTL untuk keamanan
        }

        $rateLimit = Cache::get(self::RATE_LIMIT_CACHE_KEY);

        // Reset counter jika waktu sudah berlalu
        if (now()->gt($rateLimit['reset_at'])) {
            $newCount = 1;
            $baseDelay = 5; // Delay dasar 5 detik

            Cache::put(self::RATE_LIMIT_CACHE_KEY, [
                'count' => $newCount,
                'reset_at' => now()->addMinute(),
                'last_delay' => $baseDelay
            ], 120);

            return $baseDelay;
        }

        // Increment counter
        $newCount = $rateLimit['count'] + 1;

        // Hitung delay progresif berdasarkan jumlah request
        // Semakin banyak request, semakin lama delay-nya
        $baseDelay = 5; // Delay dasar 5 detik

        // Formula delay progresif: 
        // - Jika masih di bawah threshold, tambahkan delay linier
        // - Jika melebihi threshold, tambahkan delay eksponensial
        if ($newCount <= self::MAX_JOBS_PER_MINUTE) {
            // Delay linier: 5, 10, 15, 20, ... detik
            $calculatedDelay = $baseDelay * $newCount;
        } else {
            // Delay eksponensial: last_delay * 1.5
            $calculatedDelay = $rateLimit['last_delay'] * 1.5;
        }

        // Tambahkan random jitter (0-10 detik) untuk mencegah request bersamaan
        $jitter = rand(0, 10);
        $finalDelay = $calculatedDelay + $jitter;

        // Pastikan delay tidak melebihi batas maksimum
        $finalDelay = min($finalDelay, self::MAX_DELAY_SECONDS);

        // Update rate limit di cache
        Cache::put(self::RATE_LIMIT_CACHE_KEY, [
            'count' => $newCount,
            'reset_at' => $rateLimit['reset_at'],
            'last_delay' => $finalDelay
        ], 120);

        return (int)$finalDelay;
    }
}
