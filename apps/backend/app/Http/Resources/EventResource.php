<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'createdBy' => $this->created_by,
            'title' => $this->title,
            'description' => $this->description,
            'startsAt' => optional($this->starts_at)->toIso8601String(),
            'location' => $this->location,
            'capacity' => $this->capacity,
            'category' => $this->category,
            'status' => $this->status,
            'price' => $this->price !== null ? (string)$this->price : null,
            'maxTicketsPerUser' => $this->max_tickets_per_user,

            'availableSeats' => $this->availableSeats(),
            'remainingUserQuota' => $request->user()
                ? $this->remainingUserQuota($request->user()->id)
                : null,
            'bookedQuantity' => $this->when(isset($this->booked_quantity), (int)$this->booked_quantity),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
