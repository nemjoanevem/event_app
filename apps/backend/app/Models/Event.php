<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    /**
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
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'price'     => 'decimal:2',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Available seats = capacity - sum(confirmed quantities).
     * Returns null if capacity is unlimited.
     *
     * Uses preloaded `booked_quantity` (from withSum) when present to avoid extra queries.
     */
    public function availableSeats(): ?int
    {
        if (is_null($this->capacity)) {
            return null;
        }

        $booked = isset($this->booked_quantity)
            ? (int) $this->booked_quantity
            : (int) $this->bookings()->confirmed()->sum('quantity');

        return max(0, $this->capacity - $booked);
    }

    /**
     * Sum of already booked (confirmed) quantity by identity for this event.
     */
    public function bookedQuantityForIdentity(?int $userId = null, ?string $guestEmail = null): int
    {
        $q = $this->bookings()->confirmed();

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
            // If 0 means "no per-user limit", consider returning PHP_INT_MAX here.
            return 0;
        }

        $already = $this->bookedQuantityForIdentity($userId, $guestEmail);

        return max(0, $max - $already);
    }

    /**
     * Checks if the event is currently bookable (status/time).
     */
    public function isBookableNow(): bool
    {
        return $this->status === 'published'
            && $this->starts_at->isFuture();
    }
}
