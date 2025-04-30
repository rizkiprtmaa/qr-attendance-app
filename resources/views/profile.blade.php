<x-app-layout>
    <x-slot name="header">
        <h2 class="font-inter text-3xl font-semibold leading-tight text-gray-800">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="mt-12 py-6 md:mt-0">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="rounded-lg bg-white p-4 shadow sm:p-8">

                <div class="max-w-xl">
                    <livewire:profile.update-profile-photo-form />
                </div>
            </div>
            @role(['admin', 'teacher'])
                <div class="rounded-lg bg-white p-4 shadow sm:p-8">
                    <div class="max-w-xl">
                        <livewire:profile.update-profile-information-form />
                    </div>
                </div>
            @endrole
            <div class="rounded-lg bg-white p-4 shadow sm:p-8">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>


        </div>
    </div>
</x-app-layout>
