<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreEventRegistrationRequest;
use App\Interfaces\EventCategoryRepositoryInterface;
use App\Interfaces\EventRepositoryInterface;
use App\Models\Event;
use App\Services\EventRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(
        private EventRepositoryInterface $events,
        private EventCategoryRepositoryInterface $categories,
        private EventRegistrationService $registrationService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'category', 'when']);

        return view('frontend.events.index', [
            'events' => $this->events->publicPaginated($filters, 12),
            'categories' => $this->categories->activeOrdered(),
            'featured' => $this->events->featuredList(3),
            'filters' => $filters,
        ]);
    }

    public function show(Event $event): View
    {
        abort_unless(in_array($event->status->value, EventStatus::publicValues(), true), 404);

        return view('frontend.events.show', [
            'event' => $event->load(['category', 'media', 'seo.ogImage'])->loadCount('activeRegistrations'),
            'related' => $this->events->related($event, 3),
        ]);
    }

    public function register(StoreEventRegistrationRequest $request, Event $event): RedirectResponse
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $this->registrationService->register($event, $request->validated());

        return redirect()->route('events.show', $event)
            ->with('registration_status', 'Thank you! Your registration has been received — a confirmation email is on its way.');
    }
}
