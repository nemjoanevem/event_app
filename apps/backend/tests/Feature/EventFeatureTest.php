<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
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

    public function test_guest_can_list_published_but_not_drafts_and_cancelled(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $this->makeEvent($organizer, 'published');
        $this->makeEvent($organizer, 'cancelled');
        $this->makeEvent($organizer, 'draft');

        $res = $this->getJson('/api/events');

        $res->assertOk()
            ->assertJsonMissing(['status' => 'draft'])
            ->assertJsonFragment(['status' => 'published'])
            ->assertJsonMissing(['status' => 'cancelled']);
    }

    public function test_organizer_sees_published_and_own_cancelled_and_own_drafts_only(): void
    {
        $organizerA = $this->makeUser(RoleEnum::ORGANIZER);
        $organizerB = $this->makeUser(RoleEnum::ORGANIZER);

        $this->makeEvent($organizerA, 'published');
        $this->makeEvent($organizerA, 'cancelled');
        $this->makeEvent($organizerA, 'draft');

        $this->makeEvent($organizerB, 'cancelled');
        $this->makeEvent($organizerB, 'draft');

        Sanctum::actingAs($organizerA);

        $res = $this->getJson('/api/events')->assertOk();

        $items = collect($res->json('data') ?? []);

        $this->assertTrue($items->contains(fn ($e) => ($e['status'] ?? null) === 'published'));

        $this->assertTrue(
            $items->contains(fn ($e) => ($e['status'] ?? null) === 'cancelled' && ($e['createdBy'] ?? null) === $organizerA->id),
            'Organizer should see own cancelled event.'
        );

        $this->assertFalse(
            $items->contains(fn ($e) => ($e['status'] ?? null) === 'cancelled' && ($e['createdBy'] ?? null) === $organizerB->id),
            "Organizer should NOT see other organizer's cancelled event."
        );

        $this->assertTrue(
            $items->contains(fn ($e) => ($e['status'] ?? null) === 'draft' && ($e['createdBy'] ?? null) === $organizerA->id),
            'Organizer should see own draft.'
        );

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

        Sanctum::actingAs($admin);

        $res = $this->getJson('/api/events');

        $res->assertOk()
            ->assertJsonFragment(['status' => 'published'])
            ->assertJsonFragment(['status' => 'cancelled'])
            ->assertJsonFragment(['status' => 'draft']);
    }

    public function test_guest_can_view_published_but_not_draft_or_cancelled(): void
    {
        $org = $this->makeUser(RoleEnum::ORGANIZER);
        $published = $this->makeEvent($org, 'published');
        $cancelled = $this->makeEvent($org, 'cancelled');
        $draft = $this->makeEvent($org, 'draft');

        $this->getJson("/api/events/{$published->id}")
            ->assertOk();

        $this->getJson("/api/events/{$cancelled->id}")
            ->assertNotFound();

        $this->getJson("/api/events/{$draft->id}")
            ->assertNotFound();
    }

    public function test_organizer_can_create_event_user_cannot(): void
    {
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $user = $this->makeUser(RoleEnum::USER);

        $payload = [
            'title' => 'My Event',
            'starts_at' => now()->addDays(5)->toISOString(),
            'status' => 'draft',
            'max_tickets_per_user' => 5,
        ];

        Sanctum::actingAs($organizer);

        $this->postJson('/api/events', $payload)
            ->assertOk()
            ->assertJsonFragment(['title' => 'My Event']);

        Sanctum::actingAs($user);

        $this->postJson('/api/events', $payload)
            ->assertForbidden();
    }

    public function test_organizer_can_update_own_event_but_not_others_admin_can_update_any(): void
    {
        $organizerA = $this->makeUser(RoleEnum::ORGANIZER);
        $organizerB = $this->makeUser(RoleEnum::ORGANIZER);
        $admin = $this->makeUser(RoleEnum::ADMIN);

        $ownEvent = $this->makeEvent($organizerA, 'draft');
        $otherEvent = $this->makeEvent($organizerB, 'draft');

        Sanctum::actingAs($organizerA);

        $this->putJson("/api/events/{$ownEvent->id}", ['title' => 'Updated'])
            ->assertOk()
            ->assertJsonFragment(['title' => 'Updated']);

        $this->putJson("/api/events/{$otherEvent->id}", ['title' => 'Hack'])
            ->assertForbidden();

        Sanctum::actingAs($admin);

        $this->putJson("/api/events/{$otherEvent->id}", ['title' => 'Admin Edit'])
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

        Sanctum::actingAs($organizerA);

        $this->deleteJson("/api/events/{$ownEvent->id}")
            ->assertOk();

        $this->deleteJson("/api/events/{$otherEvent->id}")
            ->assertForbidden();

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/events/{$otherEvent->id}")
            ->assertOk();
    }

    public function test_cannot_publish_past_event_via_status_endpoint(): void
    {
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $org = $this->makeUser(RoleEnum::ORGANIZER);

        $pastDraft = $this->makeEvent($org, 'draft', now()->subDays(2));

        Sanctum::actingAs($admin);

        $this->patchJson("/api/events/{$pastDraft->id}/status", ['status' => 'published'])
            ->assertStatus(422);
    }

    public function test_can_change_status_to_cancelled(): void
    {
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $org = $this->makeUser(RoleEnum::ORGANIZER);
        $future = $this->makeEvent($org, 'published', now()->addDays(7));

        Sanctum::actingAs($admin);

        $this->patchJson("/api/events/{$future->id}/status", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'cancelled']);
    }
}
