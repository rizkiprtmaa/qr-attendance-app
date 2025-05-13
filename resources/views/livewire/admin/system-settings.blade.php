<?php

use Livewire\Volt\Component;
use App\Models\SystemSetting;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] class extends Component {
    public $attendanceAutoAbsentDatangTime;
    public $attendanceAutoAbsentPulangTime;
    public $whatsappGatewayEnabled;

    public function mount()
    {
        $this->attendanceAutoAbsentDatangTime = SystemSetting::get('attendance_auto_absent_datang_time', '08:30');
        $this->attendanceAutoAbsentPulangTime = SystemSetting::get('attendance_auto_absent_pulang_time', '14:30');
        $this->whatsappGatewayEnabled = SystemSetting::get('whatsapp_gateway_enabled', true);
    }

    public function save()
    {
        $this->validate([
            'attendanceAutoAbsentDatangTime' => 'required|date_format:H:i',
            'attendanceAutoAbsentPulangTime' => 'required|date_format:H:i|after:attendanceAutoAbsentDatangTime',
        ]);

        try {
            SystemSetting::set('attendance_auto_absent_datang_time', $this->attendanceAutoAbsentDatangTime);
            SystemSetting::set('attendance_auto_absent_pulang_time', $this->attendanceAutoAbsentPulangTime);
            SystemSetting::set('whatsapp_gateway_enabled', $this->whatsappGatewayEnabled);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Pengaturan berhasil disimpan!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Gagal menyimpan pengaturan: ' . $e->getMessage(),
            ]);
        }
    }

    public function toggleWhatsappGateway()
    {
        $this->whatsappGatewayEnabled = !$this->whatsappGatewayEnabled;
    }

    public function render(): mixed
    {
        return view('livewire.admin.system-settings');
    }
}; ?>

<div class="mb-5 mt-12 md:mt-4">
    <div class="mx-auto max-w-7xl">
        <!-- Header -->
        <div class="mb-8 hidden md:block">
            <h1 class="font-inter text-3xl font-semibold text-gray-900">Pengaturan Sistem</h1>
            <p class="mt-2 text-sm text-gray-500">Kelola pengaturan sistem untuk aplikasi presensi.</p>
        </div>

        <!-- Settings Form -->
        <form wire:submit="save">
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="px-4 py-5 sm:p-6">
                    <!-- Time Settings Section -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Pengaturan Waktu Absen Otomatis</h3>
                        <p class="mt-1 text-sm text-gray-500">Atur waktu otomatis untuk kehadiran siswa (QR Presensi).
                        </p>

                        <div class="mt-6 grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <label for="attendanceAutoAbsentDatangTime"
                                    class="block text-sm font-medium text-gray-700">Waktu
                                    Otomatis Tidak Hadir (Datang)</label>
                                <div class="mt-1">
                                    <input type="time" wire:model="attendanceAutoAbsentDatangTime"
                                        id="attendanceAutoAbsentDatangTime"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Siswa yang belum presensi QR hingga waktu ini akan
                                    otomatis
                                    tercatat tidak hadir.</p>
                                @error('attendanceAutoAbsentDatangTime')
                                    <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label for="attendanceAutoAbsentPulangTime"
                                    class="block text-sm font-medium text-gray-700">Waktu
                                    Otomatis Tidak Hadir (Pulang)</label>
                                <div class="mt-1">
                                    <input type="time" wire:model="attendanceAutoAbsentPulangTime"
                                        id="attendanceAutoAbsentPulangTime"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Siswa yang belum presensi QR pulang hingga waktu
                                    ini akan
                                    otomatis tercatat tidak hadir.</p>
                                @error('attendanceAutoAbsentPulangTime')
                                    <span class="mt-1 text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- WhatsApp Gateway Section -->
                    <div class="mb-8 border-t border-gray-200 pt-8">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">WhatsApp Gateway</h3>
                        <p class="mt-1 text-sm text-gray-500">Atur penggunaan WhatsApp Gateway untuk notifikasi kepada
                            orang
                            tua/wali siswa.</p>

                        <div class="mt-6">
                            <div class="flex items-center">
                                <button type="button" wire:click="toggleWhatsappGateway"
                                    class="{{ $whatsappGatewayEnabled ? 'bg-blue-600 justify-end' : 'bg-gray-200 justify-start' }} relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    role="switch" aria-checked="{{ $whatsappGatewayEnabled ? 'true' : 'false' }}">
                                    <span
                                        class="{{ $whatsappGatewayEnabled ? 'translate-x-2 me-2' : 'translate-x-0' }} pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                        <span
                                            class="{{ $whatsappGatewayEnabled ? 'opacity-0 ease-out duration-100' : 'opacity-100 ease-in duration-200' }} absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                                            aria-hidden="true">
                                            <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                                                <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span
                                            class="{{ $whatsappGatewayEnabled ? 'opacity-100 ease-in duration-200' : 'opacity-0 ease-out duration-100' }} absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                                            aria-hidden="true">
                                            <svg class="h-3 w-3 text-blue-600" fill="currentColor" viewBox="0 0 12 12">
                                                <path
                                                    d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                                            </svg>
                                        </span>
                                    </span>
                                </button>
                                <span class="ml-3 text-sm">
                                    <span
                                        class="font-medium text-gray-900">{{ $whatsappGatewayEnabled ? 'Aktif' : 'Nonaktif' }}</span>
                                    <span class="text-gray-500"> -
                                        {{ $whatsappGatewayEnabled ? 'Notifikasi WhatsApp diaktifkan' : 'Notifikasi WhatsApp dinonaktifkan' }}</span>
                                </span>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Fitur ini digunakan untuk mengirimkan notifikasi
                                WhatsApp kepada orang tua siswa tentang status kehadiran dan ringkasan harian.</p>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="bg-gray-50 px-4 py-3 text-right sm:px-6">
                    <button type="submit"
                        class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Simpan Pengaturan
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Toast Notification -->
    <div x-data="{
        toastMessage: '',
        toastType: '',
        showToast: false
    }"
        x-on:show-toast.window="
            const data = $event.detail[0] || $event.detail;
            toastMessage = data.message;
            toastType = data.type;
            showToast = true;
            setTimeout(() => showToast = false, 3000)
         ">
        <div x-cloak x-show="showToast" x-transition.opacity
            :class="toastType === 'success' ? 'bg-white text-gray-500' : 'bg-red-100 text-red-700'"
            class="fixed bottom-5 right-5 z-10 mb-4 flex w-full max-w-xs items-center rounded-lg p-4 shadow"
            role="alert">
            <div
                class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-500">
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                    viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z" />
                </svg>
            </div>
            <div class="ml-3 text-sm font-normal" x-text="toastMessage"></div>
            <button type="button" @click="showToast = false"
                class="-mx-1.5 -my-1.5 ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-900 focus:ring-2 focus:ring-gray-300">
                <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                </svg>
            </button>
        </div>
    </div>
</div>
