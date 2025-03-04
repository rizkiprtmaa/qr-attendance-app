<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
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
            'email' => 'admin@example',
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
