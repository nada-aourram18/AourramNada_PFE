<?php

namespace Database\Factories;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        $lang = fake()->randomElement(['ar', 'fr', 'en']);
        $messages = [];
        for ($i = 0; $i < fake()->numberBetween(2, 8); $i++) {
            $role = $i % 2 === 0 ? 'user' : 'assistant';
            $messages[] = [
                'role' => $role,
                'content' => fake()->sentence(),
                'timestamp' => now()->subMinutes(10 - $i)->toIso8601String(),
            ];
        }

        return [
            'patient_id' => null,
            'language' => $lang,
            'messages' => $messages,
            'status' => fake()->randomElement(['active', 'cloturee']),
        ];
    }
}
