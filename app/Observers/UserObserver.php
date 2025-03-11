<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Str;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

class UserObserver
{
    public function created(User $user)
    {
        // Generate QR Token
        $qrToken = Str::uuid();
        $user->qr_token = $qrToken;

        // Generate QR Image
        $qrCode = QrCode::create($qrToken)
            ->setSize(300)
            ->setMargin(10);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Simpan QR Code
        $filename = "qr_codes/{$user->name}_qr_presensi.png";
        Storage::disk('public')->put($filename, $result->getString());

        // Update path QR Code
        $user->qr_code_path = $filename;
        $user->save();
    }
}
