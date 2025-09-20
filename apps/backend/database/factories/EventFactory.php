<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $start = $this->faker->dateTimeBetween('-10 days', '+2 months');

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'starts_at' => $start,
            'location' => $this->faker->city(),
            'capacity' => $this->faker->boolean(80) ? $this->faker->numberBetween(10, 300) : null, // 20% unlimited
            'category' => $this->faker->randomElement([null, 'music', 'tech', 'workshop', 'meetup']),
            'status' => $this->faker->randomElement(['draft', 'published']),
            'price' => $this->faker->boolean(70) ? $this->faker->randomFloat(2, 0, 200) : null, // 30% free
            'created_by' => User::inRandomOrder()->first()->id ?? User::factory()->create()->id,
            'max_tickets_per_user' => $this->faker->numberBetween(1, 10),
        ];
    }
}
