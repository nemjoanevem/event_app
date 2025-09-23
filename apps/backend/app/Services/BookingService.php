<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BookingService
{

    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        $q = Booking::query()
            ->with(['event:id,title,starts_at', 'user:id,name,email'])
            ->orderByDesc('id');

        // Scope by role
        if ($user->isAdmin()) {
            // admins see everything
        } else {
            // users/organizers see their own bookings (booked as themselves)
            $q->where('user_id', $user->id);
        }

        // Search by event title OR user/guest name/email
        if (!empty($filters['q'])) {
            $term = trim($filters['q']);
            $q->where(function (Builder $w) use ($term) {
                $w->whereHas('event', function (Builder $we) use ($term) {
                    $we->where('title', 'ilike', "%{$term}%");
                })
                ->orWhere('guest_name', 'ilike', "%{$term}%")
                ->orWhere('guest_email', 'ilike', "%{$term}%")
                ->orWhereHas('user', function (Builder $wu) use ($term) {
                    $wu->where('name', 'ilike', "%{$term}%")
                       ->orWhere('email', 'ilike', "%{$term}%");
                });
            });
        }

        $perPage = (int)($filters['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 100));

        return $q->paginate($perPage);
    }

    /**
     * Create a booking under transaction and row-level lock on the event.
     *
     * Rules:
     * - Event must be published and in the future.
     * - Enforce per-identity quota (user_id or guest_email).
     * - Enforce capacity (if limited).
     * - Compute total_price = quantity * (event.price ?? 0).
     */
    public function create(Event $event, ?User $user, array $data): Booking
    {
        $quantity = (int) ($data['quantity'] ?? 1);
        $guestName = $data['guest_name'] ?? null;
        $guestEmail = $data['guest_email'] ?? null;

        return DB::transaction(function () use ($event, $user, $quantity, $guestName, $guestEmail) {
            // Lock the event row to serialize concurrent bookings for the same event.
            /** @var Event $locked */
            $locked = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            // 1) Time/status guard: only published & future events are bookable
            if ($locked->status !== 'published' || !$locked->starts_at->isFuture()) {
                throw ValidationException::withMessages([
                    'event' => __('bookings.not_bookable_now'),
                ]);
            }

            // 2) Per-identity quota check
            $remainingQuota = $this->remainingQuota($locked, $user, $guestEmail);
            if ($quantity > $remainingQuota) {
                throw ValidationException::withMessages([
                    'quantity' => __('bookings.quota_exceeded', ['remaining' => $remainingQuota]),
                ]);
            }

            // 3) Capacity check (if limited)
            if (!is_null($locked->capacity)) {
                $available = $this->availableSeats($locked);
                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'quantity' => __('bookings.capacity_exceeded', ['available' => $available]),
                    ]);
                }
            }

            // 4) Compute total price
            $unit = $locked->price ?? 0;
            $total = (string) number_format($unit * $quantity, 2, '.', '');

            // 5) Create booking
            $booking = Booking::create([
                'user_id'     => $user?->id,
                'event_id'    => $locked->id,
                'guest_name'  => $user ? null : $guestName,
                'guest_email' => $user ? null : $guestEmail,
                'quantity'    => $quantity,
                'total_price' => $total,
                'start_at'    => $locked->starts_at,
                'status'      => 'confirmed',
            ]);

            return $booking->fresh(['event', 'user']);
        });
    }

    /**
     * Remaining per-identity quota for this event.
     */
    protected function remainingQuota(Event $event, ?User $user, ?string $guestEmail): int
    {
        $max = (int) ($event->max_tickets_per_user ?? 0);
        if ($max <= 0) {
            return 0;
        }

        $already = $this->bookedQuantityForIdentity($event, $user, $guestEmail);

        return max(0, $max - $already);
    }

    /**
     * Sum of already-booked (confirmed) quantity by identity for this event.
     */
    protected function bookedQuantityForIdentity(Event $event, ?User $user, ?string $guestEmail): int
    {
        $q = $event->bookings()->where('status', 'confirmed');

        if ($user) {
            $q->where('user_id', $user->id);
        } else {
            // Guests identified by email
            $q->whereNull('user_id')->where('guest_email', $guestEmail);
        }

        return (int) $q->sum('quantity');
    }

    /**
     * Available seats considering confirmed bookings, or null if unlimited.
     */
    protected function availableSeats(Event $event): ?int
    {
        if (is_null($event->capacity)) {
            return null;
        }

        $booked = (int) $event->bookings()
            ->where('status', 'confirmed')
            ->sum('quantity');

        return max(0, $event->capacity - $booked);
    }
}
