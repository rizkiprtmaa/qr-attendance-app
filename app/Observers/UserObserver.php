<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Str;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\Logo\Logo;

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

        // Create generic logo
        $logo = new Logo(
            path: public_path('images/logo-sekolah.png'),
            resizeToWidth: 50,
            punchoutBackground: true
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode, $logo);

        // Simpan QR Code
        $filename = "qr_codes/{$user->name}_qr_presensi.png";
        Storage::disk('public')->put($filename, $result->getString());

        // Update path QR Code
        $user->qr_code_path = $filename;
        $user->save();
    }
}
