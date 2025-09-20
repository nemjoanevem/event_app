<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_booted_fills_start_at_and_total_price_on_create(): void
    {
        // Given an event with a unit price and a start time
        $owner = User::factory()->create();
        $event = Event::factory()->create([
            'created_by' => $owner->id,
            'status' => 'published',
            'starts_at' => now()->addDays(4),
            'price' => 12.50,
        ]);

        // When creating a booking without explicit totals/timestamps
        $booking = Booking::create([
            'event_id' => $event->id,
            'quantity' => 3,
            // user_id / guest identity are irrelevant for this test
        ]);

        $booking->refresh();

        // Then model boot logic should populate start_at and total_price
        $this->assertEquals($event->starts_at->toIso8601String(), $booking->start_at->toIso8601String());
        $this->assertSame('37.50', (string) $booking->total_price);
    }

    public function test_casting_and_fillable_are_sane(): void
    {
        $booking = new Booking();

        // Check mass-assignable attributes exist
        $fillable = $booking->getFillable();
        $this->assertContains('user_id', $fillable);
        $this->assertContains('event_id', $fillable);
        $this->assertContains('guest_name', $fillable);
        $this->assertContains('guest_email', $fillable);
        $this->assertContains('quantity', $fillable);
        $this->assertContains('total_price', $fillable);
        $this->assertContains('start_at', $fillable);
        $this->assertContains('status', $fillable);

        // Check casts
        $casts = $booking->getCasts();
        $this->assertSame('datetime', $casts['start_at'] ?? null);
    }
}
