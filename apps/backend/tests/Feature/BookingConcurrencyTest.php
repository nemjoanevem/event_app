<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_parallel_like_bookings_second_fails_with_422()
    {
        $event = Event::factory()->create([
            'status'     => 'published',
            'starts_at'  => now()->addDay(),
            'capacity'   => 1,
            'price'      => 1000,
        ]);

        $payload1 = [
            'quantity'    => 1,
            'guest_name'  => 'Alice One',
            'guest_email' => 'alice1@example.test',
        ];
        $r1 = $this->postJson("/api/events/{$event->id}/bookings", $payload1);
        $r1->assertStatus(201);

        $payload2 = [
            'quantity'    => 1,
            'guest_name'  => 'Bob Two',
            'guest_email' => 'bob2@example.test',
        ];
        $r2 = $this->postJson("/api/events/{$event->id}/bookings", $payload2);
        $r2->assertStatus(422)
           ->assertJsonValidationErrors(['quantity']);
    }

    public function test_two_requests_quick_succession_do_not_oversell()
    {
        $event = Event::factory()->create([
            'status'     => 'published',
            'starts_at'  => now()->addMinutes(30),
            'capacity'   => 1,
            'price'      => 500,
        ]);

        $p1 = [
            'quantity'    => 1,
            'guest_name'  => 'Speed One',
            'guest_email' => 'speed1@example.test',
        ];
        $p2 = [
            'quantity'    => 1,
            'guest_name'  => 'Speed Two',
            'guest_email' => 'speed2@example.test',
        ];

        $r1 = $this->postJson("/api/events/{$event->id}/bookings", $p1);
        $r2 = $this->postJson("/api/events/{$event->id}/bookings", $p2);

        $codes = collect([$r1->getStatusCode(), $r2->getStatusCode()]);
        $this->assertTrue($codes->contains(201) || $codes->contains(200), 'One request should succeed.');
        $this->assertTrue($codes->contains(422), 'Second request should fail with 422 due to capacity.');
    }
}
