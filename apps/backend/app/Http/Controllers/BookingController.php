<?php

namespace App\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Booking\BookingIndexRequest;
use App\Http\Requests\Booking\BookingStoreRequest;
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

    public function index(BookingIndexRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $list = $this->service->list($user, $request->validated());

        return BookingResource::collection($list);
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