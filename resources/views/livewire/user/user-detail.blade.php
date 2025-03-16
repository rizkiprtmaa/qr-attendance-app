<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;
    public $user;

    public function mount(User $user)
    {
        $this->user = $user;
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->nisn = $user->student->nisn;
        $this->phone = $user->student->parent_number;
        $this->class = $user->student->classes->name;
        $this->major = $user->student->classes->major->name;
    }

    public function render(): mixed
    {
        $this->attendances = Attendance::where('user_id', $this->userId)->orderBy('attendance_date', 'desc')->paginate(8);
        return view('livewire.user.user-detail', [
            'attendances' => $this->attendances,
        ]);
    }
}; ?>

<div class="m-6 mx-auto w-full flex-col items-center justify-center p-6" x-data="{
    nisnContent: '{{ $user->student->nisn }}',
    copy() {
        $clipboard(this.nisnContent);
    },
    expand: false
}">
    <div class="mb-7">
        <p class="font-inter text-2xl font-medium dark:text-slate-900">Detail Siswa</p>
    </div>
    <div class="w-full rounded-md border border-solid border-slate-400/30 bg-white p-6 shadow-sm">

        <div class="mb-4 flex flex-col items-center justify-between md:flex-row">
            <div class="mb-4 flex flex-row items-center md:mb-0">
                <span class="text-blue-600">
                    <svg fill="currentColor" xmlns="http://www.w3.org/2000/svg" width="10666.667" height="10666.667"
                        version="1.0" viewBox="0 0 8000 8000" class="h-10 w-10">
                        <path
                            d="M3920.5 946.6c-71.4 2.5-101 4-147.5 7.4-669.3 48.9-1301.8 317.1-1805 765.6-335.8 299.4-601.2 668.2-778 1081.4-138.5 323.8-219.7 671.5-239.5 1026-3.5 63.7-4 84.8-4 173s.5 109.3 4 173c29 518.5 188.4 1017.4 465.3 1456 210.6 333.4 485.3 624.6 806.7 855.1 343.3 246.2 733.4 418.1 1145.5 504.8 223 46.9 456.4 69.1 683 65.1 74.1-1.4 125.9-3.8 193-9.1 436.8-34.3 858-161.6 1241.3-375.1 590.2-328.6 1057.6-847 1323.7-1467.8 81.7-190.6 143.6-389 184.5-590.9 5.7-28.1 13.9-72.1 15-80.1.4-2.5 1.7-10.8 3.1-18.5 17.7-103.1 31.6-227.2 37.9-339.5 3.5-63.7 4-84.8 4-173s-.5-109.3-4-173c-30.1-539.6-201.2-1056.7-498.9-1508-270.8-410.4-641.2-753.5-1070.1-991.2-371.6-206-775.4-330.3-1199-369.3-48-4.4-96.3-7.6-160-10.6-21.3-1-180.6-2-201-1.3zm275.5 988.5c25.7 1.2 44.8 3.1 65.3 6.5 108.1 18.2 214.5 72.5 316.3 161.4 32.4 28.3 76 71.6 117.5 116.5 55.2 59.9 90.3 113.2 108 164.5 10.8 31 15.3 57 21.9 126.5 5.3 55.3 8.6 74.1 24 137.5 15.7 64.7 18.3 78.5 18.3 99.5 0 22.9-3.7 44.9-11.4 67.3-7.1 20.6-19.2 43.5-28.9 54.6-2.9 3.2-3.1 4-6 27.7-4.3 35.6-15.2 137.4-24.4 227.9-2 19-3.8 36.5-4.2 39l-.6 4.5 3-4.1c5.9-8.1 14.3-12.7 26.1-14.4 29.5-4.2 54.7 17 64.6 54.2 9.6 36.2 8.3 84-4.1 147.3-5.9 30-52.2 250.9-54.4 259.6-4.2 16.2-10.3 29.5-19.5 42.4-13.4 18.6-24.1 27-39.8 31.2-8.9 2.4-26.5 2.1-39.2-.5-5.4-1.2-10-2-10.1-1.9-.3.3-6.1 30.9-17.4 92.2-21.6 116.6-37.7 171.4-64.2 219.1-16.6 30-28.2 44.7-63.8 80.9-29.2 29.7-45.7 46.9-98.7 102.7l-27.3 28.7v6c0 8.1 3.5 58.7 6.6 96.1 6.2 75.2 14.1 139.8 21.4 176 4.5 22.4 4.8 23.1 9.5 24.6 2.2.7 13.9 4.2 26 7.8 101.9 30.2 235.4 80.7 351.5 132.9 116.7 52.5 241.8 118.7 334.7 177.1 51.2 32.2 95.1 73 133.5 124.1 84.5 112.1 143.4 276.9 171.7 479.5 6.7 48.3 8.3 63.8 8.8 89.3.8 35.9-1.9 62.5-9.7 94.7-25.7 105.9-98.1 195-196.6 242.1-23.4 11.3-86.6 35.3-136.4 51.9-257.1 85.9-574.6 137.7-937.5 153-147.1 6.2-313.9 6.2-461 0-411.4-17.3-764-81.6-1035.5-188.6-29.5-11.7-50.3-21.5-69-32.7-63.7-38.1-114.8-95.7-145-163.7-23-51.8-34.1-111.3-31-166.1 2.8-48.3 16.6-138.8 31.6-205.8 41-184.3 108.5-322.3 202.6-414.6 32.4-31.9 53.2-47.3 107.8-80.2 132.8-79.9 276.4-150.9 430-212.3 78.6-31.4 166.2-62.3 231-81.5 12.4-3.6 23.6-7 24.9-7.4 3-.9 4-4.1 8.6-27.6 9.4-48.4 18.5-133.2 25.5-235.8 2.9-42.9 4.2-36.4-10.3-51.4-6.7-7.1-28.7-30.1-48.8-51.3-20.1-21.2-49.9-52-66.1-68.6-38.8-39.6-51-55.9-68.8-91.7-23.9-48.1-39.2-102.5-58.5-209.2-7.8-42.7-16.6-89.9-16.9-90.2-.2-.2-3.2.3-6.7 1.1-18.6 4.3-36 4.1-48.7-.6-7.9-2.9-18.3-9.9-23.7-16.1-6.9-7.8-19-26.3-22.4-34.3-5.7-13.3-9.1-28.3-32.7-140.9-39.6-189.8-38.7-184.7-38.8-229.5-.1-39.1 2.9-56.1 13.6-78 4.4-8.8 6.5-11.8 13.3-18.6 9-9.1 15.1-12.6 26-14.9 8.9-1.9 13.4-1.9 21.4 0 8.6 2.1 15.4 5.9 20.5 11.6 3.9 4.3 4.2 4.5 3.9 2-.2-1.4-3.7-35.5-7.9-75.6-10.8-105.4-19.5-184.9-23-211-.5-3.7-1.6-6-4.1-8.8-7.9-8.9-19.1-29-25.8-46.7-9.8-25.7-15.5-58.9-14.1-82.1 1-17.1 4.9-36.2 19.5-96.4 14.4-59 17.6-77.7 23.1-135 4.7-49.2 6.8-64.9 11.6-87.9 12-56.9 40.4-110 91.3-170.5 35.4-42.2 101.7-109.7 144.5-147.1 30.7-26.8 57-47 87.7-67.3 25.8-17.1 43.1-27.1 68.9-40.1 75.2-38 144.6-56.9 221.1-60.4 24.8-1.2 370.9-1.2 395.9-.1z" />
                    </svg>
                </span>

                <p class="ms-2 font-inter text-xl font-medium dark:text-slate-900">{{ $user->name }}</p>
            </div>
            <flux:separator class="block md:hidden" />
            <div class="mt-4 md:mt-0">
                <button
                    class="text-on-surface rounded-md border border-solid border-slate-400 px-4 py-2 font-inter text-sm text-slate-900">Unduh
                    QR</button>
                <button class="text-on-surface rounded-md bg-slate-900 px-4 py-2 font-inter text-sm text-white">Unduh
                    Laporan</button>
            </div>

        </div>
        <flux:separator class="hidden dark:text-slate-900 md:block" />
        <div class="mt-4 flex flex-col items-center gap-5 md:flex-row">
            <div class="flex flex-col gap-2 text-center md:me-4 md:text-start">
                <p class="font-inter text-sm text-slate-500">Nomor Induk Siswa Nasional (NISN)</p>
                <p
                    class="flex flex-row items-center justify-center gap-2 font-inter font-medium dark:text-slate-900 md:justify-start">
                    {{ $user->student->nisn }} <button x-on:click="copy"><svg xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                            class="size-4 text-blue-500">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                        </svg>
                    </button>
                </p>

            </div>
            <flux:separator class="hidden dark:bg-slate-900 md:block" vertical />
            <div class="hidden flex-col gap-2 text-center md:mx-4 md:flex md:text-start">
                <p class="font-inter text-sm text-slate-500">Email</p>
                <p class="font-inter font-medium text-blue-600">{{ $user->email }}</p>
            </div>
            <flux:separator class="hidden md:block" vertical />
            <div class="hidden flex-col gap-2 text-center md:mx-4 md:flex md:text-start">
                <p class="font-inter text-sm text-slate-500">Nomor Orangtua</p>
                <p class="font-inter font-medium text-blue-600">{{ $user->student->parent_number }}</p>
            </div>
            <flux:separator class="hidden md:block" vertical />
            <div class="hidden flex-col gap-2 text-center md:mx-4 md:flex md:text-start">
                <p class="font-inter text-sm text-slate-500">Jurusan</p>
                <p class="font-inter font-medium text-blue-600">{{ $user->student->classes->major->name }}</p>
            </div>
            <flux:separator class="hidden md:block" vertical />
            <div class="hidden flex-col gap-2 text-center md:mx-4 md:flex md:text-start">
                <p class="font-inter text-sm text-slate-500">Kelas</p>
                <p class="font-inter font-medium text-blue-600">{{ $user->student->classes->name }}</p>
            </div>

        </div>
    </div>
    <div x-cloak x-show="expand" x-transition.fade class="grid grid-cols-2 gap-2 md:hidden">
        <div class="mt-2 rounded-md border border-solid border-slate-400/30 bg-white p-4 shadow-sm">
            <p class="font-inter text-sm text-slate-500">Nomor Orangtua</p>
            <p class="font-inter font-medium text-blue-600">{{ $user->student->parent_number }}</p>
        </div>
        <div class="mt-2 rounded-md border border-solid border-slate-400/30 bg-white p-4 shadow-sm">
            <p class="font-inter text-sm text-slate-500">Email</p>
            <p class="font-inter font-medium text-blue-600">{{ $user->email }}</p>
        </div>
        <div class="mt-2 rounded-md border border-solid border-slate-400/30 bg-white p-4 shadow-sm">
            <p class="font-inter text-sm text-slate-500">Jurusan</p>
            <p class="font-inter font-medium text-blue-600">{{ $user->student->classes->major->name }}</p>
        </div>
        <div class="mt-2 rounded-md border border-solid border-slate-400/30 bg-white p-4 shadow-sm">
            <p class="font-inter text-sm text-slate-500">Kelas</p>
            <p class="font-inter font-medium text-blue-600">{{ $user->student->classes->name }}</p>
        </div>
    </div>
    <div class="mt-4 flex flex-row justify-center md:hidden" x-show="!expand">
        <flux:button class="animate-bounce" icon="arrow-down" x-on:click="expand = true">
            Info Lanjutan
        </flux:button>
    </div>
    <div class="mt-4 flex flex-row justify-center md:hidden" x-show="expand">
        <flux:button class="animate-bounce" icon="arrow-up" x-on:click="expand = false">Tutup Info</flux:button>
    </div>


    @php
        $total_attendances = $attendances->where('type', 'datang')->count();
        $latest_attendance = $attendances->where('attendance_date', Carbon::now()->toDateString())->first();
        $avg_check_in_seconds = $attendances->where('type', 'datang')->avg(function ($attendance) {
            return Carbon::parse($attendance->check_in_time)->secondsSinceMidnight();
        });
        $avg_check_out_seconds = $attendances->where('type', 'pulang')->avg(function ($attendance) {
            return Carbon::parse($attendance->check_out_time)->secondsSinceMidnight();
        });
        $avg_check_in = gmdate('H:i:s', $avg_check_in_seconds);
        $avg_check_out = gmdate('H:i:s', $avg_check_out_seconds);
    @endphp
    <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-md border border-solid border-slate-400/30 bg-white p-4 shadow-sm">
            <flux:subheading class="dark:text-slate-900">Total Kehadiran</flux:subheading>

            <flux:heading size="xl" class="mb-1 dark:text-slate-900">{{ $total_attendances }}</flux:heading>

            <div class="flex items-center gap-2">

            </div>
        </div>

        <div class="rounded-md border border-solid border-slate-400/30 bg-white p-4 shadow-sm">
            <flux:subheading class="dark:text-slate-900">Rerata Waktu Datang</flux:subheading>

            <flux:heading size="xl" class="mb-1 dark:text-slate-900">{{ $avg_check_in }}
            </flux:heading>

            <div class="flex items-center gap-2">


            </div>
        </div>
        <div class="rounded-md border border-solid border-slate-400/30 bg-white p-4 shadow-sm">
            <flux:subheading class="dark:text-slate-900">Rerata Waktu Pulang</flux:subheading>

            <flux:heading size="xl" class="mb-1 dark:text-slate-900">{{ $avg_check_out }}
            </flux:heading>

            <div class="flex items-center gap-2">



            </div>
        </div>
        <div class="rounded-md border border-solid border-slate-400/30 bg-white p-4 shadow-sm">
            <flux:subheading class="dark:text-slate-900">Ranking Kehadiran</flux:subheading>

            <flux:heading size="xl" class="mb-1 dark:text-slate-900">Coming Soon
            </flux:heading>

            <div class="flex items-center gap-2">



            </div>
        </div>
    </div>

    <div class="my-5">
        <p class="font-inter text-lg font-medium dark:text-slate-900">Riwayat Presensi</p>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        @php
            $groupedAttendances = $attendances->groupBy('attendance_date');
        @endphp

        @foreach ($groupedAttendances as $date => $dailyAttendances)
            <div class="rounded-md border border-solid border-slate-400/30 bg-white p-6 shadow-sm"
                wire:key="attendance-{{ $date }}">
                <div class="flex flex-row items-center justify-between">
                    <p class="flex flex-row items-center gap-2 font-inter font-medium dark:text-slate-900">
                        <flux:icon.clock></flux:icon.clock>
                        {{ $date }}
                    </p>
                    <flux:badge color="{{ $dailyAttendances->contains('status', 'terlambat') ? 'yellow' : 'green' }}"
                        class="dark:text-{{ $dailyAttendances->contains('status', 'terlambat') ? 'yellow' : 'green' }}-500">
                        {{ $dailyAttendances->first()->status }}
                    </flux:badge>
                </div>
                <div class="mt-8 flex flex-row items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Datang</p>
                        <p class="font-semibold">
                            @php
                                $datang = $dailyAttendances->firstWhere('type', 'datang');
                            @endphp
                            {{ $datang ? $datang->check_in_time : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Pulang</p>
                        <p class="font-semibold">
                            @php
                                $pulang = $dailyAttendances->firstWhere('type', 'pulang');
                            @endphp
                            {{ $pulang ? $pulang->check_in_time : '-' }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-4 flex flex-row justify-center">
        {{ $attendances->links() }}
    </div>

</div>
