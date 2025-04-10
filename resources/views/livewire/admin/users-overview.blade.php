<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Student;
use Livewire\Attributes\On;

new class extends Component {
    // Method untuk menginisialisasi data

    public $teachers;
    public $students_count;

    public $users;

    #[On('delete-user')]
    public function mount()
    {
        $this->teachers = User::role('teacher')->count();
        $this->students_count = Student::count();
        $this->users = User::count();
    }
};
?>

<div class="overflow-hidden">
    <div class="grid max-w-full grid-cols-2 gap-4 md:grid-cols-3">
        <div class="flex w-auto flex-row gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md">
            <div class="hidden items-center rounded-full bg-blue-500 px-4 py-2 md:flex">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </div>
            <div class="gap-5">
                <p class="font-inter text-sm font-medium">Jumlah Pengguna</p>
                <p class="font-inter text-xl">{{ $users }}</p>
            </div>



        </div>
        <div class="flex flex-row gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md">
            <div class="hidden items-center rounded-full bg-blue-500 px-4 py-2 md:flex">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h1.5m9 0h-9" />
                </svg>


            </div>
            <div class="gap-5">
                <p class="font-inter text-sm font-medium">Jumlah Guru</p>
                <p class="font-inter text-xl">{{ $teachers }}</p>
            </div>



        </div>
        <div class="flex flex-row gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md">
            <div class="hidden items-center rounded-full bg-blue-500 px-4 py-2 md:flex">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="size-6 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                </svg>


            </div>
            <div class="gap-5">
                <p class="font-inter text-sm font-medium">Jumlah Siswa</p>
                <p class="font-inter text-xl">{{ $students_count }}</p>
            </div>



        </div>
    </div>
</div>
