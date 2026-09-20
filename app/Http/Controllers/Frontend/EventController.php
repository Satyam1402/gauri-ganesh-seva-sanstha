<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreEventRegistrationRequest;
use App\Interfaces\EventCategoryRepositoryInterface;
use App\Interfaces\EventRepositoryInterface;
use App\Models\Event;
use App\Services\EventRegistrationService;
use App\Support\Seo\StructuredData;
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
        $isPast = ($filters['when'] ?? null) === 'past';

        return view('frontend.events.index', [
            'events' => $this->events->publicPaginated($filters, 12),
            'categories' => $this->categories->activeOrdered(),
            'featured' => $this->events->featuredList(3),
            'filters' => $filters,
            'seo' => $this->seo()->listing($request, route('events.index'), [
                'title' => $isPast ? 'Past Events' : 'Events',
                'description' => 'Upcoming food distributions, medical camps, awareness campaigns and community events organised by '.setting('general.site_name').'. Register to take part.',
                'breadcrumbs' => [['label' => 'Home', 'url' => route('home')], ['label' => 'Events']],
                'schemas' => [StructuredData::webPage('CollectionPage', 'Events', route('events.index'))],
            ], ['when', 'category']),
        ]);
    }

    public function show(Event $event): View
    {
        abort_unless(in_array($event->status->value, EventStatus::publicValues(), true), 404);

        $event->load(['category', 'media', 'seo.ogImage', 'seo.twitterImage'])->loadCount('activeRegistrations');
        $url = route('events.show', $event);
        $image = $event->getFirstMedia('featured_image')?->getUrl();

        return view('frontend.events.show', [
            'event' => $event,
            'related' => $this->events->related($event, 3),
            'seo' => $this->seo()->forModel($event, [
                'title' => $event->title,
                'description' => $event->short_description,
                'canonical' => $url,
                'image' => $image,
                'breadcrumbs' => array_values(array_filter([
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Events', 'url' => route('events.index')],
                    $event->category ? ['label' => $event->category->name, 'url' => route('events.index', ['category' => $event->category->slug])] : null,
                    ['label' => $event->title],
                ])),
                'schemas' => [StructuredData::event($event, $url, $image)],
            ]),
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
