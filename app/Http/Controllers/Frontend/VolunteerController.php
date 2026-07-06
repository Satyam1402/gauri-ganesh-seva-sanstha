<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\CommunicationMethod;
use App\Enums\Gender;
use App\Enums\VolunteerAvailability;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreVolunteerApplicationRequest;
use App\Models\Page;
use App\Services\VolunteerApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class VolunteerController extends Controller
{
    public function __construct(private VolunteerApplicationService $applicationService) {}

    /**
     * "Become a Volunteer" page — benefits, process timeline and the
     * application form. The Page anchor supplies admin-editable SEO and is
     * cached forever; SeoService busts "pages.{slug}" on update.
     */
    public function create(): View
    {
        $page = Cache::rememberForever('pages.volunteer', fn () => Page::query()
            ->where('slug', 'volunteer')
            ->with(['seo.ogImage', 'media'])
            ->first()
        );

        return view('frontend.volunteers.create', [
            'page' => $page,
            'genders' => Gender::options(),
            'availabilities' => VolunteerAvailability::options(),
            'communicationMethods' => CommunicationMethod::options(),
            'areasOfInterest' => config('volunteers.areas_of_interest', []),
        ]);
    }

    public function store(StoreVolunteerApplicationRequest $request): RedirectResponse
    {
        $application = $this->applicationService->submit($request->validated());

        return redirect()->route('volunteer.thank-you')
            ->with('volunteer_reference', $application->reference)
            ->with('volunteer_first_name', $application->first_name);
    }

    /**
     * Post-submission thank-you page. Only reachable straight after a
     * submission — direct visits are sent back to the application form.
     */
    public function thankYou(): View|RedirectResponse
    {
        if (! session()->has('volunteer_reference')) {
            return redirect()->route('volunteer.create');
        }

        return view('frontend.volunteers.thank-you', [
            'reference' => session('volunteer_reference'),
            'firstName' => session('volunteer_first_name'),
        ]);
    }
}
