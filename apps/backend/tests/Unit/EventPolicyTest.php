<?php

namespace Tests\Unit;

use App\Enums\RoleEnum;
use App\Models\Event;
use App\Models\User;
use App\Policies\EventPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(RoleEnum $role): User
    {
        return User::factory()->create(['role' => $role->value]);
    }

    protected function makeEvent(User $owner, string $status = 'draft'): Event
    {
        return Event::factory()->create([
            'created_by' => $owner->id,
            'status' => $status,
            'starts_at' => now()->addDays(3),
        ]);
    }

    public function test_create_is_allowed_for_organizer_and_admin_only(): void
    {
        $policy = new EventPolicy();
        $organizer = $this->makeUser(RoleEnum::ORGANIZER);
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $user = $this->makeUser(RoleEnum::USER);

        $this->assertTrue($policy->create($organizer));
        $this->assertTrue($policy->create($admin));
        $this->assertFalse($policy->create($user));
    }

    public function test_update_delete_status_only_for_owner_organizer_or_admin(): void
    {
        $policy = new EventPolicy();

        $owner = $this->makeUser(RoleEnum::ORGANIZER);
        $other = $this->makeUser(RoleEnum::ORGANIZER);
        $admin = $this->makeUser(RoleEnum::ADMIN);

        $event = $this->makeEvent($owner);

        // Owner organizer can update/delete/changeStatus
        $this->assertTrue($policy->update($owner, $event));
        $this->assertTrue($policy->delete($owner, $event));
        $this->assertTrue($policy->changeStatus($owner, $event));

        // Other organizer cannot modify someone else's
        $this->assertFalse($policy->update($other, $event));
        $this->assertFalse($policy->delete($other, $event));
        $this->assertFalse($policy->changeStatus($other, $event));

        // Admin can modify anything
        $this->assertTrue($policy->update($admin, $event));
        $this->assertTrue($policy->delete($admin, $event));
        $this->assertTrue($policy->changeStatus($admin, $event));
    }

    public function test_view_published_is_open_but_draft_or_cancelled_is_restricted(): void
    {
        $policy = new EventPolicy();

        $owner = $this->makeUser(RoleEnum::ORGANIZER);
        $other = $this->makeUser(RoleEnum::USER);
        $admin = $this->makeUser(RoleEnum::ADMIN);

        $draft = $this->makeEvent($owner, 'draft');
        $published = $this->makeEvent($owner, 'published');
        $cancelled = $this->makeEvent($owner, 'cancelled');

        // Anyone authenticated can view published
        $this->assertTrue($policy->view($other, $published));
        $this->assertFalse($policy->view($other, $cancelled));

        // Draft only for owner or admin
        $this->assertTrue($policy->view($owner, $draft));
        $this->assertTrue($policy->view($admin, $draft));
        $this->assertFalse($policy->view($other, $draft));
    }
}
