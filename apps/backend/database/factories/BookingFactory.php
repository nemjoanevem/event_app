<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Event;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $event = Event::inRandomOrder()->first() ?? Event::factory()->published()->create();

        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        $quantity = $this->faker->numberBetween(1, 4);
        $unitPrice = $event->price ?? 0;
        $total = $quantity * $unitPrice;

        return [
            'user_id' => $user->id,
            'event_id' => $event->id,
            'quantity' => $quantity,
            'total_price' => $total,
            'start_at' => $event->starts_at, // keep historical value
            'status' => 'confirmed',
        ];
    }
}
