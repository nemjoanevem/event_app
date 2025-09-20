<?php

namespace Tests\Unit;

use App\Enums\RoleEnum;
use App\Models\Event;
use App\Models\User;
use App\Services\EventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EventServiceStatusTest extends TestCase
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
            'status' => 'draft',
            'starts_at' => now()->addDays(2),
        ];
        return Event::factory()->create(array_merge($base, $overrides));
    }

    public function test_cannot_publish_past_event(): void
    {
        $svc = new EventService();
        $owner = $this->makeUser(RoleEnum::ORGANIZER);
        $pastDraft = $this->makeEvent($owner, ['starts_at' => now()->subDay()]);

        $this->expectException(HttpException::class);
        $svc->changeStatus($pastDraft, 'published');
    }

    public function test_can_cancel_any_event_status(): void
    {
        $svc = new EventService();
        $owner = $this->makeUser(RoleEnum::ORGANIZER);

        $published = $this->makeEvent($owner, ['status' => 'published']);
        $updated = $svc->changeStatus($published, 'cancelled');

        $this->assertEquals('cancelled', $updated->status);
    }
}
