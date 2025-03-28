<?php
// navigation.blade.php (Livewire Component)
use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div x-data="{ open: false }"
    class="fixed inset-y-0 left-0 z-40 transform font-inter text-white transition duration-300 ease-in-out md:translate-x-0">
    <!-- Sidebar -->
    <div x-cloak :class="{ '-translate-x-full': !open, 'translate-x-0': open }"
        class="fixed inset-y-0 left-0 z-40 min-h-screen w-64 transform rounded-r-3xl bg-white text-slate-900 transition duration-300 ease-in-out md:static md:block md:translate-x-0">
        <div class="flex items-center justify-between p-4">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center justify-end">
                <span class="ml-4 mt-2 text-lg font-medium">SMK Nurussalam</span>
            </a>
            <button @click="open = !open" x-show="open"
                class="z-50 ml-2 mt-2 rounded-full bg-white p-2 text-gray-900 focus:outline-none md:hidden">
                <svg height="512" class="h-4 w-4" viewBox="0 0 24 24" width="512"
                    xmlns="http://www.w3.org/2000/svg" id="fi_9777564">
                    <g id="_01" data-name="01">
                        <path
                            d="m15 1.25h-6c-.3 0-.594.01-.879.024a.81.81 0 0 0 -.121-.024.732.732 0 0 0 -.261.053c-4.526.352-6.489 2.708-6.489 7.697v6c0 4.989 1.963 7.345 6.489 7.7a.732.732 0 0 0 .261.05.81.81 0 0 0 .121-.024c.285.014.575.024.879.024h6c5.432 0 7.75-2.317 7.75-7.75v-6c0-5.433-2.318-7.75-7.75-7.75zm-7.75 19.9c-3.282-.414-4.5-2.175-4.5-6.148v-6.002c0-3.973 1.218-5.734 4.5-6.148zm14-6.15c0 4.614-1.636 6.25-6.25 6.25h-6c-.087 0-.165 0-.25-.006v-18.488c.085 0 .163-.006.25-.006h6c4.614 0 6.25 1.636 6.25 6.25zm-5.72-5.029-2.03 2.029 2.029 2.029a.75.75 0 1 1 -1.06 1.061l-2.56-2.56a.749.749 0 0 1 0-1.06l2.56-2.56a.75.75 0 0 1 1.06 1.061z">
                        </path>
                    </g>
                </svg>
            </button>

        </div>

        <nav class="mt-4">
            <div class="px-4">
                <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="mr-3 h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                    </svg>

                    {{ __('Dashboard') }}
                </x-sidebar-link>

                @role('admin')
                    <x-sidebar-link :href="route('users')" :active="request()->routeIs(['users', 'teachers', 'create.user', 'user.detail'])" wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="mr-3 h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>

                        {{ __('Users') }}
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('classes')" :active="request()->routeIs(['classes', 'classes.detail'])" wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="mr-3 h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>

                        {{ __('Classes') }}
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('qr.attendances')" :active="request()->routeIs(['qr.attendances', 'attendances.detail'])" wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="mr-3 h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />
                        </svg>


                        {{ __('QR Attendance') }}
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('permission-management')" :active="request()->routeIs('permission-management')" wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="mr-3 h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>


                        </svg>
                        {{ __('Manajemen Izin') }}
                    </x-sidebar-link>

                    <x-sidebar-link :href="route('settings')" :active="request()->routeIs('settings')" wire:navigate>
                        <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ __('Settings') }}
                    </x-sidebar-link>
                @endrole
                @role('teacher')
                    <x-sidebar-link :href="route('classes.attendances')" :active="request()->routeIs(['classes.attendances', 'subject.detail', 'session.attendance'])" wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="mr-3 h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 6.878V6a2.25 2.25 0 0 1 2.25-2.25h7.5A2.25 2.25 0 0 1 18 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 0 0 4.5 9v.878m13.5-3A2.25 2.25 0 0 1 19.5 9v.878m0 0a2.246 2.246 0 0 0-.75-.128H5.25c-.263 0-.515.045-.75.128m15 0A2.25 2.25 0 0 1 21 12v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6c0-.98.626-1.813 1.5-2.122" />
                        </svg>

                        </svg>
                        {{ __('Presensi Kelas') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('permission-submission')" :active="request()->routeIs('permission-submission')" wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="mr-3 h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>


                        </svg>
                        {{ __('Pengajuan Izin') }}
                    </x-sidebar-link>
                @endrole
                @role('student')
                    <x-sidebar-link :href="route('permission-submission')" :active="request()->routeIs('permission-submission')" wire:navigate>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="mr-3 h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>


                        </svg>
                        {{ __('Pengajuan Izin') }}
                    </x-sidebar-link>
                @endrole
                <x-sidebar-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>
                    <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ __('Profile') }}
                </x-sidebar-link>
            </div>
        </nav>

        <!-- User Profile Section -->
        <div class="absolute bottom-0 w-full p-4">
            <div class="flex items-center rounded-full bg-gray-300 px-4 py-3">
                <img src="{{ auth()->user()->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}"
                    alt="{{ auth()->user()->name }}" class="mr-3 h-10 w-10 rounded-full">
                <div>
                    <p class="text-inter text-sm font-medium">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400">{{ auth()->user()->email }}</p>
                </div>
                <button wire:click="logout" class="ml-auto text-slate-900 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-liejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Toggle -->
    <button @click="open = !open" x-show="!open" x-transition.origin.top.left.duration.200ms
        class="inset-y-0 z-50 ml-5 mt-5 rounded-full bg-white p-2 text-gray-900 focus:outline-none md:hidden">
        <svg height="512" class="h-4 w-4" viewBox="0 0 24 24" width="512" xmlns="http://www.w3.org/2000/svg"
            id="fi_9777566">
            <g id="_01" data-name="01">
                <path
                    d="m15 1.25h-6c-5.433 0-7.75 2.317-7.75 7.75v6c0 5.433 2.317 7.75 7.75 7.75h6c5.433 0 7.75-2.317 7.75-7.75v-6c0-5.433-2.317-7.75-7.75-7.75zm-.75 20h-5.25c-4.614 0-6.25-1.636-6.25-6.25v-6c0-4.614 1.636-6.25 6.25-6.25h5.25zm7-6.25c0 4.354-1.459 6.054-5.5 6.232v-18.464c4.041.178 5.5 1.878 5.5 6.232zm-12.72-6.09 2.56 2.56a.749.749 0 0 1 0 1.06l-2.56 2.56a.75.75 0 0 1 -1.06-1.061l2.03-2.029-2.03-2.029a.75.75 0 0 1 1.06-1.061z">
                </path>
            </g>
        </svg>
    </button>



    <!-- Main Content Area -->
    <div class="flex-1 bg-gray-100 transition-all duration-300 ease-in-out md:ml-64">
        @yield('content')
    </div>
</div>
