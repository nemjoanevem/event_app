<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Event;
use App\Models\User;


class Booking extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            // Fill start_at from event if missing
            if (empty($booking->start_at) && $booking->event) {
                $booking->start_at = $booking->event->starts_at;
            }

            // Compute total price safely if missing
            if (is_null($booking->total_price)) {
                $price = (float) (optional($booking->event)->price ?? 0);
                $qty   = (int)   ($booking->quantity ?? 1);

                // two decimals, '.' as decimal separator, no thousands separator
                $booking->total_price = number_format($price * $qty, 2, '.', '');
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'event_id',
        'guest_name',
        'guest_email',
        'quantity',
        'total_price',
        'start_at',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_at' => 'datetime',
        'total_price' => 'decimal:2',
    ];

        public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
