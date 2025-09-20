<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a user with given role.
     */
    protected function makeUser(RoleEnum $role): User
    {
        return User::factory()->create([
            'role' => $role->value,
        ]);
    }

    /**
     * Create a future published Event with helpers.
     */
    protected function makeFutureEvent(User $owner, array $overrides = []): Event
    {
        $base = [
            'created_by' => $owner->id,
            'status' => 'published',
            'starts_at' => now()->addDays(5),
            'capacity' => 10,
            'price' => 20.00,
            'max_tickets_per_user' => 5,
        ];

        return Event::factory()->create(array_merge($base, $overrides));
    }

    /**
     * POST /events/{event}/bookings helper (optionally acting as a user).
     */
    protected function postBooking(Event $event, array $payload, ?User $as = null)
    {
        $req = $this->withHeaders(['Accept' => 'application/json']);
        if ($as) {
            $req = $this->actingAs($as, 'web')->withHeaders(['Accept' => 'application/json']);
        }
        return $req->post("/events/{$event->id}/bookings", $payload);
    }

    public function test_guest_can_create_booking_for_published_future_event(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $event = $this->makeFutureEvent($organizer, [
            'capacity' => 50,
            'price' => 12.50,
            'max_tickets_per_user' => 6,
        ]);

        $payload = [
            'quantity' => 3,
            'guest_name' => 'Guesty McGuest',
            'guest_email' => 'guest@example.com',
        ];

        $res = $this->postBooking($event, $payload);

        $res->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'eventId',
                    'quantity',
                    'totalPrice',
                    'startAt',
                    'createdAt',
                ],
                'message',
            ])
            ->assertJsonFragment([
                'eventId' => $event->id,
                'quantity' => 3,
                'totalPrice' => '37.50', // 12.50 * 3
            ]);
    }

    public function test_guest_must_provide_name_and_email_when_not_authenticated(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $event = $this->makeFutureEvent($organizer);

        // Missing guest_name and guest_email -> 422 with errors
        $res = $this->postBooking($event, ['quantity' => 1]);

        $res->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['guest_name', 'guest_email']]);
    }

    public function test_cannot_book_draft_cancelled_or_past_event(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);

        // Draft event (future)
        $draft = $this->makeFutureEvent($organizer, ['status' => 'draft']);
        $this->postBooking($draft, [
            'quantity' => 1,
            'guest_name' => 'Guest',
            'guest_email' => 'guest@x.com',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['event']]);

        // Cancelled event (future)
        $cancelled = $this->makeFutureEvent($organizer, ['status' => 'cancelled']);
        $this->postBooking($cancelled, [
            'quantity' => 1,
            'guest_name' => 'Guest',
            'guest_email' => 'guest@x.com',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['event']]);

        // Published but past
        $past = $this->makeFutureEvent($organizer, ['starts_at' => now()->subDay()]);
        $this->postBooking($past, [
            'quantity' => 1,
            'guest_name' => 'Guest',
            'guest_email' => 'guest@x.com',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['event']]);
    }

    public function test_user_booking_respects_per_user_quota(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $user = $this->makeUser(RoleEnum::USER);

        $event = $this->makeFutureEvent($organizer, [
            'max_tickets_per_user' => 5,
            'capacity' => 100,
            'price' => 10.00,
        ]);

        // First booking: 3 -> OK
        $this->postBooking($event, ['quantity' => 3], $user)
            ->assertOk()
            ->assertJsonFragment(['quantity' => 3, 'totalPrice' => '30.00']);

        // Second booking: 3 -> should fail (remaining = 2)
        $this->postBooking($event, ['quantity' => 3], $user)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quantity']]);

        // Third booking: 2 -> OK (exactly remaining)
        $this->postBooking($event, ['quantity' => 2], $user)
            ->assertOk()
            ->assertJsonFragment(['quantity' => 2, 'totalPrice' => '20.00']);
    }

    public function test_guest_booking_respects_quota_by_email(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $event = $this->makeFutureEvent($organizer, [
            'max_tickets_per_user' => 4,
            'capacity' => 100,
            'price' => 5.00,
        ]);

        $guest = ['guest_name' => 'G', 'guest_email' => 'g@example.com'];

        // First: 3 -> OK
        $this->postBooking($event, array_merge(['quantity' => 3], $guest))
            ->assertOk()
            ->assertJsonFragment(['quantity' => 3, 'totalPrice' => '15.00']);

        // Second: 2 -> fail (remaining = 1)
        $this->postBooking($event, array_merge(['quantity' => 2], $guest))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quantity']]);

        // Third: 1 -> OK
        $this->postBooking($event, array_merge(['quantity' => 1], $guest))
            ->assertOk()
            ->assertJsonFragment(['quantity' => 1, 'totalPrice' => '5.00']);
    }

    public function test_capacity_cannot_be_exceeded(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $event = $this->makeFutureEvent($organizer, [
            'capacity' => 5,
            'max_tickets_per_user' => 10, // make quota non-limiting
            'price' => 1.00,
        ]);

        // Fill 4 seats
        $this->postBooking($event, [
            'quantity' => 4,
            'guest_name' => 'A',
            'guest_email' => 'a@example.com',
        ])->assertOk();

        // Try to book 2 more (only 1 left) -> 422
        $this->postBooking($event, [
            'quantity' => 2,
            'guest_name' => 'B',
            'guest_email' => 'b@example.com',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['quantity']]);

        // Book the last 1 -> OK
        $this->postBooking($event, [
            'quantity' => 1,
            'guest_name' => 'C',
            'guest_email' => 'c@example.com',
        ])->assertOk();
    }

    public function test_sequential_bookings_fill_capacity_then_last_fails(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $event = $this->makeFutureEvent($organizer, [
            'capacity' => 3,
            'max_tickets_per_user' => 3,
            'price' => 2.00,
        ]);

        // Two separate identities book 1 each -> 2/3 filled
        $this->postBooking($event, [
            'quantity' => 1,
            'guest_name' => 'X',
            'guest_email' => 'x@example.com',
        ])->assertOk();

        $this->postBooking($event, [
            'quantity' => 1,
            'guest_name' => 'Y',
            'guest_email' => 'y@example.com',
        ])->assertOk();

        // Third tries to book 2 -> only 1 left -> 422
        $this->postBooking($event, [
            'quantity' => 2,
            'guest_name' => 'Z',
            'guest_email' => 'z@example.com',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['quantity']]);

        // Third books 1 -> OK (now 3/3)
        $this->postBooking($event, [
            'quantity' => 1,
            'guest_name' => 'Z',
            'guest_email' => 'z@example.com',
        ])->assertOk();
    }

    public function test_total_price_is_calculated_and_formatted_to_two_decimals(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $event = $this->makeFutureEvent($organizer, [
            'price' => 3.4, // 3.40
            'capacity' => 10,
            'max_tickets_per_user' => 10,
        ]);

        $res = $this->postBooking($event, [
            'quantity' => 3,
            'guest_name' => 'Fmt',
            'guest_email' => 'fmt@example.com',
        ])->assertOk();

        $res->assertJsonFragment([
            'totalPrice' => '10.20',
        ]);
    }

    public function test_quantity_must_be_at_least_one(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $event = $this->makeFutureEvent($organizer);

        $this->postBooking($event, [
            'quantity' => 0,
            'guest_name' => 'Zero',
            'guest_email' => 'zero@example.com',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['quantity']]);
    }
}
