<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-row items-center justify-between">
            <div class="flex flex-col gap-2">
                <h2 class="font-inter text-xl font-medium leading-tight text-gray-800">
                    {{ __('Presensi Kelas') }}
                </h2>
                <p class="font-inter text-sm text-gray-600">Buat dan atur presensi kelas dari sekarang</p>
            </div>
            <div>
                <p class="text-end font-inter text-sm text-gray-600">
                    {{ \Carbon\Carbon::now()->timezone('Asia/Jakarta')->locale('id')->translatedFormat('l, d M Y') }}
                </p>
            </div>
        </div>
    </x-slot>



    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <livewire:teacher.create-attendances-class />

        </div>
    </div>
</x-app-layout>
