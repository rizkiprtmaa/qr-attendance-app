<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;


class PrincipleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kepalaSekolahRole = Role::create(['name' => 'kepala_sekolah']);

        $kepalaSekolah = User::find(46); // ganti dengan user ID kepala sekolah
        $kepalaSekolah->assignRole('kepala_sekolah');
    }
}
