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
            ->withSum(['bookings as booked_quantity' => function ($q) {
                $q->where('status', 'confirmed');
            }], 'quantity')
            // Date range filter on starts_at
            ->when(!empty($filters['from']) || !empty($filters['to']), function ($qq) use ($filters) {
                $from = !empty($filters['from']) ? \Carbon\Carbon::parse($filters['from'])->startOfDay() : null;
                $to   = !empty($filters['to'])   ? \Carbon\Carbon::parse($filters['to'])->endOfDay()   : null;

                if ($from && $to) {
                    if ($from->gt($to)) { [$from, $to] = [$to, $from]; } // swap if reversed
                    $qq->whereBetween('starts_at', [$from, $to]);
                } elseif ($from) {
                    $qq->where('starts_at', '>=', $from);
                } elseif ($to) {
                    $qq->where('starts_at', '<=', $to);
                }
            })
            ->orderBy('starts_at', 'asc');

        // Filter to own events only (for organizer/admin)
        if (!empty($filters['own']) && $user) {
            $q->where('created_by', $user->id);
        }

        // Role-based visibility
        if ($user?->isAdmin()) {
            // admin: no base restriction
        } elseif ($user?->isOrganizer()) {
            // organizer: only published + own drafts/cancelled
            $q->where(function ($w) use ($user) {
                $w->where('status', 'published')
                ->orWhere(function ($w2) use ($user) {
                    $w2->where('created_by', $user->id)
                        ->whereIn('status', ['draft', 'cancelled']);
                });
            });
        } else {
            // user or guest: only published
            $q->where('status', 'published');
        }

        // Full-text like search across key fields
        if (!empty($filters['q'])) {
            $term = trim($filters['q']);
            $q->where(function ($w) use ($term) {
                $w->where('title', 'ilike', "%{$term}%")
                ->orWhere('description', 'ilike', "%{$term}%")
                ->orWhere('location', 'ilike', "%{$term}%")
                ->orWhere('category', 'ilike', "%{$term}%");
            });
        }

        $perPage = (int)($filters['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 100));

        return $q->paginate($perPage);
    }


    public function canView(?User $user, Event $event): bool
    {
        if (in_array($event->status, ['published'], true)) {
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
