<?php

namespace Tests\Unit;

use App\Enums\RoleEnum;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(RoleEnum $role): User
    {
        return User::factory()->create(['role' => $role->value]);
    }

    protected function makeEvent(User $owner, array $overrides = []): Event
    {
        $base = [
            'created_by' => $owner->id,
            'status' => 'published',
            'starts_at' => now()->addDays(5),
            'capacity' => 5,
            'price' => 10.00,
            'max_tickets_per_user' => 3,
        ];
        return Event::factory()->create(array_merge($base, $overrides));
    }

    public function test_user_quota_is_enforced(): void
    {
        $svc = new BookingService();
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $user = $this->makeUser(RoleEnum::USER);
        $event = $this->makeEvent($organizer, ['capacity' => 100, 'max_tickets_per_user' => 4]);

        // First booking ok (3)
        $b1 = $svc->create($event, $user, ['quantity' => 3]);
        $this->assertInstanceOf(Booking::class, $b1);

        // Second booking of 2 should fail (remaining is 1)
        $this->expectException(ValidationException::class);
        $svc->create($event->fresh(), $user, ['quantity' => 2]);
    }

    public function test_guest_quota_by_email_is_enforced(): void
    {
        $svc = new BookingService();
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $event = $this->makeEvent($organizer, ['capacity' => 100, 'max_tickets_per_user' => 4]);

        // First (3) ok
        $svc->create($event, null, [
            'quantity' => 3,
            'guest_name' => 'G',
            'guest_email' => 'g@example.com',
        ]);

        // Another (2) with same guest email should fail
        $this->expectException(ValidationException::class);
        $svc->create($event->fresh(), null, [
            'quantity' => 2,
            'guest_name' => 'G2',
            'guest_email' => 'g@example.com',
        ]);
    }

    public function test_capacity_is_enforced_even_sequentially(): void
    {
        $svc = new BookingService();
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $event = $this->makeEvent($organizer, ['capacity' => 3, 'max_tickets_per_user' => 5]);

        // Book 2 seats
        $svc->create($event, null, [
            'quantity' => 2,
            'guest_name' => 'A',
            'guest_email' => 'a@example.com',
        ]);

        // Try to book 2 more -> only 1 left -> should fail
        $this->expectException(ValidationException::class);
        $svc->create($event->fresh(), null, [
            'quantity' => 2,
            'guest_name' => 'B',
            'guest_email' => 'b@example.com',
        ]);
    }

    public function test_not_bookable_for_draft_cancelled_or_past(): void
    {
        $svc = new BookingService();
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);

        $draft = $this->makeEvent($organizer, ['status' => 'draft']);
        $this->expectException(ValidationException::class);
        $svc->create($draft, null, [
            'quantity' => 1,
            'guest_name' => 'X',
            'guest_email' => 'x@example.com',
        ]);

        $cancelled = $this->makeEvent($organizer, ['status' => 'cancelled']);
        $this->expectException(ValidationException::class);
        $svc->create($cancelled, null, [
            'quantity' => 1,
            'guest_name' => 'Y',
            'guest_email' => 'y@example.com',
        ]);

        $past = $this->makeEvent($organizer, ['starts_at' => now()->subDay(), 'status' => 'published']);
        $this->expectException(ValidationException::class);
        $svc->create($past, null, [
            'quantity' => 1,
            'guest_name' => 'Z',
            'guest_email' => 'z@example.com',
        ]);
    }

    public function test_total_price_calculation_is_consistent(): void
    {
        $svc = new BookingService();
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);

        $event = $this->makeEvent($organizer, [
            'price' => 3.40,
            'capacity' => 10,
            'max_tickets_per_user' => 10,
        ]);

        $b = $svc->create($event, null, [
            'quantity' => 3,
            'guest_name' => 'Fmt',
            'guest_email' => 'fmt@example.com',
        ]);

        $this->assertSame('10.20', (string) $b->total_price);
    }
}
