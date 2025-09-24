<?php

namespace App\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Booking\BookingIndexRequest;
use App\Http\Requests\Booking\BookingStoreRequest;
use App\Http\Resources\BookingResource;
use App\Models\Event;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class BookingController extends Controller
{
    public function __construct(private BookingService $service)
    {
        //
    }

    /**
     * Display a listing of bookings.
     *
     * Handles the incoming request to retrieve a collection of bookings,
     * applying any filters or pagination as specified in the BookingIndexRequest.
     *
     * @param  BookingIndexRequest  $request  The request instance containing validation and filter data.
     * @return AnonymousResourceCollection    A collection of booking resources.
     */
    public function index(BookingIndexRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $list = $this->service->list($user, $request->validated());

        return BookingResource::collection($list);
    }

    /**
     * Create a booking for an event (guests allowed).
     */
    /**
     * Handles the storage of a new booking for the specified event.
     *
     * @param  BookingStoreRequest  $request  The validated request containing booking data.
     * @param  Event  $event  The event for which the booking is being made.
     * @return \Illuminate\Http\Response
     */
    public function store(BookingStoreRequest $request, Event $event): BookingResource
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