<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a user with the given role.
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
     * POST /api/events/{event}/bookings helper (optionally acting as a user).
     */
    protected function postBooking(Event $event, array $payload, ?User $as = null)
    {
        if ($as) {
            Sanctum::actingAs($as);
        }

        return $this->postJson("/api/events/{$event->id}/bookings", $payload);
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
                    'startsAt',
                    'createdAt',
                ],
                'message',
            ])
            ->assertJsonFragment([
                'eventId' => $event->id,
                'quantity' => 3,
                'totalPrice' => '37.50',
            ]);
    }

    public function test_guest_must_provide_name_and_email_when_not_authenticated(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $event = $this->makeFutureEvent($organizer);

        $res = $this->postBooking($event, ['quantity' => 1]);

        $res->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['guest_name', 'guest_email']]);
    }

    public function test_cannot_book_draft_cancelled_or_past_event(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);

        $draft = $this->makeFutureEvent($organizer, ['status' => 'draft']);
        $this->postBooking($draft, [
            'quantity' => 1,
            'guest_name' => 'Guest',
            'guest_email' => 'guest@x.com',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['event']]);

        $cancelled = $this->makeFutureEvent($organizer, ['status' => 'cancelled']);
        $this->postBooking($cancelled, [
            'quantity' => 1,
            'guest_name' => 'Guest',
            'guest_email' => 'guest@x.com',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['event']]);

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

        $this->postBooking($event, ['quantity' => 3], $user)
            ->assertOk()
            ->assertJsonFragment(['quantity' => 3, 'totalPrice' => '30.00']);

        $this->postBooking($event, ['quantity' => 3], $user)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quantity']]);

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

        $this->postBooking($event, array_merge(['quantity' => 3], $guest))
            ->assertOk()
            ->assertJsonFragment(['quantity' => 3, 'totalPrice' => '15.00']);

        $this->postBooking($event, array_merge(['quantity' => 2], $guest))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quantity']]);

        $this->postBooking($event, array_merge(['quantity' => 1], $guest))
            ->assertOk()
            ->assertJsonFragment(['quantity' => 1, 'totalPrice' => '5.00']);
    }

    public function test_capacity_cannot_be_exceeded(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $event = $this->makeFutureEvent($organizer, [
            'capacity' => 5,
            'max_tickets_per_user' => 10,
            'price' => 1.00,
        ]);

        $this->postBooking($event, [
            'quantity' => 4,
            'guest_name' => 'A',
            'guest_email' => 'a@example.com',
        ])->assertOk();

        $this->postBooking($event, [
            'quantity' => 2,
            'guest_name' => 'B',
            'guest_email' => 'b@example.com',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['quantity']]);

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

        $this->postBooking($event, [
            'quantity' => 2,
            'guest_name' => 'Z',
            'guest_email' => 'z@example.com',
        ])->assertStatus(422)->assertJsonStructure(['errors' => ['quantity']]);

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
            'price' => 3.4,
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
