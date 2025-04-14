<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_date',
        'type',
        'status',
        'check_in_time',
        'check_out_time'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function recordAttendance($userId, $type)
    {
        $now = Carbon::now()->timezone('Asia/Jakarta');

        // Cek apakah sudah ada presensi hari ini
        $existingAttendance = self::where('user_id', $userId)
            ->where('attendance_date', $now->toDateString())
            ->first();

        // Validasi urutan presensi
        if ($type === 'pulang') {
            $checkIn = self::where('user_id', $userId)
                ->where('attendance_date', $now->toDateString())
                ->where('type', 'datang')
                ->first();

            if (!$checkIn) {
                throw new \Exception('Anda belum melakukan presensi datang hari ini.');
            }
        }

        // Jika sudah ada presensi untuk tipe tertentu, throw exception
        $typeExists = self::where('user_id', $userId)
            ->where('attendance_date', $now->toDateString())
            ->where('type', $type)
            ->exists();

        if ($typeExists) {
            throw new \Exception($type === 'datang'
                ? 'Anda sudah melakukan presensi datang hari ini.'
                : 'Anda sudah melakukan presensi pulang hari ini.');
        }

        // Tentukan status
        $status = self::determineAttendanceStatus($type, $now);

        // Buat presensi baru
        return self::create([
            'user_id' => $userId,
            'attendance_date' => $now->toDateString(),
            'type' => $type,
            'status' => $status,
            'check_in_time' => $now->toTimeString()
        ]);
    }

    protected static function determineAttendanceStatus($type, $checkTime = null)
    {
        if ($checkTime === null) {
            $checkTime = Carbon::now()->timezone('Asia/Jakarta');
        }

        Log::info('Check Time: ' . $checkTime->toDateTimeString());

        if ($type === 'datang') {
            /// Definisikan batas waktu presensi datang
            $jamAwalValid = $checkTime->copy()->setTime(0, 0, 0);
            $jamAkhirValid = $checkTime->copy()->setTime(7, 0, 0);

            Log::info('Jam Awal Valid: ' . $jamAwalValid->toDateTimeString());
            Log::info('Jam Akhir Valid: ' . $jamAkhirValid->toDateTimeString());

            // Gunakan metode compare untuk validasi waktu
            return $checkTime->greaterThanOrEqualTo($jamAwalValid) &&
                $checkTime->lessThan($jamAkhirValid)
                ? 'hadir'
                : 'terlambat';
        }

        if ($type === 'pulang') {
            // Jam pulang sebelum 14:00 dianggap pulang cepat
            $jamPulangNormal = $checkTime->copy()->setTime(14, 0, 0);

            return $checkTime->lt($jamPulangNormal)
                ? 'pulang_cepat'
                : 'hadir';
        }

        return 'hadir';
    }

    // Tambahan method untuk mendapatkan detail presensi
    public static function getTodayAttendanceDetails($userId)
    {
        $now = Carbon::now()->timezone('Asia/Jakarta');

        return self::where('user_id', $userId)
            ->where('attendance_date', $now->toDateString())
            ->get();
    }
}
