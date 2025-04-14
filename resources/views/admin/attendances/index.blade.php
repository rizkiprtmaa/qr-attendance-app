<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-inter text-3xl font-semibold leading-tight text-slate-900">
                    {{ __('Rekap Presensi QR') }}
                </h2>

            </div>

        </div>
    </x-slot>





    <div class="py-6">
        <div class="mx-auto max-w-7xl">
            <div>
                <livewire:admin.qr-attendance-overview />
            </div>


        </div>
    </div>
</x-app-layout>
