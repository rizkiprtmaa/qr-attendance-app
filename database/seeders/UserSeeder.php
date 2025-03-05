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
        $studentRole = Role::firstOrCreate(['name' => 'student']);

        $studentUsers = User::factory(10)->create();
        foreach ($studentUsers as $studentUser) {
            $studentUser->assignRole($studentRole);

            Student::factory()->create([
                'user_id' => $studentUser->id,
            ]);
        }
    }
}
