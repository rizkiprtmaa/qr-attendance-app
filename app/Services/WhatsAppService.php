<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\SystemSetting;

class WhatsAppService
{
    protected $apiUrl;
    protected $apiKey;
    protected $isEnabled;

    public function __construct()
    {
        $this->apiUrl = config('services.fonnte.url', 'https://api.fonnte.com/send');
        $this->apiKey = config('services.fonnte.key');

        // Periksa status aktif WhatsApp Gateway dari pengaturan sistem
        $this->isEnabled = SystemSetting::get('whatsapp_gateway_enabled', true);
    }

    public function sendAttendanceNotification($phoneNumber, $studentName, $attendanceType, $attendanceTime, $status)
    {
        // Jika WhatsApp Gateway dinonaktifkan, log pesan dan return false
        if (!$this->isEnabled) {
            Log::info("WhatsApp Gateway is disabled. Skipping notification for {$studentName}");
            return false;
        }

        try {
            $message = $this->composeAttendanceMessage($studentName, $attendanceType, $attendanceTime, $status);

            // Format nomor telepon dengan benar (tambahkan kode negara jika diperlukan)
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);

            // Kirim menggunakan curl sesuai dokumentasi Fonnte
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'target' => $phoneNumber . '|' . $studentName,
                    'message' => $message,
                    'countryCode' => '62', // Kode negara Indonesia
                ),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $this->apiKey
                ),
            ));

            $response = curl_exec($curl);
            $error = '';

            if (curl_errno($curl)) {
                $error = curl_error($curl);
                Log::error("Curl error in WhatsApp notification: " . $error);
            }

            curl_close($curl);

            if (!empty($error)) {
                return false;
            }

            // Log response for debugging
            Log::info("WhatsApp notification response: " . $response);

            // Parse response
            $responseData = json_decode($response, true);
            if (isset($responseData['status']) && $responseData['status'] === true) {
                Log::info("WhatsApp notification sent successfully to {$phoneNumber}");
                return true;
            }

            Log::error("Failed to send WhatsApp notification: " . $response);
            return false;
        } catch (\Exception $e) {
            Log::error("Error sending WhatsApp notification: " . $e->getMessage());
            return false;
        }
    }

    public function sendDailySummary($phoneNumber, $studentName, $attendanceData)
    {
        // Jika WhatsApp Gateway dinonaktifkan, log pesan dan return false
        if (!$this->isEnabled) {
            Log::info("WhatsApp Gateway is disabled. Skipping daily summary for {$studentName}");
            return false;
        }

        try {
            $message = $this->composeDailySummaryMessage($studentName, $attendanceData);

            // Format nomor telepon dengan benar
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);

            // Kirim menggunakan curl sesuai dokumentasi Fonnte
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array(
                    'target' => $phoneNumber . '|' . $studentName,
                    'message' => $message,
                    'countryCode' => '62', // Kode negara Indonesia
                ),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: ' . $this->apiKey
                ),
            ));

            $response = curl_exec($curl);
            $error = '';

            if (curl_errno($curl)) {
                $error = curl_error($curl);
                Log::error("Curl error in WhatsApp daily summary: " . $error);
            }

            curl_close($curl);

            if (!empty($error)) {
                return false;
            }

            // Log response for debugging
            Log::info("WhatsApp daily summary response: " . $response);

            // Parse response
            $responseData = json_decode($response, true);
            if (isset($responseData['status']) && $responseData['status'] === true) {
                Log::info("WhatsApp daily summary sent successfully to {$phoneNumber}");
                return true;
            }

            Log::error("Failed to send WhatsApp daily summary: " . $response);
            return false;
        } catch (\Exception $e) {
            Log::error("Error sending WhatsApp daily summary: " . $e->getMessage());
            return false;
        }
    }

    protected function formatPhoneNumber($phoneNumber)
    {
        // Hapus spasi, tanda kurung, dan tanda hubung
        $phoneNumber = preg_replace('/[() -]/', '', $phoneNumber);

        // Hapus kode negara jika sudah ada
        if (substr($phoneNumber, 0, 2) === '62') {
            return $phoneNumber;
        }

        // Hapus angka 0 di depan jika ada dan tambahkan 62
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '62' . substr($phoneNumber, 1);
        } else {
            $phoneNumber = '62' . $phoneNumber;
        }

        return $phoneNumber;
    }

    protected function composeAttendanceMessage($studentName, $attendanceType, $attendanceTime, $status)
    {
        $statusText = $this->getStatusText($status);
        $typeText = $attendanceType == 'datang' ? 'tiba di sekolah' : 'pulang dari sekolah';

        return "🔔 *INFORMASI PRESENSI* 🔔\n\n" .
            "Yth. Orang Tua/Wali dari:\n" .
            "*{$studentName}*\n\n" .
            "Putra/Putri Anda telah {$typeText} pada:\n" .
            "📅 Tanggal: " . date('d/m/Y') . "\n" .
            "⏰ Pukul: {$attendanceTime}\n" .
            "📝 Status: {$statusText}\n\n" .
            "Informasi ini dihasilkan secara otomatis oleh Sistem Presensi QR SMK.";
    }

    protected function composeDailySummaryMessage($studentName, $attendanceData)
    {
        $message = "📊 *RINGKASAN PRESENSI HARIAN* 📊\n\n" .
            "Yth. Orang Tua/Wali dari:\n" .
            "*{$studentName}*\n\n" .
            "Berikut ringkasan kehadiran putra/putri Anda pada hari *" . Carbon::now()->timezone('asia/jakarta')->locale('id')->translatedFormat('l, d/m/Y') . "*:\n\n";

        if (count($attendanceData) > 0) {
            foreach ($attendanceData as $index => $item) {
                $message .= ($index + 1) . ". *" . $item['subject'] . "*\n" .
                    "   ⏰ " . $item['time'] . "\n" .
                    "   📝 " . $this->getStatusText($item['status']) . "\n\n";
            }
        } else {
            $message .= "Tidak ada data kehadiran yang terekam hari ini.\n\n";
        }

        $message .= "Informasi ini dihasilkan secara otomatis oleh Sistem Presensi QR SMK Nurussalam.\n" .
            "Terima kasih atas perhatian dan kerjasamanya.";

        return $message;
    }

    protected function getStatusText($status)
    {
        return match ($status) {
            'kelas - hadir' => 'Hadir ✅',
            'kelas - tidak_hadir' => 'Tidak Hadir ❌',
            'kelas - terlambat' => 'Terlambat ⚠️',
            'kelas - izin' => 'Izin 📝',
            'kelas - sakit' => 'Sakit 🏥',
            'pulang - hadir' => 'Tepat waktu ✅',
            'pulang - tidak_hadir' => 'Belum absen pulang ❌',
            'hadir' => 'Tepat waktu ✅',
            'terlambat' => 'Terlambat ⚠️',
            'izin' => 'Izin 📝',
            'sakit' => 'Sakit 🏥',
            'tidak_hadir' => 'Tidak Hadir ❌',
            'pulang - pulang_cepat' => 'Pulang lebih awal ⚠️',
            default => ucfirst($status)
        };
    }
}
