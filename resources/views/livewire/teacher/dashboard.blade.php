<?php

use Livewire\Volt\Component;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\SubjectClass;
use App\Models\SubjectClassSession;
use App\Models\Teacher;

new class extends Component {
    public $attendances;
    public $subjectClass;
    public $subjectClassSessions;
    public $teacherId;

    public function mount()
    {
        $this->teacherId = auth()->user()->id;
        $this->teacher = Teacher::where('id', auth()->user()->id)->get();
        $this->attendances = Attendance::where('user_id', auth()->user()->id)->get();
        $this->subjectClass = SubjectClass::where('teacher_id', auth()->user()->id)->get();
        $this->subjectClassSessions = SubjectClassSession::whereIn('subject_class_id', $this->subjectClass->pluck('id'))->get();
    }

    public function render(): mixed
    {
        // Calculate total hours based on sessions
        $totalHours = 0;
        $sessionsQuery = SubjectClassSession::whereIn('subject_class_id', $this->subjectClass->pluck('id'));

        foreach ($sessionsQuery->get() as $session) {
            $start = \Carbon\Carbon::parse($session->start_time);
            $end = \Carbon\Carbon::parse($session->end_time);
            $totalHours += $start->diffInHours($end); // Pastikan urutan parameter benar
        }

        $totalHours = number_format($totalHours, 2, '.', '');

        $totalSubjectClassSessions = $this->subjectClassSessions->count();
        return view('livewire.teacher.dashboard', [
            'attendances' => $this->attendances,
            'totalAttendances' => $this->attendances->count(),
            'totalSubjectClasses' => $this->subjectClass->count(),
            'totalSubjectClassSessions' => $totalSubjectClassSessions,
            'teacher' => $this->teacher,
            'totalHours' => $totalHours,
            'subjectClassSessions' => $this->subjectClassSessions,
        ]);
    }
}; ?>

