<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\EnquiryCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreContactEnquiryRequest;
use App\Models\OrgProfile;
use App\Models\Page;
use App\Services\ContactEnquiryService;
use App\Services\OrgProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(private ContactEnquiryService $enquiryService) {}

    /**
     * Public contact page. Both the Page SEO anchor and the org profile
     * (address, phones, map, socials) are cached forever — SeoService and
     * OrgProfileService bust their keys on update.
     */
    public function index(): View
    {
        $page = Cache::rememberForever('pages.contact', fn () => Page::query()
            ->where('slug', 'contact')
            ->with(['seo.ogImage', 'media'])
            ->first()
        );

        $orgProfile = Cache::rememberForever(OrgProfileService::CACHE_KEY, fn () => OrgProfile::query()
            ->with('media')
            ->first()
        );

        return view('frontend.contact.index', [
            'page' => $page,
            'orgProfile' => $orgProfile,
            'categories' => EnquiryCategory::options(),
            'recaptchaSiteKey' => config('services.recaptcha.site_key'),
        ]);
    }

    public function store(StoreContactEnquiryRequest $request): RedirectResponse
    {
        // Honeypot: bots fill the hidden "website" field. Pretend success so
        // they don't learn the form rejected them — nothing is stored.
        if ($request->filled('website')) {
            return $this->successRedirect();
        }

        $this->enquiryService->submit([
            ...$request->validated(),
            'ip_address' => $request->ip(),
        ]);

        return $this->successRedirect();
    }

    private function successRedirect(): RedirectResponse
    {
        return redirect()
            ->to(route('contact').'#contact-form')
            ->with('enquiry_status', 'Thank you! Your message has been received — a confirmation email is on its way, and we usually respond within 2–3 working days.');
    }
}
