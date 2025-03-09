<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@mail',
            'password' => bcrypt('password'),
        ]);

        $admin->assignRole('admin');

        $admin = User::factory()->create([
            'name' => 'Teacher',
            'email' => 'teacher@example',
            'password' => bcrypt('password'),
        ]);

        $admin->assignRole('teacher');
    }
}
