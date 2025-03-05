<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(), // Relasi dengan UserFactory
            'nisn' => $this->faker->unique()->numerify('##########'),
            'parent_number' => $this->faker->phoneNumber,
            'major' => 'Teknik Sepeda Motor',
            'class' => '12A', // Atur kelas sesuai keinginan
        ];
    }
}