<div x-data="{
    currentQrCode: null,
    currentUserId: null,
    showQrModal: false,
    openQrModal(qrCodePath, userId) {
        this.currentQrCode = qrCodePath;
        this.currentUserId = userId;
        this.showQrModal = true;
    },
    activeTab: 'overview'
}">
    <!-- Header Section -->
    <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
        <div>
            @php
                $hour = \Carbon\Carbon::now('Asia/Jakarta')->hour;
                if ($hour >= 5 && $hour < 12) {
                    $greeting = 'Selamat Pagi';
                } elseif ($hour >= 12 && $hour < 18) {
                    $greeting = 'Selamat Siang';
                } else {
                    $greeting = 'Selamat Malam';
                }
            @endphp

            <p class="mt-1 font-inter text-base font-medium text-gray-600">✨ {{ $greeting }},
                {{ auth()->user()->name }}</p>
        </div>
        <div class="flex flex-col space-x-4">
            <p class="font-inter font-medium text-gray-600">{{ \Carbon\Carbon::now('Asia/Jakarta')->format('d F Y') }}
            </p>
            <button @click="openQrModal('{{ auth()->user()->qr_code_path }}', '{{ auth()->user()->id }}')"
                class="rounded-md bg-blue-500 px-4 py-2 font-inter text-sm text-white shadow-md transition hover:bg-blue-600 hover:shadow-lg">
                Tampilkan QR Presensi
            </button>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="mt-6 border-b border-gray-200">
        <ul class="-mb-px flex flex-wrap text-center text-sm font-medium">
            <li class="mr-2">
                <a href="#" @click.prevent="activeTab = 'overview'"
                    :class="{ 'text-blue-600 border-b-2 border-blue-600': activeTab === 'overview', 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300': activeTab !== 'overview' }"
                    class="inline-block p-4">
                    Overview
                </a>
            </li>
            <li class="mr-2">
                <a href="#" @click.prevent="activeTab = 'classes'"
                    :class="{ 'text-blue-600 border-b-2 border-blue-600': activeTab === 'classes', 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300': activeTab !== 'classes' }"
                    class="inline-block p-4">
                    Kelas Saya
                </a>
            </li>
            <li class="mr-2">
                <a href="#" @click.prevent="activeTab = 'analytics'"
                    :class="{ 'text-blue-600 border-b-2 border-blue-600': activeTab === 'analytics', 'text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300': activeTab !== 'analytics' }"
                    class="inline-block p-4">
                    Analitik
                </a>
            </li>
        </ul>
    </div>

    <!-- Overview Tab Content -->
    <div x-show="activeTab === 'overview'" class="mt-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                class="flex flex-col gap-4 rounded-lg border border-slate-400/30 bg-white p-4 shadow-sm transition duration-300 hover:shadow-md">
                <div>
                    <p class="font-inter text-sm text-slate-500">Total Kehadiran</p>
                </div>
                <div class="flex flex-row items-center justify-between">
                    <p class="font-inter text-2xl font-medium">{{ $totalAttendances }}</p>
                    <div class="rounded-full bg-blue-100 p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-6 text-blue-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Z" />
                        </svg>
                    </div>
                </div>
            </div>
            <div
                class="flex flex-col gap-4 rounded-lg border border-slate-400/30 bg-white p-4 shadow-sm transition duration-300 hover:shadow-md">
                <div>
                    <p class="font-inter text-sm text-slate-500">Mata Pelajaran</p>
                </div>
                <div class="flex flex-row items-center justify-between">
                    <p class="font-inter text-2xl font-medium">{{ $totalSubjectClasses }}</p>
                    <div class="rounded-full bg-green-100 p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-6 text-green-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                </div>
            </div>
            <div
                class="flex flex-col gap-4 rounded-lg border border-slate-400/30 bg-white p-4 shadow-sm transition duration-300 hover:shadow-md">
                <div>
                    <p class="font-inter text-sm text-slate-500">Jumlah Pertemuan</p>
                </div>
                <div class="flex flex-row items-center justify-between">
                    <p class="font-inter text-2xl font-medium">{{ $totalSubjectClassSessions }}</p>
                    <div class="rounded-full bg-purple-100 p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-6 text-purple-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            <div
                class="flex flex-col gap-4 rounded-lg border border-slate-400/30 bg-white p-4 shadow-sm transition duration-300 hover:shadow-md">
                <div>
                    <p class="font-inter text-sm text-slate-500">Total Jam</p>
                </div>
                <div class="flex flex-row items-center justify-between">
                    <p class="font-inter text-2xl font-medium">{{ $totalHours }}</p>
                    <div class="rounded-full bg-amber-100 p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="size-6 text-amber-600">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jadwal Hari Ini -->
        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-md font-inter font-semibold text-gray-800">Jadwal Hari Ini</h2>
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                        {{ \Carbon\Carbon::now('Asia/Jakarta')->format('l') }}
                    </span>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    @php
                        $hasClassesToday = false;
                        $today = \Carbon\Carbon::now('Asia/Jakarta')->toDateString();
                        foreach ($subjectClassSessions as $session) {
                            if (\Carbon\Carbon::parse($session->class_date)->toDateString() === $today) {
                                $hasClassesToday = true;
                                break;
                            }
                        }
                    @endphp

                    @if ($hasClassesToday)
                        <ul class="divide-y divide-gray-200">
                            @foreach ($subjectClassSessions as $session)
                                @if (\Carbon\Carbon::parse($session->class_date)->toDateString() === $today)
                                    <li class="flex items-center justify-between p-4 hover:bg-gray-50">
                                        <div class="flex items-center space-x-4">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="h-5 w-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="font-medium text-gray-900">{{ $session->subject_title }}
                                                </h3>
                                                <p class="text-sm text-gray-500">
                                                    {{ $session->subjectClass->class_name }} -
                                                    {{ $session->subjectClass->classes->name }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}</p>
                                            <a href="{{ route('session.attendance', $session) }}"
                                                class="mt-1 inline-block font-inter text-xs text-blue-600 hover:underline">Kelola
                                                Presensi</a>
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @else
                        <div class="flex flex-col items-center justify-center p-8">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-12 w-12 text-gray-300">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada jadwal hari ini</h3>
                            <p class="mt-1 text-sm text-gray-500">Anda tidak memiliki kelas yang dijadwalkan untuk hari
                                ini.</p>
                        </div>
                    @endif
                </div>
                @if ($hasClassesToday)
                    <div class="mt-2 flex flex-row justify-center">
                        <a href="{{ route('classes.attendances') }}"
                            class="mt-4 rounded-full bg-gray-400 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-gray-300">
                            Tambah Pertemuan +
                        </a>
                    </div>
                @endif
            </div>

            <!-- Quick Links and Notes -->
            <div class="space-y-6">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="text-md mb-4 font-inter font-semibold text-gray-800">Aksi Cepat</h2>
                    <div class="space-y-3">
                        <a href="{{ route('classes.attendances') }}"
                            class="flex items-center space-x-3 rounded-md bg-blue-50 p-3 text-blue-700 transition hover:bg-blue-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Buat Sesi Baru</span>
                        </a>
                        <a href="#"
                            class="flex items-center space-x-3 rounded-md bg-purple-50 p-3 text-purple-700 transition hover:bg-purple-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            <span>Lihat Daftar Siswa</span>
                        </a>
                        <a href="#"
                            class="flex items-center space-x-3 rounded-md bg-green-50 p-3 text-green-700 transition hover:bg-green-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span>Unduh Laporan</span>
                        </a>
                    </div>
                </div>

                <!-- Reminder Section -->
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800">Pengingat</h2>

                    @if (count($subjectClassSessions) > 0)
                        <ul class="space-y-3">
                            <li class="flex items-start space-x-3 rounded-md bg-yellow-50 p-3 text-yellow-800">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="mt-0.5 h-5 w-5 flex-shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                                <span class="text-sm">Perbarui presensi untuk pertemuan terakhir</span>
                            </li>
                            <li class="flex items-start space-x-3 rounded-md bg-blue-50 p-3 text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="mt-0.5 h-5 w-5 flex-shrink-0">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                                <span class="text-sm">Jadwalkan pertemuan untuk minggu depan</span>
                            </li>
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">Tidak ada pengingat saat ini.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Classes -->
        <div class="mt-8">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="flex items-center space-x-2 text-lg font-semibold text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    <span>Pertemuan Terbaru</span>
                </h2>
                <a href="{{ route('classes.attendances') }}"
                    class="text-sm font-medium text-blue-600 hover:underline">
                    Kelola Kehadiran
                </a>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($subjectClassSessions->sortByDesc('created_at')->take(6) as $subject)
                    <div
                        class="rounded-lg border border-slate-200 bg-white shadow-sm transition duration-300 hover:shadow-md">
                        <div class="flex flex-col gap-1 p-4">
                            <div class="flex items-center justify-between">
                                <p class="font-inter text-base font-medium text-slate-800">
                                    {{ $subject->subjectClass->class_name }}
                                </p>
                                <span class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($subject->created_at)->format('d M Y') }}
                                </span>
                            </div>
                            <p class="font-inter text-sm text-slate-700">{{ $subject->subject_title }}</p>
                            <div class="mt-1 flex flex-wrap gap-2">
                                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                    {{ $subject->subjectClass->classes->major->name }}
                                </span>
                                <span
                                    class="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">
                                    {{ $subject->subjectClass->classes->name }}
                                </span>
                                <span
                                    class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                    {{ \Carbon\Carbon::parse($subject->start_time)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($subject->end_time)->format('H:i') }}
                                </span>
                            </div>
                        </div>
                        <div class="border-t border-slate-200"></div>
                        <div class="flex items-center justify-between p-4">
                            <p class="text-xs font-medium text-slate-500">
                                {{ $subject->created_at->diffForHumans(['locale' => 'id']) }}
                            </p>
                            <a href="{{ route('session.attendance', $subject) }}"
                                class="rounded-md bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                Lihat Kehadiran
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3">
                        <div
                            class="flex flex-col items-center justify-center rounded-lg border border-gray-200 bg-white p-8">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-12 w-12 text-gray-300">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                            </svg>
                            <h3 class="mt-2 text-base font-medium text-gray-900">Belum ada pertemuan</h3>
                            <p class="mt-1 text-sm text-gray-500">Anda belum membuat pertemuan apapun.</p>
                            <a href="{{ route('classes.attendances') }}"
                                class="mt-4 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                Buat Pertemuan
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Classes Tab Content -->
    <div x-show="activeTab === 'classes'" class="mt-6" x-cloak>
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">Daftar Mata Pelajaran</h2>
            <a href="{{ route('classes.attendances') }}"
                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Buat Kelas Baru
            </a>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Mata Pelajaran
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Kelas
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Jurusan
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Jumlah Pertemuan
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Total Jam
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($subjectClass as $class)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0">
                                            <div
                                                class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                    class="h-5 w-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $class->class_name }}
                                            </div>
                                            <div class="text-sm text-gray-500">{{ $class->class_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $class->classes->name }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $class->classes->major->name }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        {{ $subjectClassSessions->where('subject_class_id', $class->id)->count() }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        @php
                                            $totalClassHours = 0;
                                            foreach (
                                                $subjectClassSessions->where('subject_class_id', $class->id)
                                                as $session
                                            ) {
                                                $start = \Carbon\Carbon::parse($session->start_time);
                                                $end = \Carbon\Carbon::parse($session->end_time);
                                                $totalClassHours += $start->diffInHours($end);
                                            }
                                        @endphp
                                        {{ number_format($totalClassHours, 2) }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <a href="{{ route('subject.detail', $class) }}"
                                        class="text-blue-600 hover:text-blue-900">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="1.5" stroke="currentColor" class="h-10 w-10 text-gray-300">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data</h3>
                                        <p class="mt-1 text-sm text-gray-500">Anda belum memiliki kelas mata pelajaran.
                                        </p>
                                        <a href="{{ route('classes.attendances') }}"
                                            class="mt-3 rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                            Buat Kelas
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Analytics Tab Content -->
    <div x-show="activeTab === 'analytics'" class="mt-6" x-cloak>
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Statistik Kehadiran</h2>

            <!-- Overview Statistics Cards -->
            <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="flex items-start">
                        <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-green-600">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Tingkat Kehadiran</p>
                            <p class="text-2xl font-bold text-gray-900">85%</p>
                            <p class="mt-1 text-xs text-green-600">↑ 2% dari bulan lalu</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="flex items-start">
                        <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-yellow-600">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Izin & Sakit</p>
                            <p class="text-2xl font-bold text-gray-900">10%</p>
                            <p class="mt-1 text-xs text-gray-500">± 0% dari bulan lalu</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="flex items-start">
                        <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-red-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Tanpa Keterangan</p>
                            <p class="text-2xl font-bold text-gray-900">5%</p>
                            <p class="mt-1 text-xs text-red-600">↓ 2% dari bulan lalu</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="flex items-start">
                        <div class="mr-4 flex h-12 w-12 items-center justify-center rounded-full bg-purple-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-purple-600">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Siswa yang Perlu Perhatian</p>
                            <p class="text-2xl font-bold text-gray-900">3</p>
                            <p class="mt-1 text-xs text-gray-500">Kehadiran < 70%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Chart placeholder -->
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <div class="flex h-64 flex-col items-center justify-center">
                    <canvas id="attendance-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Class Comparison -->
        <div class="mt-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">Perbandingan Kehadiran Antar Kelas</h2>

            <div class="overflow-hidden rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Kelas</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Tingkat Kehadiran</th>
                            <th scope="col"
                                class="hidden px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 md:table-cell">
                                Izin/Sakit</th>
                            <th scope="col"
                                class="hidden px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 md:table-cell">
                                Tanpa Keterangan</th>
                            <th scope="col"
                                class="hidden px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 md:table-cell">
                                Tren</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($subjectClass->take(5) as $class)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $class->classes->name }} - {{ $class->class_name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-full max-w-xs">
                                            <div class="h-2.5 w-full rounded-full bg-gray-200">
                                                @php $percentage = rand(75, 95); @endphp
                                                <div class="h-2.5 rounded-full bg-green-600"
                                                    style="width: {{ $percentage }}%"></div>
                                            </div>
                                        </div>
                                        <span class="ml-3 text-sm text-gray-900">{{ $percentage }}%</span>
                                    </div>
                                </td>
                                <td class="hidden whitespace-nowrap px-6 py-4 text-sm text-gray-500 sm:table-cell">
                                    @php $izinSakit = $class->izin_sakit_percentage ?? 0; @endphp
                                    {{ $izinSakit }}%
                                </td>
                                <td class="hidden whitespace-nowrap px-6 py-4 text-sm text-gray-500 sm:table-cell">
                                    @php $tanpaKet = $class->tanpa_keterangan_percentage ?? 0; @endphp
                                    {{ $tanpaKet }}%
                                </td>
                                <td class="hidden whitespace-nowrap px-6 py-4 text-sm sm:table-cell">
                                    @php $trend = rand(-3, 3); @endphp
                                    @if ($trend > 0)
                                        <span class="inline-flex items-center text-green-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="mr-1 h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                                            </svg>
                                            +{{ $trend }}%
                                        </span>
                                    @elseif ($trend < 0)
                                        <span class="inline-flex items-center text-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="mr-1 h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l5.94-2.28m-5.94 2.28l-2.28 5.941" />
                                            </svg>
                                            {{ $trend }}%
                                        </span>
                                    @else
                                        <span class="inline-flex items-center text-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                                class="mr-1 h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M18.75 7.5h-7.5A2.25 2.25 0 009 9.75v7.5A2.25 2.25 0 0011.25 19.5h7.5A2.25 2.25 0 0021 17.25v-7.5A2.25 2.25 0 0018.75 7.5z" />
                                            </svg>
                                            0%
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- QR Modal -->
    <div x-show="showQrModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        x-cloak @keydown.escape.window="showQrModal = false">
        <div class="w-full max-w-md rounded-lg bg-white p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-inter text-xl font-medium">QR Code Presensi</h2>
                <button @click="showQrModal = false" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-4 flex justify-center">
                <template x-if="currentQrCode">
                    <img :src="'/storage/' + currentQrCode" alt="QR Code"
                        class="h-auto max-w-full rounded-lg border border-gray-200 p-2">
                </template>
                <template x-if="!currentQrCode">
                    <div
                        class="flex h-64 w-64 flex-col items-center justify-center rounded-lg border border-gray-200 bg-gray-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" class="h-12 w-12 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                        <p class="mt-2 text-center text-sm text-gray-500">QR Code tidak tersedia</p>
                    </div>
                </template>
            </div>

            <div class="mt-3 flex justify-center space-x-4">
                <a x-show="currentUserId" :href="`/users/${currentUserId}/download-qr`"
                    class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    Download QR
                </a>
                <button @click="showQrModal = false"
                    class="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-300">
                    Tutup
                </button>
            </div>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('attendance-chart').getContext('2d');
            const labels = ['January', 'February', 'March', 'April', 'May', 'June', 'July'];
            const data = {
                labels: labels,
                datasets: [{
                    label: 'Attendance Rate',
                    data: [65, 59, 80, 81, 56, 55, 40],
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            };

            const config = {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        title: {
                            display: false,

                        }
                    }
                }
            };

            new Chart(ctx, config);
        });
    </script>
</div>
