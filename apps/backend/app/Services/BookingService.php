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

        // Role-based scoping
        if ($user->isAdmin()) {
            // Admins see all bookings
        } elseif ($user->isOrganizer()) {
            // Organizers see bookings for their events only
            $q->whereHas('event', function (Builder $qe) use ($user) {
                $qe->where('created_by', $user->id);
            });
        } else {
            // Regular users see their own bookings only
            // Guests (no user) see no bookings
            $q->where('user_id', $user->id);
        }

        if (!empty($filters['event_id'])) {
            $q->where('event_id', $filters['event_id']);
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
     * Create a booking inside a transaction with a row-level lock on the event.
     *
     * Rules:
     * - Event must be published and in the future.
     * - Enforce per-identity quota (user_id or guest_email).
     * - Enforce capacity (if limited).
     * - Compute total_price = quantity * (event.price ?? 0).
     */
    public function create(Event $event, ?User $user, array $data): Booking
    {
        $quantity   = (int) ($data['quantity'] ?? 1);
        $guestName  = $data['guest_name']  ?? null;
        $guestEmail = $data['guest_email'] ?? null;

        return DB::transaction(function () use ($event, $user, $quantity, $guestName, $guestEmail) {
            /** @var Event $locked */
            $locked = Event::query()
                ->lockForUpdate()
                ->findOrFail($event->id);

            // 1) Time/status guard
            if (! $locked->isBookableNow()) {
                throw ValidationException::withMessages([
                    'event' => __('bookings.not_bookable_now'),
                ]);
            }

            // 2) Per-identity quota
            $remainingQuota = $locked->remainingQuotaForIdentity($user?->id, $guestEmail);
            if ($quantity > $remainingQuota) {
                throw ValidationException::withMessages([
                    'quantity' => __('bookings.quota_exceeded', ['remaining' => $remainingQuota]),
                ]);
            }

            // 3) Capacity guard (if limited)
            if (! is_null($locked->capacity)) {
                $available = $locked->availableSeats();
                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'quantity' => __('bookings.capacity_exceeded', ['available' => $available]),
                    ]);
                }
            }

            // 4) Price
            $unit  = $locked->price ?? 0;
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
}
