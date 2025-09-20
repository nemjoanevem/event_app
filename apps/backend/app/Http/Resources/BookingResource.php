<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'eventId'    => $this->event_id,
            'userId'     => $this->user_id,
            'guestName'  => $this->guest_name,
            'guestEmail' => $this->guest_email,
            'quantity'   => (int) $this->quantity,
            'totalPrice' => (string) $this->total_price,
            'startAt'    => $this->start_at?->toIso8601String(),
            'status'     => $this->status,
            'createdAt'  => $this->created_at?->toIso8601String(),
        ];
    }
}
