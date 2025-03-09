<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class UserObserver
{
    public function created(User $user)
    {
        // Generate QR Token
        $qrToken = Str::uuid();
        $user->qr_token = $qrToken;

        // Generate QR Image
        $qrCode = QrCode::size(300)
            ->generate(route('attendance.scan', $qrToken));

        // Simpan QR Image
        $filename = "qr_codes/user_{$user->id}_qr.svg";
        Storage::disk('public')->put($filename, $qrCode);

        // Simpan path QR ke user
        $user->qr_code_path = $filename;
        $user->save();
    }
}
