<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteEventsRequest;
use App\Http\Requests\Admin\BulkPublishEventsRequest;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Interfaces\EventCategoryRepositoryInterface;
use App\Interfaces\EventRepositoryInterface;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(
        private EventRepositoryInterface $events,
        private EventCategoryRepositoryInterface $categories,
        private EventService $eventService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Event::class);

        $filters = $request->only(['q', 'category', 'status', 'when', 'featured', 'sort', 'direction', 'trashed']);

        return view('admin.events.index', [
            'events' => $this->events->adminSearch($filters, 15),
            'categories' => $this->categories->allOrdered(),
            'statuses' => EventStatus::options(),
            'statistics' => $this->events->statistics(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Event::class);

        return view('admin.events.create', [
            'categories' => $this->categories->allOrdered(),
            'statuses' => EventStatus::options(),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $event = $this->eventService->createEvent($request->validated());

        return redirect()->route('admin.events.edit', $event)
            ->with('status', 'Event created successfully.');
    }

    public function edit(Event $event): View
    {
        $this->authorize('update', $event);

        return view('admin.events.edit', [
            'event' => $event->load(['category', 'media', 'seo'])->loadCount(['registrations', 'activeRegistrations']),
            'categories' => $this->categories->allOrdered(),
            'statuses' => EventStatus::options(),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $this->eventService->updateEvent($event, $request->validated());

        return redirect()->route('admin.events.edit', $event)
            ->with('status', 'Event updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $this->eventService->deleteEvent($event);

        return redirect()->route('admin.events.index')
            ->with('status', 'Event moved to trash.');
    }

    public function restore(Event $event): RedirectResponse
    {
        $this->authorize('restore', $event);

        $this->eventService->restoreEvent($event);

        return redirect()->route('admin.events.index', ['trashed' => 1])
            ->with('status', 'Event restored successfully.');
    }

    public function toggleFeatured(Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $this->eventService->toggleFeatured($event);

        return back()->with('status', $event->is_featured ? 'Event marked as featured.' : 'Event removed from featured.');
    }

    public function publish(Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $this->eventService->publish($event);

        return back()->with('status', 'Event published.');
    }

    public function unpublish(Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $this->eventService->unpublish($event);

        return back()->with('status', 'Event unpublished.');
    }

    public function cancel(Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $this->eventService->cancel($event);

        return back()->with('status', 'Event cancelled.');
    }

    public function bulkDestroy(BulkDeleteEventsRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Event::class);

        $count = $this->eventService->bulkDelete($request->validated('ids'));

        return back()->with('status', "{$count} events moved to trash.");
    }

    public function bulkPublish(BulkPublishEventsRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Event::class);

        $count = $this->eventService->bulkPublish($request->validated('ids'));

        return back()->with('status', "{$count} events published.");
    }
}
