<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EventService
{
    /**
     * List events with optional filters.
     */
    public function list(?User $user, array $filters = []): LengthAwarePaginator|Collection
    {
        $q = Event::query()
            ->withCount(['bookings as booked_quantity' => function ($w) {
                $w->where('status', 'confirmed')
                  ->select(\DB::raw('coalesce(sum(quantity),0)'));
            }])
            ->orderBy('starts_at', 'asc');

        // Role-based visibility
        if ($user?->isAdmin()) {
            // admin: no base restriction
        } elseif ($user?->isOrganizer()) {
            // organizer: all published+cancelled + own drafts
            $q->where(function ($w) use ($user) {
                $w->whereIn('status', ['published', 'cancelled'])
                  ->orWhere(function ($w2) use ($user) {
                      $w2->where('status', 'draft')->where('created_by', $user->id);
                  });
            });
        } else {
            // user or guest: only published+cancelled
            $q->whereIn('status', ['published', 'cancelled']);
        }

        // Optional filters
        if (!empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (!empty($filters['category'])) {
            $q->where('category', $filters['category']);
        }
        if (!empty($filters['q'])) {
            $term = $filters['q'];
            $q->where(function ($w) use ($term) {
                $w->where('title', 'ilike', "%{$term}%")
                  ->orWhere('description', 'ilike', "%{$term}%")
                  ->orWhere('location', 'ilike', "%{$term}%");
            });
        }

        return $q->paginate($filters['per_page'] ?? 15);
    }

    public function canView(?User $user, Event $event): bool
    {
        if (in_array($event->status, ['published', 'cancelled'], true)) {
            return true; // visible to anyone
        }
        if ($user?->isAdmin()) {
            return true;
        }
        if ($user?->isOrganizer() && $event->created_by === $user->id) {
            return true;
        }
        return false;
    }

    /**
     * Create an event for the creator (auth user).
     */
    public function create(User $creator, array $data): Event
    {
        $data['created_by'] = $creator->id;

        // Normalize business defaults
        $data['status'] = $data['status'] ?? 'draft';

        /** @var Event $event */
        $event = Event::create($data);

        return $event->fresh();
    }

    /**
     * Update basic fields (status not handled here if you use dedicated endpoint).
     */
    public function update(Event $event, array $data): Event
    {
        // If you keep status in this path, validate transitions separately.
        $event->fill($data);
        $event->save();

        return $event->fresh();
    }

    /**
     * Delete the event.
     */
    public function delete(Event $event): void
    {
        $event->delete();
    }

    /**
     * Change event status with optional transition rules.
     */
    public function changeStatus(Event $event, string $status): Event
    {
        // Optional transition rules (example):
        // - cannot publish past events
        if ($status === 'published' && !$event->starts_at->isFuture()) {
            abort(422, __('events.cannot_publish_past'));
        }

        // - if cancelled, you might later notify attendees, etc.
        $event->status = $status;
        $event->save();

        return $event->fresh();
    }
}
