<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Booking;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'location',
        'capacity',
        'category',
        'status',
        'price',
        'created_by',
        'max_tickets_per_user',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Helper: available seats = capacity - sum(booked quantities).
     * Returns null if capacity is unlimited.
     */
    public function availableSeats(): ?int
    {
        if (is_null($this->capacity)) {
            return null;
        }

        $booked = (int) $this->bookings()
            ->where('status', 'confirmed')
            ->sum('quantity');

        return max(0, $this->capacity - $booked);
    }

    /**
     * Sum of already booked quantity by a given user for this event.
     */
    public function bookedQuantityForIdentity(?int $userId = null, ?string $guestEmail = null): int
    {
        $q = $this->bookings()->where('status', 'confirmed');

        if ($userId) {
            $q->where('user_id', $userId);
        } elseif ($guestEmail) {
            $q->whereNull('user_id')
            ->where('guest_email', $guestEmail);
        } else {
            return 0;
        }

        return (int) $q->sum('quantity');
    }

    /**
     * Remaining tickets this user can still book, based on max_tickets_per_user.
     */
    public function remainingQuotaForIdentity(?int $userId = null, ?string $guestEmail = null): int
    {
        $max = (int) ($this->max_tickets_per_user ?? 0);
        if ($max <= 0) {
            return 0;
        }
        $already = $this->bookedQuantityForIdentity($userId, $guestEmail);
        return max(0, $max - $already);
    }

    /**
     * Quick check whether booking is allowed time/status-wise.
     */
    public function isBookableNow(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }
        return $this->starts_at->isFuture(); // no booking for past events
    }
}
