<?php

use Livewire\Volt\Component;
use App\Models\User;

new class extends Component {
    public $teachers;
    public $students;
    public $users;

    // Method untuk menginisialisasi data
    public function mount()
    {
        $this->teachers = User::role('teacher')->count();
        $this->students = User::role('student')->count();
        $this->users = User::count();
    }
};
?>

<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="flex flex-row gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md">
        <div class="flex items-center rounded-full bg-blue-100 px-4 py-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
            </svg>
        </div>
        <div class="gap-5">
            <p class="font-header text-xl font-medium">Jumlah Pengguna</p>
            <p class="font-body">{{ $users }}</p>
        </div>



    </div>
    <div class="flex flex-row gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md">
        <div class="flex items-center rounded-full bg-blue-100 px-4 py-2">
            <svg id="fi_12404317" enable-background="new 0 0 512 512" viewBox="0 0 512 512" class="size-6"
                xmlns="http://www.w3.org/2000/svg">
                <g>
                    <path
                        d="m484.119 343.733v-295.546h-395.932v128.021c-4.168-.828-8.474-1.271-12.881-1.271-36.4 0-66.013 29.613-66.013 66.013 0 20.444 9.343 38.746 23.982 50.864-20.059 13.543-33.275 36.484-33.275 62.455v109.544h150.614l-.024-90.077 361.41-.003v-30zm-408.814-138.796c19.858 0 36.013 16.155 36.013 36.013 0 19.857-16.155 36.013-36.013 36.013s-36.013-16.155-36.013-36.013 16.155-36.013 36.013-36.013zm-45.305 228.876v-79.544c0-24.981 20.324-45.305 45.305-45.305h130.62v12.003c0 12.522-10.162 22.714-22.672 22.767h-122.948v30h60.285c.005 15.426.012 40.005.017 60.08h-90.607zm200.755-90.08c3.312-6.897 5.17-14.618 5.17-22.767v-17.666l81.283-39.989-13.243-26.919-86.53 42.57h-88.211c7.606-10.756 12.094-23.867 12.094-38.013 0-20.041-8.988-38.011-23.132-50.127v-112.635h335.932v265.546z">
                    </path>
                    <path d="m203.743 116.998h164.819v30h-164.819z"></path>
                    <path d="m177.861 180.567h216.583v30h-216.583z"></path>
                </g>

            </svg>

        </div>
        <div class="gap-5">
            <p class="font-header text-xl font-medium">Jumlah Guru</p>
            <p class="font-body">{{ $teachers }}</p>
        </div>



    </div>
    <div class="flex flex-row gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md">
        <div class="flex items-center rounded-full bg-blue-100 px-4 py-2">
            <svg id="fi_3173683" enable-background="new 0 0 512.109 512.109" height="512" class="size-6"
                viewBox="0 0 512.109 512.109" width="512" xmlns="http://www.w3.org/2000/svg">
                <g>
                    <path
                        d="m482.109 348.743v-185.243l29.878-12.415-255.875-105.138-256.112 105.138 106.119 44.049-.01 142.317 19.681-7.036c1.245-.368 20.787-5.862 52.378.457 32.504 6.501 67 40.565 67.335 40.898l10.597 10.576 10.606-10.566c.345-.343 34.841-34.407 67.345-40.908 31.859-6.372 52.373-.739 53.552-.401l19.507 6.55v-142.358l45-18.698v172.777c-17.459 6.192-30 22.865-30 42.42v75h90v-75c-.001-19.554-12.542-36.227-30.001-42.419zm-105-50.294c-11.871-1.062-28.644-1.055-48.941 3.005-29.128 5.825-57.293 26.865-72.059 39.453-14.766-12.588-42.931-33.627-72.059-39.453-20.134-4.027-36.385-4.129-47.941-3.114v-90.752l120.003 49.817 120.997-50.276zm-121.003-73.529-177.487-73.68 177.487-72.862 177.324 72.862zm226.003 211.243h-30v-45c0-8.271 6.729-15 15-15s15 6.729 15 15z">
                    </path>
                </g>
            </svg>

        </div>
        <div class="gap-5">
            <p class="font-header text-xl font-medium">Jumlah Siswa</p>
            <p class="font-body">{{ $students }}</p>
        </div>



    </div>
</div>
