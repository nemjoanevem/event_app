<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EventFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** 
     * Helper: create user with a specific role.
     */
    protected function makeUser(RoleEnum $role): User
    {
        return User::factory()->create([
            'role' => $role->value,
        ]);
    }

    /** 
     * Helper: create event with owner and status.
     */
    protected function makeEvent(User $owner, string $status = 'published', ?Carbon $startsAt = null, array $overrides = []): Event
    {
        $startsAt = $startsAt ?? now()->addDays(3);

        return Event::factory()->create(array_merge([
            'created_by' => $owner->id,
            'status' => $status,
            'starts_at' => $startsAt,
        ], $overrides));
    }

    public function test_guest_can_list_published_and_cancelled_but_not_drafts(): void
    {
        // Arrange
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $this->makeEvent($organizer, 'published');
        $this->makeEvent($organizer, 'cancelled');
        $this->makeEvent($organizer, 'draft');

        // Act
        $res = $this->withHeaders(['Accept' => 'application/json'])
            ->get('/events');

        // Assert
        $res->assertOk()
            ->assertJsonMissing(['status' => 'draft'])
            ->assertJsonFragment(['status' => 'published'])
            ->assertJsonFragment(['status' => 'cancelled']);
    }

    public function test_organizer_sees_published_and_cancelled_and_own_drafts_only(): void
    {
        // Arrange
        $organizerA = $this->makeUser(\App\Enums\RoleEnum::ORGANIZER);
        $organizerB = $this->makeUser(\App\Enums\RoleEnum::ORGANIZER);

        // Organizer A events
        $this->makeEvent($organizerA, 'published');
        $this->makeEvent($organizerA, 'cancelled');
        $this->makeEvent($organizerA, 'draft');

        // Organizer B draft (should NOT be visible to A)
        $this->makeEvent($organizerB, 'draft');

        // Act
        $res = $this->actingAs($organizerA, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/events')
            ->assertOk();

        // Parse payload for robust checks
        $items = collect($res->json('data') ?? []);

        // Assert published & cancelled are present (for anyone)
        $this->assertTrue($items->contains(fn ($e) => ($e['status'] ?? null) === 'published'));
        $this->assertTrue($items->contains(fn ($e) => ($e['status'] ?? null) === 'cancelled'));

        // Assert A's own draft is visible
        $this->assertTrue(
            $items->contains(fn ($e) => ($e['status'] ?? null) === 'draft' && ($e['createdBy'] ?? null) === $organizerA->id),
            'Organizer should see own draft.'
        );

        // Assert B's draft is NOT visible
        $this->assertFalse(
            $items->contains(fn ($e) => ($e['status'] ?? null) === 'draft' && ($e['createdBy'] ?? null) === $organizerB->id),
            "Organizer should NOT see other organizer's draft."
        );
    }

    public function test_admin_sees_every_event_including_drafts(): void
    {
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $org = $this->makeUser(RoleEnum::ORGANIZER);

        $this->makeEvent($org, 'published');
        $this->makeEvent($org, 'cancelled');
        $this->makeEvent($org, 'draft');

        $res = $this->actingAs($admin, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/events');

        $res->assertOk()
            ->assertJsonFragment(['status' => 'published'])
            ->assertJsonFragment(['status' => 'cancelled'])
            ->assertJsonFragment(['status' => 'draft']);
    }

    public function test_guest_can_view_published_or_cancelled_but_not_draft(): void
    {
        $org = $this->makeUser(RoleEnum::ORGANIZER);
        $published = $this->makeEvent($org, 'published');
        $cancelled = $this->makeEvent($org, 'cancelled');
        $draft = $this->makeEvent($org, 'draft');

        $this->withHeaders(['Accept' => 'application/json'])
            ->get("/events/{$published->id}")
            ->assertOk();

        $this->withHeaders(['Accept' => 'application/json'])
            ->get("/events/{$cancelled->id}")
            ->assertOk();

        // Draft should be hidden from guests -> 404
        $this->withHeaders(['Accept' => 'application/json'])
            ->get("/events/{$draft->id}")
            ->assertNotFound();
    }

    public function test_organizer_can_create_event_user_cannot(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $user = $this->makeUser(RoleEnum::USER);

        // Organizer can create
        $payload = [
            'title' => 'My Event',
            'starts_at' => now()->addDays(5)->toISOString(),
            'status' => 'draft',
            'max_tickets_per_user' => 5,
        ];

        $this->actingAs($organizer, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/events', $payload)
            ->assertOk()
            ->assertJsonFragment(['title' => 'My Event']);

        // User cannot create
        $this->actingAs($user, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/events', $payload)
            ->assertForbidden();
    }

    public function test_organizer_can_update_own_event_but_not_others_admin_can_update_any(): void
    {
        $organizerA = $this->makeUser(RoleEnum::ORGANIZER);
        $organizerB = $this->makeUser(RoleEnum::ORGANIZER);
        $admin = $this->makeUser(RoleEnum::ADMIN);

        $ownEvent = $this->makeEvent($organizerA, 'draft');
        $otherEvent = $this->makeEvent($organizerB, 'draft');

        // Own event: OK
        $this->actingAs($organizerA, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->put("/events/{$ownEvent->id}", ['title' => 'Updated'])
            ->assertOk()
            ->assertJsonFragment(['title' => 'Updated']);

        // Other's event: forbidden
        $this->actingAs($organizerA, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->put("/events/{$otherEvent->id}", ['title' => 'Hack'])
            ->assertForbidden();

        // Admin can update any
        $this->actingAs($admin, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->put("/events/{$otherEvent->id}", ['title' => 'Admin Edit'])
            ->assertOk()
            ->assertJsonFragment(['title' => 'Admin Edit']);
    }

    public function test_organizer_can_delete_own_event_but_not_others_admin_can_delete_any(): void
    {
        $organizerA = $this->makeUser(RoleEnum::ORGANIZER);
        $organizerB = $this->makeUser(RoleEnum::ORGANIZER);
        $admin = $this->makeUser(RoleEnum::ADMIN);

        $ownEvent = $this->makeEvent($organizerA, 'draft');
        $otherEvent = $this->makeEvent($organizerB, 'draft');

        // Own delete OK
        $this->actingAs($organizerA, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->delete("/events/{$ownEvent->id}")
            ->assertOk();

        // Other's delete forbidden
        $this->actingAs($organizerA, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->delete("/events/{$otherEvent->id}")
            ->assertForbidden();

        // Admin delete OK
        $this->actingAs($admin, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->delete("/events/{$otherEvent->id}")
            ->assertOk();
    }

    public function test_cannot_publish_past_event_via_status_endpoint(): void
    {
        // Past event: publishing should fail (422)
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $org = $this->makeUser(RoleEnum::ORGANIZER);

        $pastDraft = $this->makeEvent($org, 'draft', now()->subDays(2));

        $this->actingAs($admin, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->patch("/events/{$pastDraft->id}/status", ['status' => 'published'])
            ->assertStatus(422);
    }

    public function test_can_change_status_to_cancelled(): void
    {
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $org = $this->makeUser(RoleEnum::ORGANIZER);
        $future = $this->makeEvent($org, 'published', now()->addDays(7));

        $this->actingAs($admin, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->patch("/events/{$future->id}/status", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'cancelled']);
    }
}
