{{--
    Site footer — every value comes from Site Settings; the two link
    columns are managed menus. Contact lines fall back to the Contact tab
    when the Footer tab leaves them blank; empty values simply don't render.
--}}
@php
    $siteName = setting('general.site_name', config('app.name'));
    $footerLogo = setting_media('footer.logo', 'webp') ?? setting_media('branding.logo_light', 'webp');
    $description = setting('footer.description') ?? setting('organization.about_short');
    $address = setting('footer.address') ?? collect([setting('organization.address_line'), setting('organization.city'), setting('organization.state'), setting('organization.pin_code')])->filter()->implode(', ');
    $phone = setting('footer.phone') ?? setting('contact.phone_primary');
    $email = setting('footer.email') ?? setting('contact.email_primary');
    $socials = collect([
        'facebook' => [setting('social.facebook_url'), 'Facebook'],
        'instagram' => [setting('social.instagram_url'), 'Instagram'],
        'youtube' => [setting('social.youtube_url'), 'YouTube'],
        'x-twitter' => [setting('social.twitter_url'), 'X (Twitter)'],
        'linkedin' => [setting('social.linkedin_url'), 'LinkedIn'],
        'telegram' => [setting('social.telegram_url'), 'Telegram'],
    ])->filter(fn ($s) => filled($s[0]));
    $whatsapp = preg_replace('/\D/', '', (string) setting('contact.whatsapp_number'));
    $explore = site_menu('footer_explore');
    $involved = site_menu('footer_involved');
    $legal = App\Http\Controllers\Frontend\LegalPageController::published(app(App\Services\SettingsService::class));
    $copyright = str_replace(['{year}', '{name}'], [now()->year, $siteName], setting('footer.copyright_text') ?? '© {year} {name}. All rights reserved.');
    $ctaHeading = setting('footer.cta_heading');
@endphp

<footer class="bg-primary-800 text-text-inverse">
    @if ($ctaHeading)
        <div class="border-b border-white/10">
            <div class="mx-auto flex max-w-[1360px] flex-col items-center justify-between gap-6 px-4 py-10 text-center sm:px-6 md:flex-row md:text-left lg:px-8">
                <div>
                    <p class="font-display text-2xl font-semibold">{{ $ctaHeading }}</p>
                    @if ($ctaText = setting('footer.cta_text'))
                        <p class="mt-2 max-w-xl text-sm text-white/70">{{ $ctaText }}</p>
                    @endif
                </div>
                @if (setting('footer.cta_button_label') && setting('footer.cta_button_url'))
                    <x-ui.button href="{{ setting('footer.cta_button_url') }}" variant="accent">{{ setting('footer.cta_button_label') }}</x-ui.button>
                @endif
            </div>
        </div>
    @endif

    <div class="mx-auto grid max-w-[1360px] grid-cols-1 gap-10 px-4 py-16 sm:px-6 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
        <div>
            @if ($footerLogo)
                <img src="{{ $footerLogo }}" alt="{{ $siteName }}" class="h-10 w-auto" width="160" height="40" loading="lazy" decoding="async">
            @else
                <p class="font-display text-lg font-semibold">{{ $siteName }}</p>
            @endif
            @if ($description)
                <p class="mt-3 text-sm text-white/70">{{ $description }}</p>
            @endif

            @if ($socials->isNotEmpty() || $whatsapp)
                <ul class="mt-5 flex flex-wrap gap-2" aria-label="Social media">
                    @foreach ($socials as $icon => [$url, $name])
                        <li>
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 focus:outline-none focus-visible:ring-3 focus-visible:ring-white/50" aria-label="{{ $name }} (opens in a new tab)">
                                <x-ui.icon :name="$icon" class="h-4 w-4" />
                            </a>
                        </li>
                    @endforeach
                    @if ($whatsapp)
                        <li>
                            <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 focus:outline-none focus-visible:ring-3 focus-visible:ring-white/50" aria-label="WhatsApp (opens in a new tab)">
                                <x-ui.icon name="whatsapp" class="h-4 w-4" />
                            </a>
                        </li>
                    @endif
                </ul>
            @endif

            {{-- Only registrations an administrator has actually entered are shown. --}}
            @php $registrations = array_filter(['Reg. No.' => setting('organization.registration_no'), '12A' => setting('organization.section_12a_no'), '80G' => setting('organization.section_80g_no')]); @endphp
            @if ($registrations)
                <dl class="mt-5 space-y-1 text-xs text-white/60">
                    @foreach ($registrations as $label => $number)
                        <div class="flex gap-2"><dt>{{ $label }}:</dt><dd>{{ $number }}</dd></div>
                    @endforeach
                </dl>
            @endif
        </div>

        @if ($explore->isNotEmpty())
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Explore</p>
                <nav class="mt-4 flex flex-col gap-2 text-sm text-white/80" aria-label="Explore">
                    @foreach ($explore as $item)
                        <a href="{{ $item->href() }}" @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="hover:text-white">{{ $item->label }}</a>
                    @endforeach
                </nav>
            </div>
        @endif

        @if ($involved->isNotEmpty())
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Get Involved</p>
                <nav class="mt-4 flex flex-col gap-2 text-sm text-white/80" aria-label="Get involved">
                    @foreach ($involved as $item)
                        <a href="{{ $item->href() }}" @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="hover:text-white">{{ $item->label }}</a>
                    @endforeach
                </nav>
            </div>
        @endif

        @if ($address || $phone || $email || setting('contact.office_hours'))
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Contact</p>
                <address class="mt-4 flex flex-col gap-2 text-sm not-italic text-white/80">
                    @if ($address)
                        <span class="whitespace-pre-line">{{ $address }}</span>
                    @endif
                    @if ($phone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="hover:text-white">{{ $phone }}</a>
                    @endif
                    @if ($email)
                        <a href="mailto:{{ $email }}" class="hover:text-white">{{ $email }}</a>
                    @endif
                    @if ($hours = setting('contact.office_hours'))
                        <span class="text-white/60">{{ $hours }}</span>
                    @endif
                </address>
            </div>
        @endif
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-[1360px] flex-col items-center justify-between gap-3 px-4 py-6 text-xs text-white/60 sm:flex-row sm:px-6 lg:px-8">
            <p>{{ $copyright }}</p>
            @if ($legal)
                <nav class="flex flex-wrap justify-center gap-4" aria-label="Legal">
                    @foreach ($legal as $slug => $title)
                        <a href="{{ route('legal.show.'.$slug) }}" class="hover:text-white">{{ $title }}</a>
                    @endforeach
                </nav>
            @endif
        </div>
    </div>
</footer>
