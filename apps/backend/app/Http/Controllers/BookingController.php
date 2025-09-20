<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookingStoreRequest;
use App\Http\Resources\BookingResource;
use App\Models\Event;
use App\Services\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $service)
    {
        //
    }

    /**
     * Create a booking for an event (guests allowed).
     */
    public function store(BookingStoreRequest $request, Event $event)
    {
        // No authorize() here because guests can book.
        $booking = $this->service->create(
            event: $event,
            user: $request->user(),
            data: $request->validated()
        );

        return (new BookingResource($booking))
            ->additional(['message' => __('bookings.created')]);
    }
}