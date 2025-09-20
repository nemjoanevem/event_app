<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventStoreRequest;
use App\Http\Requests\EventUpdateRequest;
use App\Http\Requests\EventStatusRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function __construct(private EventService $service)
    {

    }

    
    /**
     * Display a listing of the events.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request instance.
     * @return \Illuminate\Http\Response           The HTTP response containing the list of events.
     */
    public function index(Request $request)
    {
        $events = $this->service->list($request->user(), $request->query());
        return EventResource::collection($events);
    }


    /**
     * Display the specified event.
     *
     * @param  \App\Models\Event  $event  The event instance to display.
     * @return \Illuminate\Http\Response
     */
    public function show(Event $event, Request $request)
    {
        if (! $this->service->canView($request->user(), $event)) {
            abort(404);
        }
        return new EventResource($event);
    }

    /**
     * Handles the incoming request to store a new event.
     *
     * Validates the request using EventStoreRequest and persists the event data.
     *
     * @param  EventStoreRequest  $request  The validated request containing event data.
     * @return \Illuminate\Http\Response    The response after storing the event.
     */
    public function store(EventStoreRequest $request)
    {
        $this->authorize('create', Event::class);

        $event = $this->service->create($request->user(), $request->validated());

        return (new EventResource($event))
            ->additional(['message' => __('events.created')]);
    }

    /**
     * Update an existing event.
     */
    public function update(EventUpdateRequest $request, Event $event)
    {
        $this->authorize('update', $event);

        $event = $this->service->update($event, $request->validated());

        return (new EventResource($event))
            ->additional(['message' => __('events.updated')]);
    }

    /**
     * Delete an event (cascades bookings via FK if set).
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $this->service->delete($event);

        return response()->json([
            'message' => __('events.deleted'),
        ]);
    }

    /**
     * Dedicated status change endpoint.
     */
    public function changeStatus(EventStatusRequest $request, Event $event)
    {
        $this->authorize('changeStatus', $event);

        $event = $this->service->changeStatus($event, $request->validated('status'));

        return (new EventResource($event))
            ->additional(['message' => __('events.status_changed')]);
    }
}
