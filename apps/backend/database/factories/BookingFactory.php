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
        $event = Event::where('status', 'published')
            ->where('starts_at', '>', now())
            ->inRandomOrder()
            ->first()
            ?? Event::factory()->published()->create(['starts_at' => now()->addDays(7)]);

        $isGuest = $this->faker->boolean(40); // 40% guest

        $userId = $isGuest ? null : (User::inRandomOrder()->value('id') ?? User::factory()->create()->id);

        $quantity = $this->faker->numberBetween(1, 3);
        $unit = $event->price ?? 0;
        $total = $quantity * $unit;

        return [
            'user_id'     => $userId,
            'event_id'    => $event->id,
            'guest_name'  => $isGuest ? $this->faker->name() : null,
            'guest_email' => $isGuest ? $this->faker->safeEmail() : null,
            'quantity'    => $quantity,
            'total_price' => $total,
            'start_at'    => $event->starts_at,
            'status'      => 'confirmed',
        ];
    }
}
