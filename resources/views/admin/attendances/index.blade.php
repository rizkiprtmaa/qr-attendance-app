<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-inter text-2xl font-medium leading-tight text-slate-900">
                    {{ __('Presensi QR') }}
                </h2>

            </div>

        </div>
    </x-slot>





    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div>
                <livewire:admin.qr-attendance-overview />
            </div>


        </div>
    </div>
</x-app-layout>
