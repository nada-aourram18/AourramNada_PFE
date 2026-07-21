<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'patient_uid' => Patient::generateUid(),
            'full_name' => fake()->name(),
            'phone' => fake()->numerify('06########'),
            'language' => fake()->randomElement(['ar', 'fr', 'en']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
