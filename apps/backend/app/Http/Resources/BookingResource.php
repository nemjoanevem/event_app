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
        $name  = $this->guest_name  ?: optional($this->user)->name;
        $email = $this->guest_email ?: optional($this->user)->email;

        return [
            'id'         => $this->id,
            'eventId'    => $this->event_id,
            'eventTitle' => optional($this->event)->title,
            'quantity'   => (int) $this->quantity,
            'totalPrice' => $this->total_price !== null ? (string) $this->total_price : null,
            'startsAt'   => optional($this->event?->starts_at)->toIso8601String(),
            'name'       => $name,
            'email'      => $email,
            'createdAt'  => optional($this->created_at)->toIso8601String(),
        ];
    }
}
