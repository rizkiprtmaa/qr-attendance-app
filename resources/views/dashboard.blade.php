<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-row justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Dashboard') }}
            </h2>
            <x-primary-button href="{{ route('attendance.scan') }}" color="blue" class="text-white" wire:navigate><svg
                    height="512" viewBox="0 0 32 32" width="512" xmlns="http://www.w3.org/2000/svg" id="fi_6927609"
                    class="size-6" fill="currentColor">
                    <g id="Layer_2" data-name="Layer 2">
                        <path
                            d="m3 11a1 1 0 0 0 1-1v-5a1 1 0 0 1 1-1h5.5a1 1 0 0 0 0-2h-5.5a3 3 0 0 0 -3 3v5a1 1 0 0 0 1 1z">
                        </path>
                        <path d="m27 2h-5.5a1 1 0 0 0 0 2h5.5a1 1 0 0 1 1 1v5a1 1 0 0 0 2 0v-5a3 3 0 0 0 -3-3z"></path>
                        <path
                            d="m29 21a1 1 0 0 0 -1 1v5a1 1 0 0 1 -1 1h-5.5a1 1 0 0 0 0 2h5.5a3 3 0 0 0 3-3v-5a1 1 0 0 0 -1-1z">
                        </path>
                        <path d="m10.5 28h-5.5a1 1 0 0 1 -1-1v-5a1 1 0 0 0 -2 0v5a3 3 0 0 0 3 3h5.5a1 1 0 0 0 0-2z">
                        </path>
                        <path d="m29 15h-26a1 1 0 0 0 0 2h26a1 1 0 0 0 0-2z"></path>
                    </g>
                </svg></x-primary-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
