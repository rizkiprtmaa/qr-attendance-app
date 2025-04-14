<?php

namespace App\Http\Controllers;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class UserCardController extends Controller
{
    public function downloadUserCard(User $user)
    {
        // Gunakan user dari parameter atau user yang sedang login
        $user = $user ?: Auth::user();

        // Pastikan user ditemukan
        if (!$user) {
            return back()->with('error', 'User tidak ditemukan');
        }

        if ($user->teacher->is_karyawan) {
            $role = 'Karyawan';
        } else {
            $role = $user->teacher ? 'Guru' : 'Siswa';
        }

        // Ambil data tambahan sesuai peran
        if ($user->teacher && !$user->teacher->is_karyawan) {
            $identifier = $user->teacher->nuptk ?? '-';
            $position = $user->teacher->position ?? 'Guru';
            $additionalInfo = $position;
        } elseif ($user->student) {
            $identifier = $user->student->nisn ?? '-';
            $class = $user->student->classes->name ?? '-';
            $major = $user->student->classes->major->name ?? '-';
            $additionalInfo = "$class - $major";
        } else {
            $identifier = $user->teacher->nuptk ?? '-';
            $additionalInfo = "Karyawan";
        }

        // Path QR Code - gunakan pendekatan yang sama seperti di modal
        $qrCodeUrl = null;
        if ($user->qr_code_path) {
            // Konversi URL storage menjadi path disk untuk file_get_contents
            $qrCodeUrl = Storage::url($user->qr_code_path);
            // Hapus /storage dari awal URL jika ada
            $qrCodeUrl = str_replace('/storage/', '', $qrCodeUrl);

            // Buat path lengkap untuk public/storage/
            $fullQrCodePath = public_path('storage/' . $qrCodeUrl);

            // Encode sebagai base64
            $qrCodeBase64 = base64_encode(file_get_contents($fullQrCodePath));
            $qrCodeDataUri = 'data:image/png;base64,' . $qrCodeBase64;
        } else {
            // Fallback ke placeholder
            $qrCodeDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/qr-placeholder.png')));
        }

        // Encode logo untuk PDF
        $logoSekolahBase64 = base64_encode(file_get_contents(public_path('images/logo-sekolah.png')));
        $logoProvBase64 = base64_encode(file_get_contents(public_path('images/logo-prov.png')));

        // Tentukan tahun ajaran
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $academicYear = $currentMonth > 6
            ? "$currentYear/" . ($currentYear + 1)
            : ($currentYear - 1) . "/$currentYear";

        // Data untuk PDF
        $data = [
            'name' => $user->name,
            'identifier' => $identifier,
            'additionalInfo' => $additionalInfo,
            'role' => $role,
            'qrCodePath' => $qrCodeDataUri,
            'logoSekolahData' => 'data:image/png;base64,' . $logoSekolahBase64,
            'logoProvData' => 'data:image/png;base64,' . $logoProvBase64,
            'academicYear' => $academicYear,
        ];



        // Buat PDF dengan custom format
        $pdf = PDF::loadView('pdfs.user-card', $data);



        // Atur ukuran kertas ke ukuran ID card
        $pdf->setPaper([0, 0, 240, 153]); // 240pt x 153pt (85mm x 54mm)

        // Set opsi minimal
        $pdf->setOptions([
            'isPhpEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'margin-top' => 0,
            'margin-right' => 0,
            'margin-bottom' => 0,
            'margin-left' => 0,
        ]);
    }

    // Method untuk route langsung
    public function download(User $user)
    {
        return $this->downloadUserCard($user);
    }
}
