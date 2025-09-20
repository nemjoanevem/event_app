<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    public function bookedQuantityByUser(User|int $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        return (int) $this->bookings()
            ->where('user_id', $userId)
            ->where('status', 'confirmed')
            ->sum('quantity');
    }

    /**
     * Remaining tickets this user can still book, based on max_tickets_per_user.
     */
    public function remainingUserQuota(User|int $user): int
    {
        $max = (int) ($this->max_tickets_per_user ?? 0);
        if ($max <= 0) {
            return 0; // if somehow set to 0, block
        }
        $already = $this->bookedQuantityByUser($user);
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
