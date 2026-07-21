<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-30 days', '+30 days');

        return [
            'appointment_uid' => Appointment::generateUid(),
            'patient_id' => Patient::factory(),
            'appointment_date' => $date->format('Y-m-d'),
            'appointment_time' => fake()->time('H:i:s'),
            'consultation_type' => fake()->randomElement(['general', 'dentaire', 'autre']),
            'status' => fake()->randomElement(['confirme', 'en_attente', 'annule']),
            'google_calendar_event_id' => fake()->optional(0.3)->uuid(),
        ];
    }
}
