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
        $name  = $this->guest_name  ?? $this->whenLoaded('user', fn () => $this->user?->name);
        $email = $this->guest_email ?? $this->whenLoaded('user', fn () => $this->user?->email);

        return [
            'id'         => $this->id,
            'eventId'    => $this->event_id,
            'eventTitle' => $this->whenLoaded('event', fn () => $this->event?->title),
            'quantity'   => (int) $this->quantity,
            'totalPrice' => $this->total_price !== null ? (string) $this->total_price : null,
            'startsAt'   => $this->whenLoaded('event',
                                fn () => $this->event?->starts_at?->toIso8601String(),
                                fn () => $this->start_at?->toIso8601String()
                                ),
            'name'       => $name,
            'email'      => $email,
            'createdAt'  => optional($this->created_at)->toIso8601String(),
        ];
    }
}
