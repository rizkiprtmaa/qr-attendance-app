<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<div>
    <div>
        <button class="rounded-md bg-blue-500 px-4 py-2 font-inter text-sm text-white hover:bg-blue-700">Buat
            Kelas</button>
    </div>

    <div class="mb-6 mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg bg-white p-4 shadow-md">
            <h3 class="mb-2 text-gray-500">Kelas Aktif</h3>
            <p class="text-2xl font-bold">1</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-md">
            <h3 class="mb-2 text-gray-500">Jumlah Kelas</h3>
            <p class="text-2xl font-bold">2</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-md">
            <h3 class="mb-2 text-gray-500">Jumlah Mata Pelajaran</h3>
            <p class="text-2xl font-bold">3</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-md">
            <h3 class="mb-2 text-gray-500">Jumlah Murid</h3>
            <p class="text-2xl font-bold">56</p>
        </div>
    </div>


    <div class="mb-6 rounded-lg bg-white p-6 shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold">10 A</h2>
                <p class="text-gray-500">
                    Jurusan: Multimedia
                    | Jumlah Siswa: 28
                    | Waktu: 10.00 - 12.00
                </p>
            </div>
            <button class="rounded-lg bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
                Kelola Presensi
            </button>
        </div>
    </div>


    <div class="rounded-lg bg-white shadow-md">
        <div class="border-b p-6">
            <h3 class="text-lg font-semibold">Daftar Kelas</h3>
        </div>

        <div class="divide-y">

            <div class="flex items-center justify-between p-6 transition hover:bg-gray-50">
                <div>
                    <h4 class="font-semibold">10 A</h4>
                    <p class="text-gray-500">
                        Jurusan: Multimedia
                        | Guru: Ridwan Anas
                    </p>
                </div>
                <div class="flex space-x-2">
                    <button class="text-blue-500 hover:text-blue-600">Lihat Detail</button>
                    <button class="text-green-500 hover:text-green-600">Kelola</button>
                </div>
            </div>

        </div>
    </div>
